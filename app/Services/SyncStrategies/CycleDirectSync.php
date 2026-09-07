<?php

declare(strict_types=1);

namespace App\Services\SyncStrategies;

use App\Models\FirebirdSync;
use App\Models\FirebirdSyncItem;
use App\Services\FirebirdReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class CycleDirectSync implements SyncStrategyInterface
{
    /**
     * Tablas que se sincronizan por ciclo: DELETE + INSERT.
     * Orden respetando foreign keys: CICLOS es raíz, GRUPOS depende de CICLOS,
     * CURSOS de CICLOS, CURSOS_DET de CURSOS, ALUMNOS_GRUPOS de ALUMNOS+GRUPOS,
     * HORARIOS_DET de CICLOS+GRUPOS+MATERIAS+PROFESORES.
     */
    protected const TABLAS_CICLO_DIRECTO = [
        'CICLOS',        // Raíz: sin FKs a otras tablas académicas
        'GRUPOS',        // Depende de: CICLOS, NIVELES, TURNOS, SEDES
        'CURSOS',        // Depende de: CICLOS
        'CURSOS_DET',    // Depende de: CURSOS, MATERIAS
        'ALUMNOS_GRUPOS', // Depende de: ALUMNOS, GRUPOS — filtrado por ciclo
        'HORARIOS_DET',  // Depende de: CICLOS, GRUPOS, PROFESORES, MATERIAS, SEDES
    ];

    /**
     * Alumnos: solo ALUMNOS_NIVELES (para obtener IDs) y ALUMNOS (datos completos).
     * ALUMNOS_KARDEX se excluyó del sync — no se sincroniza.
     */
    protected const TABLAS_ALUMNOS_CICLO = [
        'ALUMNOS_NIVELES',
        'ALUMNOS',
    ];

    /**
     * Mapeo explícito: Firebird column name → MySQL column name
     * Solo para columnas donde los nombres NO coinciden con strtolower()
     */
    protected const COLUMN_MAP = [
        'HORARIOS_DET' => [
            'CLAVEPROFESOR'   => 'clave_profesor',
            'CLAVEASIGNATURA' => 'clave_asignatura',
            'CODIGO_GRUPO'    => 'codigo_grupo',
        ],
        'CURSOS' => [
            'CODIGO_CURSO' => 'clave_curso',
            'DESCRIPCION'  => 'nombre_curso',
            'CODIGO_GRUPO' => 'codigo_grupo',
        ],
        'CURSOS_DET' => [
            'CLAVEASIGNATURA' => 'clave_asignatura',
            'CODIGO_CURSO'    => 'clave_curso',
        ],
        'GRUPOS' => [
            'CODIGO_GRUPO' => 'codigo_grupo',
        ],
        'CICLOS' => [],
        'ALUMNOS_NIVELES' => [
            'NUMEROALUMNO' => 'numero_alumno',
        ],
        'ALUMNOS' => [
            'NUMEROALUMNO' => 'numero_alumno',
            'APELLIDOP'    => 'paterno',
            'APELLIDOM'    => 'materno',
            'NOMBRE'       => 'nombre',
        ],
        'ALUMNOS_GRUPOS' => [
            'NUMEROALUMNO'  => 'numero_alumno',
            'CODIGO_GRUPO'  => 'codigo_grupo',
        ],
    ];

    /**
     * PK lógica por tabla (no el id auto-increment de Laravel)
     */
    protected const LOGICAL_PK = [
        'GRUPOS'          => ['codigo_grupo', 'inicial', 'final', 'periodo'],
        'HORARIOS_DET'    => ['inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor', 'clave_asignatura', 'dia', 'sesion'],
        'CURSOS'          => ['inicial', 'final', 'periodo', 'clave_curso'],
        'CURSOS_DET'      => ['inicial', 'final', 'periodo', 'clave_curso', 'clave_asignatura'],
        'CICLOS'          => ['inicial', 'final', 'periodo'],
        'ALUMNOS_NIVELES' => ['numero_alumno', 'inicial', 'final', 'periodo'],
        'ALUMNOS'         => ['numero_alumno'],
        'ALUMNOS_GRUPOS'  => ['numero_alumno', 'codigo_grupo', 'inicial', 'final', 'periodo'],
    ];

    public function execute(
        FirebirdReader $firebirdReader,
        FirebirdSync $sync,
        ?string $ciclo,
        bool $deleteOrphans,
        array $tables = [],
        bool $skipExisting = true,
        callable $progressCallback
    ): array {
        // Filtrar tablas si se proporcionan (usar variables locales, no modificar constantes)
        $tablasCiclo = self::TABLAS_CICLO_DIRECTO;
        $tablasAlumnos = self::TABLAS_ALUMNOS_CICLO;
        if (!empty($tables)) {
            $tablasCiclo = array_intersect(self::TABLAS_CICLO_DIRECTO, $tables);
            $tablasAlumnos = array_intersect(self::TABLAS_ALUMNOS_CICLO, $tables);
        }
        if (! $ciclo) {
            return $this->errorResult('sync_ciclo requiere parámetro ciclo');
        }

        [$I, $F, $P] = $this->parseCiclo($ciclo);
        $log = [];
        $errors = [];
        $totals = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0];

        $mysql = DB::connection()->getPdo();

        // FASE 1: Tablas directas por ciclo
        $log[] = ['tipo' => 'info', 'msg' => "=== CICLO {$I}-{$F}-{$P} ==="];
        $log[] = ['tipo' => 'info', 'msg' => "--- FASE 1: Tablas directas ---"];

        foreach ($tablasCiclo as $tabla) {
            $result = $this->syncCycleTable(
                $firebirdReader, $mysql, $tabla, $I, $F, $P, $deleteOrphans, $skipExisting
            );
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];
        }

        // FASE 2: Alumnos por ciclo
        $log[] = ['tipo' => 'info', 'msg' => "--- FASE 2: Alumnos por ciclo ---"];

        // 2.1 ALUMNOS_NIVELES (con filtro ciclo) → obtener IDs de alumnos
        $tabla = 'ALUMNOS_NIVELES';
        $result = $this->syncCycleTable(
            $firebirdReader, $mysql, $tabla, $I, $F, $P, $deleteOrphans, $skipExisting
        );
        $log = array_merge($log, $result['log']);
        $errors = array_merge($errors, $result['errors']);
        $totals['created'] += $result['created'];
        $totals['updated'] += $result['updated'];
        $totals['deleted'] += $result['deleted'];
        $totals['processed'] += $result['processed'];

        // Extraer IDs de alumnos
        $alumnoIds = $this->getAlumnoIdsFromNiveles($mysql, $I, $F, $P);
        $log[] = ['tipo' => 'info', 'msg' => "Alumnos encontrados: " . count($alumnoIds)];

        if (empty($alumnoIds)) {
            $log[] = ['tipo' => 'skip', 'msg' => "No hay alumnos, saltando ALUMNOS"];
        } else {
            // 2.2 ALUMNOS (SIN filtro ciclo - datos completos del alumno)
            $tablasAlumnosSinNiveles = array_diff($tablasAlumnos, ['ALUMNOS_NIVELES']);
            foreach ($tablasAlumnosSinNiveles as $tabla) {
                $result = $this->syncTableWithoutCycleFilter(
                    $firebirdReader, $mysql, $tabla, 'NUMEROALUMNO', $alumnoIds, $deleteOrphans, $skipExisting
                );
                $log = array_merge($log, $result['log']);
                $errors = array_merge($errors, $result['errors']);
                $totals['created'] += $result['created'];
                $totals['updated'] += $result['updated'];
                $totals['deleted'] += $result['deleted'];
                $totals['processed'] += $result['processed'];
            }
        }

        $log[] = ['tipo' => 'info', 'msg' => "=== FIN CICLO {$I}-{$F}-{$P} ==="];

        return [
            'created' => $totals['created'],
            'updated' => $totals['updated'],
            'deleted' => $totals['deleted'],
            'processed' => $totals['processed'],
            'total' => $totals['total'],
            'log' => $log,
            'errors' => $errors,
        ];
    }

    protected function syncCycleTable(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $tabla,
        int $I, int $F, int $P,
        bool $deleteOrphans,
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            $fbCols = $fbReader->getColumns($tabla);
            $myCols = array_map(fn($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, $tabla));
            $total = $fbReader->countRows($tabla, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total} en FB"];

            $datos = $total > 0
                ? $fbReader->fetchRows($tabla, $fbCols, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P])
                : [];

            $result = $this->smartSync($mysql, $tabla, $fbCols, $myCols, $datos, $deleteOrphans, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P], $skipExisting);
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $created += $result['created'];
            $updated += $result['updated'];
            $deleted += $result['deleted'];
            $processed += $result['processed'];

        } catch (Throwable $e) {
            $errors[] = "{$tabla}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR - " . $e->getMessage()];
        }

        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    protected function syncTableWithoutCycleFilter(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $tabla,
        string $idColumn,
        array $ids,
        bool $deleteOrphans,
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            $fbCols = $fbReader->getColumns($tabla);
            $myCols = array_map(fn($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, $tabla));
            $total = $fbReader->countRowsIn($tabla, $idColumn, $ids);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total} en FB (SIN filtro ciclo)"];

            if ($total > 0) {
                $datos = $fbReader->fetchRowsIn($tabla, $fbCols, $idColumn, $ids);
                $result = $this->smartSync($mysql, $tabla, $fbCols, $myCols, $datos, $deleteOrphans, null, [], $skipExisting);
                $log = array_merge($log, $result['log']);
                $errors = array_merge($errors, $result['errors']);
                $created += $result['created'];
                $updated += $result['updated'];
                $deleted += $result['deleted'];
                $processed += $result['processed'];
            } else {
                $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros"];
            }
        } catch (Throwable $e) {
            $errors[] = "{$tabla}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR - " . $e->getMessage()];
        }

        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    protected function smartSync(
        PDO $mysql,
        string $tabla,
        array $fbCols,
        array $myCols,
        array $datosFb,
        bool $deleteOrphans,
        ?string $whereDelete = null,
        array $paramsDelete = [],
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        if (empty($datosFb)) {
            if ($whereDelete) {
                $del = $mysql->prepare("DELETE FROM `{$tabla}` WHERE {$whereDelete}");
                $del->execute($paramsDelete);
                $deleted = $del->rowCount();
                $log[] = ['tipo' => 'ok', 'msg' => "{$tabla}: limpiados {$deleted} (sin datos FB)"];
            } else {
                $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros en FB"];
            }
            return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
        }

        // Mapear columnas FB → MySQL (usar COLUMN_MAP explícito + fallback a strtolower)
        $fbMap = [];
        $explicitMap = self::COLUMN_MAP[$tabla] ?? [];
        foreach ($fbCols as $fc) {
            if (isset($explicitMap[$fc])) {
                // 1) Match exacto en COLUMN_MAP (ej. CLAVEPROFESOR → clave_profesor)
                $myCol = $explicitMap[$fc];
                if (in_array($myCol, $myCols)) {
                    $fbMap[$myCol] = $fc;
                }
            } else {
                // 2) strtolower directo (ej. DIA → dia)
                $lc = strtolower($fc);
                if (in_array($lc, $myCols)) {
                    $fbMap[$lc] = $fc;
                } else {
                    // 3) Normalizar quitando guiones bajos (ej. CLAVEASIGNATURA → clave_asignatura)
                    $normFb = str_replace('_', '', $lc);
                    foreach ($myCols as $myCol) {
                        if (str_replace('_', '', $myCol) === $normFb) {
                            $fbMap[$myCol] = $fc;
                            break;
                        }
                    }
                }
            }
        }
        $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: " . count($fbMap) . " columnas mapeadas: " . implode(', ', array_keys($fbMap))];
        if (empty($fbMap)) {
            $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: sin columnas comunes"];
            return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
        }

        $mappedFb = [];
        foreach ($datosFb as $row) {
            $m = [];
            foreach ($fbMap as $mk => $fk) $m[$mk] = $row[$fk] ?? null;
            $mappedFb[] = $m;
        }

        // PK MySQL (usar LOGICAL_PK si existe, fallback a detección automática)
        $pkCols = self::LOGICAL_PK[$tabla] ?? $this->getMysqlPk($mysql, $tabla);
        // Filtrar PK solo con columnas que existen en el mapeo
        $pkCols = array_values(array_filter($pkCols, fn($pk) => isset($fbMap[$pk])));
        if (empty($pkCols)) {
            $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: sin PK definida"];
            return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
        }

        // Indexar por PK
        $indexed = [];
        foreach ($mappedFb as $row) {
            $pkKey = '';
            foreach ($pkCols as $pk) $pkKey .= '|' . ($row[$pk] ?? '');
            $indexed[$pkKey] = $row;
        }
        if (count($indexed) < count($mappedFb)) {
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: " . (count($mappedFb) - count($indexed)) . " duplicados FB eliminados"];
        }
        $mappedFb = array_values($indexed);

        $colNames = array_keys($mappedFb[0]);
        $nonPk = array_values(array_diff($colNames, $pkCols));

        // Leer existentes
        $existing = [];
        $allColsSelect = implode(', ', array_map(fn($c) => "`{$c}`", $colNames));
        $stmt = $mysql->query("SELECT {$allColsSelect} FROM `{$tabla}`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pkKey = '';
            foreach ($pkCols as $pk) $pkKey .= '|' . ($row[$pk] ?? '');
            $existing[$pkKey] = $row;
        }

        $toInsert = [];
        $toUpdate = [];
        foreach ($mappedFb as $row) {
            $pkKey = '';
            foreach ($pkCols as $pk) $pkKey .= '|' . ($row[$pk] ?? '');
            if (! isset($existing[$pkKey])) {
                $toInsert[] = $row;
            } else {
                $changed = false;
                foreach ($nonPk as $col) {
                    if ($this->safeVal($row[$col]) !== $this->safeVal($existing[$pkKey][$col])) {
                        $changed = true;
                        break;
                    }
                }
                if ($changed && !$skipExisting) $toUpdate[] = $row;
                unset($existing[$pkKey]);
            }
        }

        $orphanCount = $deleteOrphans ? count($existing) : 0;

        // Transacción
        $mysql->beginTransaction();
        try {
            if ($deleteOrphans && $orphanCount > 0) {
                foreach ($existing as $oldRow) {
                    $wp = []; $vp = [];
                    foreach ($pkCols as $pk) { $wp[] = "`{$pk}` = ?"; $vp[] = $oldRow[$pk]; }
                    $mysql->prepare("DELETE FROM `{$tabla}` WHERE " . implode(' AND ', $wp))->execute($vp);
                    $deleted++;
                }
            }

            if (! empty($toInsert)) {
                $ph = implode(',', array_fill(0, count($colNames), '?'));
                $cs = implode(',', array_map(fn($c) => "`{$c}`", $colNames));
                foreach (array_chunk($toInsert, 500) as $batch) {
                    $values = []; $params = [];
                    foreach ($batch as $row) {
                        $values[] = "({$ph})";
                        foreach ($colNames as $cn) $params[] = $row[$cn] ?? null;
                    }
                    $mysql->prepare("INSERT INTO `{$tabla}` ({$cs}) VALUES " . implode(',', $values))->execute($params);
                    $created += count($batch);
                }
            }

            if (! empty($toUpdate) && ! empty($nonPk)) {
                $setParts = implode(', ', array_map(fn($c) => "`{$c}` = ?", $nonPk));
                $whereParts = implode(' AND ', array_map(fn($c) => "`{$c}` = ?", $pkCols));
                foreach (array_chunk($toUpdate, 200) as $batch) {
                    foreach ($batch as $row) {
                        $params = [];
                        foreach ($nonPk as $cn) $params[] = $row[$cn] ?? null;
                        foreach ($pkCols as $pk) $params[] = $row[$pk] ?? null;
                        $mysql->prepare("UPDATE `{$tabla}` SET {$setParts} WHERE {$whereParts}")->execute($params);
                        $updated++;
                    }
                }
            }

            $mysql->commit();

            $msg = "{$tabla}: INSERT {$created}";
            if ($updated > 0) $msg .= ", UPDATE {$updated}";
            if ($deleted > 0) $msg .= ", DELETE {$deleted}";
            $msg .= " (FB: " . count($mappedFb) . ", MySQL: " . ($orphanCount > 0 ? ($deleted . " huérf") : (count($mappedFb) - $created - $updated) . " intactos") . ")";
            $log[] = ['tipo' => 'ok', 'msg' => $msg];

        } catch (Throwable $e) {
            if ($mysql->inTransaction()) $mysql->rollBack();
            $errors[] = "{$tabla}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR - " . $e->getMessage()];
        }

        $processed = count($mappedFb);
        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    protected function getMysqlColumns(PDO $mysql, string $tabla): array
    {
        $stmt = $mysql->prepare("SHOW COLUMNS FROM `{$tabla}`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getMysqlPk(PDO $mysql, string $tabla): array
    {
        $cols = $this->getMysqlColumns($mysql, $tabla);
        return array_map(fn($r) => strtolower($r['Field']), array_filter($cols, fn($r) => $r['Key'] === 'PRI'));
    }

    protected function getAlumnoIdsFromNiveles(PDO $mysql, int $I, int $F, int $P): array
    {
        $stmt = $mysql->prepare("SELECT DISTINCT numero_alumno FROM alumnos_niveles WHERE inicial = ? AND final = ? AND periodo = ?");
        $stmt->execute([$I, $F, $P]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'numero_alumno');
    }

    protected function parseCiclo(string $ciclo): array
    {
        $parts = explode('-', $ciclo);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException("Ciclo inválido: {$ciclo}. Formato: INICIAL-FINAL-PERIODO");
        }
        return [(int)$parts[0], (int)$parts[1], (int)$parts[2]];
    }

    protected function safeVal($v): ?string
    {
        if ($v === null) return null;
        if (is_string($v) && $v === '') return null;
        return (string)$v;
    }

    protected function errorResult(string $msg): array
    {
        return [
            'created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0,
            'log' => [['tipo' => 'error', 'msg' => $msg]], 'errors' => [$msg],
        ];
    }
}