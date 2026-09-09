<?php

declare(strict_types=1);

namespace App\Services\SyncStrategies;

use App\Models\FirebirdSync;
use App\Services\FirebirdReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class CatalogSmartSync implements SyncStrategyInterface
{
    /**
     * Mapeo Firebird → MySQL.
     *
     * keys MySQL column name → value Firebird column name.
     * 'identity' = columnas que forman la PK natural (para dedup y WHERE en UPDATE).
     * 'skip_insert_identity' = si true, no se inserta esa columna (MySQL la auto-genera).
     */
    protected const TABLE_MAP = [
        'CFGSEDES' => [
            'mysql'   => 'sedes',
            'columns' => [
                'id_campus'   => 'ID_CAMPUS',
                'descripcion' => 'DESCRIPCION',
                'direccion'   => 'DOMICILIO',
            ],
            'identity' => ['id_campus'],
        ],
        'CFGNIVELES' => [
            'mysql'   => 'niveles',
            'columns' => [
                'nivel'       => 'NIVEL',
                'descripcion' => 'DESCRIPCION',
            ],
            'identity' => ['nivel'],
        ],
        'CFGTURNOS' => [
            'mysql'   => 'turnos',
            'columns' => [
                'turno'       => 'TURNO',
                'descripcion' => 'DESCRIPCIONTURNO',
            ],
            'identity' => ['turno'],
        ],
        'CICLOS' => [
            'mysql'   => 'ciclos',
            'columns' => [
                'inicial'      => 'INICIAL',
                'final'        => 'FINAL',
                'periodo'      => 'PERIODO',
                'descripcion'  => 'DESCRIPCION',
                'fecha_inicial' => 'FECHA_INICIAL',
                'fecha_final'  => 'FECHA_FINAL',
                'activo'       => 'ACTIVO',
            ],
            'identity' => ['inicial', 'final', 'periodo'],
        ],
        'GRUPOS' => [
            'mysql'   => 'grupos',
            'columns' => [
                'codigo_grupo' => 'CODIGO_GRUPO',
                'inicial'      => 'INICIAL',
                'final'        => 'FINAL',
                'periodo'      => 'PERIODO',
                'grado'        => 'GRADO',
                'turno'        => 'TURNO',
                'nivel'        => 'NIVEL',
                'inscritos'    => 'INSCRITOS',
                'id_campus'    => 'ID_CAMPUS',
            ],
            'identity' => ['codigo_grupo', 'inicial', 'final', 'periodo'],
        ],
        'ALUMNOS' => [
            'mysql'   => 'alumnos',
            'columns' => [
                'numero_alumno'    => 'NUMEROALUMNO',
                'paterno'          => 'PATERNO',
                'materno'          => 'MATERNO',
                'nombre'           => 'NOMBRE',
                'sexo'             => 'GENERO',
                'fecha_nacimiento' => 'FECHA_NACIMIENTO',
                'estado_civil'     => 'ESTADO_CIVIL',
                'direccion'        => 'DOMICILIO',
                'cp'               => 'CP',
                'ciudad'           => 'CIUDAD',
                'estado'           => 'ESTADO',
                'telefono'         => 'TELEFONO',
                'email'            => 'EMAIL',
                'lugar_nacimiento' => 'LUGAR_NACIMIENTO',
                'nacionalidad'     => 'NACIONALIDAD',
                'nivel'            => 'NIVEL',
                'turno'            => 'TURNO',
                'id_campus'        => 'ID_CAMPUS',
                'fecha_ingreso'    => 'FECHA_INGRESO',
                'estatus'          => 'STATUS',
                'observaciones'    => 'OBSERVACIONES',
            ],
            'identity' => ['numero_alumno'],
        ],
        'PROFESORES' => [
            'mysql'   => 'profesores',
            'columns' => [
                'clave_profesor'  => 'CLAVEPROFESOR',
                'nombre_profesor' => 'NOMBREPROFESOR',
                'departamento'    => 'DEPARTAMENTO',
                'contrato'        => 'CONTRATO',
                'status_actual'   => 'STATUSACTUAL',
                'origen_horario'  => 'ORIGEN_HORARIO',
                'fecha_ingreso'   => 'FECHA_INGRESO',
                'id_campus'       => 'ID_CAMPUS',
                'nivel'           => 'NIVEL',
                'email'           => 'EMAIL',
            ],
            'identity' => ['clave_profesor'],
        ],
        'HORARIOS_DET' => [
            'mysql'   => 'horarios_det',
            'columns' => [
                'inicial'               => 'INICIAL',
                'final'                 => 'FINAL',
                'periodo'               => 'PERIODO',
                'codigo_grupo'          => 'CODIGO_GRUPO',
                'clave_profesor'        => 'CLAVEPROFESOR',
                'clave_asignatura'      => 'CLAVEASIGNATURA',
                'dia'                   => 'DIA',
                'sesion'                => 'SESION',
                'horas_teoria_practica' => 'HORAS_TEORIA_PRACTICA',
                'id_campus'             => 'ID_CAMPUS',
                'edificio'              => 'EDIFICIO',
                'aula'                  => 'AULA',
            ],
            'identity' => ['inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor', 'clave_asignatura', 'dia', 'sesion'],
        ],
        'CURSOS' => [
            'mysql'   => 'cursos',
            'columns' => [
                'inicial'      => 'INICIAL',
                'final'        => 'FINAL',
                'periodo'      => 'PERIODO',
                'clave_curso'  => 'CODIGO_CURSO',
                'nombre_curso' => 'DESCRIPCION',
                'turno'        => 'TURNO',
                'id_campus'    => 'ID_CAMPUS',
            ],
            'identity' => ['clave_curso', 'inicial', 'final', 'periodo'],
        ],
        'CFGPLANES_MST' => [
            'mysql'   => 'planes',
            'columns' => [
                'id_plan'     => 'ID_PLAN',
                'nombre_plan' => 'NOMBRE_PLAN',
                'nivel'       => 'NIVEL',
            ],
            'identity' => ['id_plan'],
        ],
        'EMPLEADOS_CONTRATOS_CAT' => [
            'mysql'   => 'contratos',
            'columns' => [
                'contrato'    => 'CONTRATO',
                'descripcion' => 'DESCRIPCION',
            ],
            'identity' => ['contrato'],
        ],
        'CFGTIPOSEVALUACION' => [
            'mysql'   => 'metodos_eval',
            'columns' => [
                'id_eval'     => 'ID_TIPOEVAL',
                'descripcion' => 'DESCRIPCION',
            ],
            'identity' => ['id_eval'],
        ],
        'CFGSESIONES' => [
            'mysql'   => 'sesiones_base',
            'columns' => [
                'nivel'        => 'NIVEL',
                'turno'        => 'TURNO',
                'sesion'       => 'SESION',
                'descripcion'  => 'DESCRIPCION',
                'hora_inicio'  => 'HORA_INICIO',
                'hora_fin'     => 'HORA_FIN',
                'receso'       => 'RECESO',
            ],
            'identity' => ['nivel', 'turno', 'sesion'],
        ],
        'CFGPLANES_DET' => [
            'mysql'   => 'materias',
            'columns' => [
                'clave_asignatura'   => 'CLAVEASIGNATURA',
                'id_plan'            => 'ID_PLAN',
                'nombre_asignatura'  => 'NOMBREASIGNATURA',
                'nombre_corto'       => 'NOMBRECORTO',
                'creditos'           => 'CREDITOS',
                'horas_teoria'       => 'HORAS_TEORIA',
                'horas_practica'     => 'HORAS_PRACTICA',
            ],
            'identity' => ['clave_asignatura', 'id_plan'],
        ],
        'ALUMNOS_GRUPOS' => [
            'mysql'   => 'alumnos_grupos',
            'columns' => [
                'numero_alumno' => 'NUMEROALUMNO',
                'codigo_grupo'  => 'CODIGO_GRUPO',
                'inicial'       => 'INICIAL',
                'final'         => 'FINAL',
                'periodo'       => 'PERIODO',
            ],
            'identity' => ['numero_alumno', 'codigo_grupo', 'inicial', 'final', 'periodo'],
        ],
        'ALUMNOS_KARDEX' => [
            'mysql'   => 'alumnos_kardex',
            'columns' => [
                'numero_alumno'   => 'NUMEROALUMNO',
                'inicial'         => 'INICIAL',
                'final'           => 'FINAL',
                'periodo'         => 'PERIODO',
                'clave_asignatura' => 'CLAVEASIGNATURA',
                'id_eval'         => 'ID_EVAL',
                'calificacion'    => 'CALIFICACION',
                'tipo_examen'     => 'TIPOEXAMEN',
                'fecha_examen'    => 'FECHA',
                'observaciones'   => 'NOTA',
            ],
            'identity' => ['numero_alumno', 'inicial', 'final', 'periodo', 'clave_asignatura', 'id_eval'],
        ],
        'EMPLEADOS' => [
            'mysql'   => 'employees',
            'columns' => [
                'user_id'         => 'NUMEMPLEADO',
                'name'            => 'NOMBREEMPLEADO',
                'numero_empleado' => 'NUMEMPLEADO',
                'departamento'    => 'DEPARTAMENTO',
                'cargo'           => 'CARGO',
                'contrato'        => 'CONTRATO',
                'status_actual'   => 'STATUSACTUAL',
                'fecha_ingreso'   => 'FECHA_INGRESO',
                'id_campus'       => 'ID_CAMPUS',
                'nivel'           => 'NIVEL',
                'tarjeta_id'      => 'TARJETA_ID',
            ],
            'identity' => ['user_id'],
        ],
    ];

    /** Tablas que siempre se procesan en chunks por su tamaño */
    protected const CHUNKED_TABLES = [
        'ALUMNOS_KARDEX',
        'ALUMNOS_GRUPOS',
        'HORARIOS_DET',
    ];

    protected const CHUNK_SIZE = 5000;

    public function execute(
        FirebirdReader $firebirdReader,
        FirebirdSync $sync,
        ?string $ciclo,
        bool $deleteOrphans,
        array $tables = [],
        bool $skipExisting = true,
        callable $progressCallback
    ): array {
        $log = [];
        $errors = [];
        $totals = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0];

        $mysql = DB::connection()->getPdo();

        // Filtrar TABLE_MAP si se proporcionan tablas específicas
        $tableMap = self::TABLE_MAP;
        if (!empty($tables)) {
            $tableMap = array_intersect_key(self::TABLE_MAP, array_flip($tables));
        }

        $log[] = ['tipo' => 'info', 'msg' => "=== CATÁLOGOS (smart sync) — " . count($tableMap) . " tablas ==="];
        $progressCallback(0, count($tableMap), 'Iniciando catálogos');

        $index = 0;
        foreach ($tableMap as $fbTable => $mapping) {
            $index++;
            $mysqlTable = $mapping['mysql'];
            $stage = "Catálogo: {$fbTable} → {$mysqlTable}";
            $progressCallback($index, count($tableMap), $stage);

            $result = $this->syncCatalogTable($firebirdReader, $mysql, $fbTable, $mapping, $deleteOrphans, $skipExisting);
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];
        }

        return [
            'created'   => $totals['created'],
            'updated'   => $totals['updated'],
            'deleted'   => $totals['deleted'],
            'processed' => $totals['processed'],
            'total'     => $totals['total'],
            'log'       => $log,
            'errors'    => $errors,
        ];
    }

    protected function syncCatalogTable(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $fbTable,
        array $mapping,
        bool $deleteOrphans,
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        $mysqlTable = $mapping['mysql'];
        $columnMap  = $mapping['columns'];
        $identity   = $mapping['identity'];

        try {
            // 1. Validar columnas Firebird
            $fbAvailableCols = $fbReader->getColumns($fbTable);

            $validColumns = [];
            $missingCols = [];
            foreach ($columnMap as $myCol => $fbCol) {
                if (in_array(strtoupper($fbCol), $fbAvailableCols)) {
                    $validColumns[$myCol] = strtoupper($fbCol);
                } else {
                    $missingCols[] = "{$fbCol}→{$myCol}";
                }
            }

            if (empty($validColumns)) {
                $log[] = ['tipo' => 'skip', 'msg' => "{$fbTable}: sin columnas válidas en FB"];
                return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
            }

            if (!empty($missingCols)) {
                $log[] = ['tipo' => 'info', 'msg' => "{$fbTable}: columnas FB no encontradas: " . implode(', ', $missingCols)];
            }

            $fbColNames = array_values($validColumns);
            $totalFb = $fbReader->countRows($fbTable);
            $log[] = ['tipo' => 'info', 'msg' => "{$fbTable}: {$totalFb} registros en FB"];

            if ($totalFb === 0) {
                $log[] = ['tipo' => 'skip', 'msg' => "{$fbTable}: 0 registros en FB, saltando"];
                return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
            }

            $myColsForWrite = array_keys($validColumns);
            $useChunked = in_array($fbTable, self::CHUNKED_TABLES) || $totalFb > 10000;

            if ($useChunked) {
                $result = $this->syncCatalogTableChunked($fbReader, $mysql, $fbTable, $mysqlTable, $validColumns, $identity, $myColsForWrite, $totalFb, $deleteOrphans, $skipExisting);
            } else {
                $result = $this->syncCatalogTableFull($fbReader, $mysql, $fbTable, $mysqlTable, $validColumns, $identity, $myColsForWrite, $totalFb, $deleteOrphans, $skipExisting);
            }

            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $created = $result['created'];
            $updated = $result['updated'];
            $deleted = $result['deleted'];
            $processed = $result['processed'];

        } catch (Throwable $e) {
            $errors[] = "{$fbTable}→{$mysqlTable}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$fbTable}→{$mysqlTable}: ERROR - " . $e->getMessage()];
        }

        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    /**
     * Tabla pequeña: carga todo en memoria, compara, INSERT/UPDATE en transacción.
     */
    protected function syncCatalogTableFull(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $fbTable,
        string $mysqlTable,
        array $validColumns,
        array $identity,
        array $myColsForWrite,
        int $totalFb,
        bool $deleteOrphans,
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            $datosFb = $fbReader->fetchRows($fbTable, array_values($validColumns));

            // Transformar
            $mappedData = [];
            foreach ($datosFb as $row) {
                $m = [];
                foreach ($validColumns as $myCol => $fbCol) {
                    $m[$myCol] = $row[$fbCol] ?? null;
                }
                $mappedData[] = $m;
            }
            unset($datosFb);

            // Deduplicar
            $indexed = [];
            foreach ($mappedData as $row) {
                $key = $this->buildIdentityKey($row, $identity);
                $indexed[$key] = $row;
            }
            $dupsRemoved = count($mappedData) - count($indexed);
            if ($dupsRemoved > 0) {
                $log[] = ['tipo' => 'info', 'msg' => "{$fbTable}: {$dupsRemoved} duplicados FB eliminados"];
            }
            $mappedData = array_values($indexed);

            // Leer existentes MySQL
            $allMyCols = array_merge(['id'], $myColsForWrite);
            $selectCols = implode(', ', array_map(fn($c) => "`{$c}`", $allMyCols));
            $existing = [];
            $stmt = $mysql->query("SELECT {$selectCols} FROM `{$mysqlTable}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = $this->buildIdentityKey($row, $identity);
                $existing[$key] = $row;
            }

            // Comparar
            $toInsert = [];
            $toUpdate = [];
            foreach ($mappedData as $row) {
                $key = $this->buildIdentityKey($row, $identity);
                if (!isset($existing[$key])) {
                    $toInsert[] = $row;
                } else {
                    $changed = false;
                    foreach ($myColsForWrite as $col) {
                        if ($this->safeVal($row[$col]) !== $this->safeVal($existing[$key][$col])) {
                            $changed = true;
                            break;
                        }
                    }
                    if ($changed && !$skipExisting) {
                        $toUpdate[] = array_merge($row, ['_mysql_id' => $existing[$key]['id']]);
                    }
                    unset($existing[$key]);
                }
            }

            $orphanCount = $deleteOrphans ? count($existing) : 0;

            // Transacción
            $mysql->beginTransaction();
            try {
                if ($deleteOrphans && $orphanCount > 0) {
                    foreach ($existing as $oldRow) {
                        $mysql->prepare("DELETE FROM `{$mysqlTable}` WHERE `id` = ?")->execute([$oldRow['id']]);
                        $deleted++;
                    }
                }

                if (!empty($toInsert)) {
                    $result = $this->executeBatchInsert($mysql, $mysqlTable, $myColsForWrite, $toInsert);
                    $created += $result['inserted'];
                    if (!empty($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    }
                }

                if (!empty($toUpdate)) {
                    $setParts = implode(', ', array_map(fn($c) => "`{$c}` = ?", $myColsForWrite));
                    foreach (array_chunk($toUpdate, 200) as $batch) {
                        foreach ($batch as $row) {
                            $params = [];
                            foreach ($myColsForWrite as $cn) $params[] = $row[$cn] ?? null;
                            $params[] = $row['_mysql_id'];
                            $mysql->prepare("UPDATE `{$mysqlTable}` SET {$setParts} WHERE `id` = ?")->execute($params);
                            $updated++;
                        }
                    }
                }

                $mysql->commit();

                $msg = "{$fbTable} → {$mysqlTable}: INSERT {$created}";
                if ($updated > 0) $msg .= ", UPDATE {$updated}";
                if ($deleted > 0) $msg .= ", DELETE {$deleted}";
                $msg .= " (FB: " . count($mappedData) . ")";
                $log[] = ['tipo' => 'ok', 'msg' => $msg];

            } catch (Throwable $e) {
                if ($mysql->inTransaction()) $mysql->rollBack();
                throw $e;
            }

            $processed = count($mappedData);

        } catch (Throwable $e) {
            $errors[] = "{$fbTable}→{$mysqlTable}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$fbTable}→{$mysqlTable}: ERROR - " . $e->getMessage()];
        }

        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    /**
     * Tabla grande: lee MySQL existentes una vez, luego procesa Firebird en chunks.
     * Nunca carga todos los registros FB en memoria a la vez.
     */
protected function syncCatalogTableChunked(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $fbTable,
        string $mysqlTable,
        array $validColumns,
        array $identity,
        array $myColsForWrite,
        int $totalFb,
        bool $deleteOrphans,
        bool $skipExisting = true
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            // 1. Cargar existentes MySQL UNA VEZ (identity → id + values para comparar)
            $allMyCols = array_merge(['id'], $myColsForWrite);
            $selectCols = implode(', ', array_map(fn($c) => "`{$c}`", $allMyCols));
            $existing = [];
            $stmt = $mysql->query("SELECT {$selectCols} FROM `{$mysqlTable}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = $this->buildIdentityKey($row, $identity);
                $existing[$key] = $row;
            }
            $log[] = ['tipo' => 'info', 'msg' => "{$fbTable}: " . count($existing) . " registros existentes en MySQL"];

            // 2. Procesar Firebird en chunks
            $fbColNames = array_values($validColumns);
            $chunkIndex = 0;

            foreach ($fbReader->fetchRowsChunked($fbTable, $fbColNames, self::CHUNK_SIZE) as $chunk) {
                $chunkIndex++;

                // Transformar chunk
                $mappedChunk = [];
                foreach ($chunk as $row) {
                    $m = [];
                    foreach ($validColumns as $myCol => $fbCol) {
                        $m[$myCol] = $row[$fbCol] ?? null;
                    }
                    $mappedChunk[] = $m;
                }
                unset($chunk);

                // Deduplicar chunk
                $indexed = [];
                foreach ($mappedChunk as $row) {
                    $key = $this->buildIdentityKey($row, $identity);
                    $indexed[$key] = $row;
                }
                $mappedChunk = array_values($indexed);

                // Separar INSERT vs UPDATE
                $toInsert = [];
                $toUpdate = [];
                foreach ($mappedChunk as $row) {
                    $key = $this->buildIdentityKey($row, $identity);
                    if (!isset($existing[$key])) {
                        $toInsert[] = $row;
                    } else {
                        $changed = false;
                        foreach ($myColsForWrite as $col) {
                            if ($this->safeVal($row[$col]) !== $this->safeVal($existing[$key][$col])) {
                                $changed = true;
                                break;
                            }
                        }
                        if ($changed && !$skipExisting) {
                            $toUpdate[] = array_merge($row, ['_mysql_id' => $existing[$key]['id']]);
                        }
                        unset($existing[$key]);
                    }
                }

                // Ejecutar batch
                $mysql->beginTransaction();
                try {
                    if (!empty($toInsert)) {
                        $result = $this->executeBatchInsert($mysql, $mysqlTable, $myColsForWrite, $toInsert);
                        $created += $result['inserted'];
                        if (!empty($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        }
                    }

                    if (!empty($toUpdate)) {
                        $setParts = implode(', ', array_map(fn($c) => "`{$c}` = ?", $myColsForWrite));
                        foreach (array_chunk($toUpdate, 200) as $batch) {
                            foreach ($batch as $row) {
                                $params = [];
                                foreach ($myColsForWrite as $cn) $params[] = $row[$cn] ?? null;
                                $params[] = $row['_mysql_id'];
                                $mysql->prepare("UPDATE `{$mysqlTable}` SET {$setParts} WHERE `id` = ?")->execute($params);
                                $updated++;
                            }
                        }
                    }

                    $mysql->commit();

                } catch (Throwable $e) {
                    if ($mysql->inTransaction()) $mysql->rollBack();
                    $errors[] = "{$fbTable}→{$mysqlTable} chunk {$chunkIndex}: " . $e->getMessage();
                    $log[] = ['tipo' => 'error', 'msg' => "{$fbTable}→{$mysqlTable} chunk {$chunkIndex}: ERROR - " . $e->getMessage()];
                }

                $processed += count($mappedChunk);
                unset($mappedChunk, $toInsert, $toUpdate);
            }

            // 3. DELETE huérfanos (si aplica) — los que quedaron en $existing no fueron encontrados en FB
            if ($deleteOrphans && !empty($existing)) {
                $orphanIds = array_column($existing, 'id');
                $mysql->beginTransaction();
                try {
                    foreach (array_chunk($orphanIds, 500) as $batch) {
                        $ph = implode(',', array_fill(0, count($batch), '?'));
                        $mysql->prepare("DELETE FROM `{$mysqlTable}` WHERE `id` IN ({$ph})")->execute($batch);
                        $deleted += count($batch);
                    }
                    $mysql->commit();
                } catch (Throwable $e) {
                    if ($mysql->inTransaction()) $mysql->rollBack();
                    $errors[] = "{$fbTable}→{$mysqlTable} delete: " . $e->getMessage();
                }
            }

            $msg = "{$fbTable} → {$mysqlTable}: INSERT {$created}";
            if ($updated > 0) $msg .= ", UPDATE {$updated}";
            if ($deleted > 0) $msg .= ", DELETE {$deleted}";
            $msg .= " (FB total: {$totalFb}, chunks procesados: {$chunkIndex})";
            $log[] = ['tipo' => 'ok', 'msg' => $msg];

        } catch (Throwable $e) {
            $errors[] = "{$fbTable}→{$mysqlTable}: " . $e->getMessage();
            $log[] = ['tipo' => 'error', 'msg' => "{$fbTable}→{$mysqlTable}: ERROR - " . $e->getMessage()];
        }

        return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
    }

    protected function buildIdentityKey(array $row, array $identityCols): string
    {
        $parts = [];
        foreach ($identityCols as $col) {
            $parts[] = (string) ($row[$col] ?? '');
        }
        return implode('|', $parts);
    }

    protected function safeVal($v): ?string
    {
        if ($v === null) return null;
        if (is_string($v) && trim($v) === '') return null;
        return (string) $v;
    }

    /**
     * Try batch INSERT IGNORE; on failure, fall back to individual INSERT IGNORE.
     * Uses INSERT IGNORE so MySQL skips constraint violations at engine level (fast).
     */
    protected function executeBatchInsert(PDO $mysql, string $table, array $columns, array $rows): array
    {
        $inserted = 0;
        $skipped = 0;
        $errors = [];
        $ph = implode(',', array_fill(0, count($columns), '?'));
        $cs = implode(',', array_map(fn($c) => "`{$c}`", $columns));

        foreach (array_chunk($rows, 500) as $batch) {
            try {
                $values = [];
                $params = [];
                foreach ($batch as $row) {
                    $values[] = "({$ph})";
                    foreach ($columns as $cn) $params[] = $row[$cn] ?? null;
                }
                $beforeRows = $mysql->query("SELECT ROW_COUNT()")->fetchColumn();
                $mysql->prepare("INSERT IGNORE INTO `{$table}` ({$cs}) VALUES " . implode(',', $values))->execute($params);
                $afterRows = $mysql->query("SELECT ROW_COUNT()")->fetchColumn();
                $inserted += (int) $afterRows;
                $skipped += count($batch) - (int) $afterRows;
            } catch (Throwable $e) {
                // Entire batch failed — try individual rows
                foreach ($batch as $row) {
                    try {
                        $params = [];
                        foreach ($columns as $cn) $params[] = $row[$cn] ?? null;
                        $mysql->prepare("INSERT IGNORE INTO `{$table}` ({$cs}) VALUES ({$ph})")->execute($params);
                        if ($mysql->query("SELECT ROW_COUNT()")->fetchColumn() > 0) {
                            $inserted++;
                        } else {
                            $skipped++;
                            $errorInfo = $mysql->errorInfo();
                            if (!empty($errorInfo[2])) {
                                $errors[] = "{$table} skip: " . $errorInfo[2] . " | values: " . json_encode(array_slice($params, 0, 4));
                            }
                        }
                    } catch (Throwable $e2) {
                        $errors[] = "{$table} skip: " . $e2->getMessage();
                        $skipped++;
                    }
                }
            }
        }

        if ($skipped > 0) {
            $errors[] = "{$table}: {$skipped} rows skipped (constraint violations)";
        }

        return ['inserted' => $inserted, 'errors' => $errors];
    }
}
