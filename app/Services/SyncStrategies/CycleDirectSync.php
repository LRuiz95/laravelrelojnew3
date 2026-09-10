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
     *
     * NOTA: ALUMNOS_GRUPOS y ALUMNOS_NIVELES requieren que ALUMNOS existan primero.
     * Se movieron a FASE 2 después de sincronizar ALUMNOS.
     */
    protected const TABLAS_CICLO_DIRECTO = [
        'CICLOS',        // Raíz: sin FKs a otras tablas académicas
        'GRUPOS',        // Depende de: CICLOS, NIVELES, TURNOS, SEDES
        'CURSOS',        // Depende de: CICLOS
        'CURSOS_DET',    // Depende de: CURSOS, MATERIAS
        'HORARIOS_DET',  // Depende de: CICLOS, GRUPOS, PROFESORES, MATERIAS, SEDES
    ];

    /**
     * Alumnos: ALUMNOS primero (sin FK), luego ALUMNOS_NIVELES y ALUMNOS_GRUPOS.
     * ALUMNOS_KARDEX se excluyó del sync — no se sincroniza.
     */
    protected const TABLAS_ALUMNOS_CICLO = [
        'ALUMNOS',
        'ALUMNOS_NIVELES',
        'ALUMNOS_GRUPOS',
        'ALUMNOS_CURSOS',
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
            'NIVEL'        => 'nivel',
            'ID_PLAN' => 'id_plan',
            'ID_TIPOEVAL' => 'id_tipoeval',
            'ID_ETAPA' => 'id_etapa',
            'CLAVEASIGNATURA' => 'clave_asignatura',
            'CLAVEPROFESOR' => 'clave_profesor',
            'CUPOMAXIMO' => 'cupo_maximo',
            'DESDE' => 'desde',
            'HASTA' => 'hasta',
            'SESIONES' => 'sesiones',
            'INSCRITOS' => 'inscritos',
            'SUPLENTE' => 'suplente',
        ],
        'CURSOS_DET' => [
            'CODIGO_CURSO' => 'curso_id',    // Resolved via COMPOSITE_FK_RESOLVE
            'ID_ESCUELA' => 'id_escuela',
            'DIA'          => 'dia',
            'HORA_INICIAL' => 'hora_inicial',
            'HORA_FINAL'   => 'hora_final',
            'ID_CAMPUS'    => 'id_campus',
            'EDIFICIO'     => 'edificio',
            'AULA'         => 'aula',
            'INICIAL'      => 'inicial',      // For composite FK resolution
            'FINAL'        => 'final',
            'PERIODO'      => 'periodo',
        ],
        'GRUPOS' => [
            'CODIGO_GRUPO' => 'codigo_grupo',
            'TIPO_GRUPO' => 'tipo_grupo',
            'CUPO_MAXIMO' => 'cupo_maximo',
            'GRUPO' => 'grupo',
            'CLAVEPROFESOR_TITULAR' => 'clave_profesor_titular',
            'CLAVEPROFESOR_SUPLENTE' => 'clave_profesor_suplente',
            'CICLO_CERRADO' => 'ciclo_cerrado',
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
        'ALUMNOS_CURSOS' => [
            'NUMEROALUMNO' => 'numero_alumno',
            'CODIGO_CURSO' => 'codigo_curso',
            'STATUS' => 'status',
            'ID_PLAN' => 'id_plan',
            'ID_TIPOEVAL' => 'id_tipoeval',
            'ID_ETAPA' => 'id_etapa',
            'CLAVEASIGNATURA' => 'clave_asignatura',
            'VERSION' => 'version',
            'TIPOEXAMEN' => 'tipoexamen',
            'WEB' => 'web',
            'WEB_OPERACION' => 'web_operacion',
        ],
    ];

    /**
     * Valores por defecto para columnas que pueden venir NULL de Firebird
     * pero son NOT NULL en MySQL.
     */
    protected const COLUMN_DEFAULTS = [
        'HORARIOS_DET' => [
            'horas_teoria_practica' => 0,
        ],
    ];

    /**
     * Columnas que requieren validación FK contra otra tabla.
     * Si el valor no existe en la tabla referenciada, se establece NULL.
     */
    protected const FK_VALIDATION = [
        'HORARIOS_DET' => [
            'id_campus' => 'sedes',
            'clave_profesor' => 'profesores',
            'codigo_grupo' => 'grupos',
            'clave_asignatura' => 'materias',
        ],
        'GRUPOS' => [
            'id_campus' => 'sedes',
        ],
        'CURSOS' => [
            'id_campus' => 'sedes',
        ],
        'CURSOS_DET' => [
            'id_campus' => 'sedes',
        ],
    ];

    /**
     * Resolución de FKs compuestas: tabla MySQL → columnas Firebird que componen la FK → tabla referenciada + columnas.
     * Se usa para resolver curso_id desde (CODIGO_CURSO, INICIAL, FINAL, PERIODO).
     */
    protected const COMPOSITE_FK_RESOLVE = [
        'CURSOS_DET' => [
            'column' => 'curso_id',  // Columna MySQL a resolver
            'fb_keys' => ['CODIGO_CURSO', 'INICIAL', 'FINAL', 'PERIODO'],  // Columnas Firebird
            'ref_table' => 'cursos',  // Tabla referenciada
            'ref_columns' => ['clave_curso', 'inicial', 'final', 'periodo'],  // Columnas MySQL en tabla referenciada
            'ref_id' => 'id',  // Columna ID de la tabla referenciada
        ],
    ];

    /**
     * PK lógica por tabla (no el id auto-increment de Laravel)
     */
    protected const LOGICAL_PK = [
        'GRUPOS'          => ['codigo_grupo', 'inicial', 'final', 'periodo'],
        'HORARIOS_DET'    => ['inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor', 'clave_asignatura', 'dia', 'sesion'],
        'CURSOS'          => ['inicial', 'final', 'periodo', 'clave_curso'],
        'CURSOS_DET'      => ['curso_id', 'dia', 'hora_inicial'],
        'CICLOS'          => ['inicial', 'final', 'periodo'],
        'ALUMNOS_NIVELES' => ['numero_alumno', 'inicial', 'final', 'periodo'],
        'ALUMNOS'         => ['numero_alumno'],
        'ALUMNOS_GRUPOS'  => ['numero_alumno', 'codigo_grupo', 'inicial', 'final', 'periodo'],
        'ALUMNOS_CURSOS'  => ['inicial', 'final', 'periodo', 'codigo_curso', 'numero_alumno', 'id_tipoeval', 'id_etapa', 'clave_asignatura', 'version', 'tipoexamen'],
    ];

    public function execute(
        FirebirdReader $firebirdReader,
        FirebirdSync $sync,
        ?string $ciclo,
        bool $deleteOrphans,
        array $tables,
        bool $skipExisting,
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
        $workset = $this->buildCycleWorkset($firebirdReader, $I, $F, $P);
        $log[] = ['tipo' => 'info', 'msg' => sprintf(
            'Conjunto de trabajo: %d grupos, %d cursos, %d alumnos',
            count($workset['grupos']), count($workset['cursos']), count($workset['alumnos'])
        )];

        // FASE 1: Tablas directas por ciclo
        $log[] = ['tipo' => 'info', 'msg' => "=== CICLO {$I}-{$F}-{$P} ==="];
        $log[] = ['tipo' => 'info', 'msg' => "--- FASE 1: Tablas directas ---"];

        foreach ($tablasCiclo as $tabla) {
            $result = $this->syncCycleTable(
                $firebirdReader, $mysql, $tabla, $I, $F, $P, $deleteOrphans, $skipExisting, $workset
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

        // 2.1 Obtener IDs de alumnos DIRECTAMENTE de Firebird (no de MySQL)
        //     ALUMNOS_NIVELES en Firebird tiene los IDs, pero en MySQL no existen aún
        //     porque ALUMNOS no se ha sincronizado (chicken-and-egg).
        $alumnoIds = $workset['alumnos'];
        $log[] = ['tipo' => 'info', 'msg' => "Alumnos encontrados en FB: " . count($alumnoIds)];

        if (empty($alumnoIds)) {
            $log[] = ['tipo' => 'skip', 'msg' => "No hay alumnos en Firebird, saltando ALUMNOS"];
        } else {
            // 2.2 ALUMNOS (SIN filtro ciclo — datos completos del alumno, necesita IDs de FB)
            $result = $this->syncTableWithoutCycleFilter(
                $firebirdReader, $mysql, 'ALUMNOS', 'NUMEROALUMNO', $alumnoIds, $deleteOrphans, $skipExisting
            );
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];

            // 2.3 ALUMNOS_NIVELES (con filtro ciclo — ahora ALUMNOS ya existe en MySQL)
            $result = $this->syncCycleTable(
                $firebirdReader, $mysql, 'ALUMNOS_NIVELES', $I, $F, $P, $deleteOrphans, $skipExisting, $workset
            );
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];

            // 2.4 ALUMNOS_GRUPOS (con filtro ciclo — ahora ALUMNOS y GRUPOS ya existen)
            $result = $this->syncAlumnosGruposForCycle(
                $firebirdReader, $mysql, $I, $F, $P, $deleteOrphans, $skipExisting, $workset
            );
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];

            if (in_array('ALUMNOS_CURSOS', $tablasAlumnos, true)) {
                $result = $this->syncAlumnosCursosForCycle(
                    $firebirdReader, $mysql, $I, $F, $P, $deleteOrphans, $skipExisting, $workset
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
        bool $skipExisting = false,
        array $workset = []
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            $fbCols = $fbReader->getColumns($tabla);
            $myCols = array_map(fn($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, $tabla));
            [$where, $params] = $this->worksetFilter($tabla, $I, $F, $P, $workset);
            $deleteWhere = "INICIAL = ? AND FINAL = ? AND PERIODO = ?";
            $deleteParams = [$I, $F, $P];
            if ($tabla === 'ALUMNOS_NIVELES' && ! empty($workset['alumnos'])) {
                $datos = [];
                foreach (array_chunk($workset['alumnos'], 1000) as $studentChunk) {
                    $placeholders = implode(',', array_fill(0, count($studentChunk), '?'));
                    $chunkParams = array_merge([$I, $F, $P], $studentChunk);
                    $datos = array_merge($datos, $fbReader->fetchRows($tabla, $fbCols, 'INICIAL = ? AND FINAL = ? AND PERIODO = ? AND NUMEROALUMNO IN (' . $placeholders . ')', $chunkParams));
                }
                $total = count($datos);
                $deleteOrphans = false;
                $deleteWhere = null;
                $deleteParams = [];
            } else {
                $total = $fbReader->countRows($tabla, $where, $params);
                $datos = $total > 0 ? $fbReader->fetchRows($tabla, $fbCols, $where, $params) : [];
            }
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total} en FB"];

            $result = $this->smartSync($mysql, $tabla, $fbCols, $myCols, $datos, $deleteOrphans, $deleteWhere, $deleteParams, $skipExisting);
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

    /**
     * ALUMNOS_GRUPOS no tiene columnas de ciclo en Firebird. El ciclo se
     * determina por los grupos del ciclo y se materializa en MySQL para
     * conservar la clave compuesta usada por la aplicación.
     */
    protected function syncAlumnosGruposForCycle(
        FirebirdReader $fbReader,
        PDO $mysql,
        int $I,
        int $F,
        int $P,
        bool $deleteOrphans,
        bool $skipExisting,
        array $workset = []
    ): array {
        $log = [];
        $errors = [];

        try {
            $groupRows = $fbReader->fetchRows(
                'GRUPOS',
                ['CODIGO_GRUPO'],
                'INICIAL = ? AND FINAL = ? AND PERIODO = ?',
                [$I, $F, $P],
            );
            $groups = $workset['grupos'] ?: array_values(array_unique(array_filter(array_column($groupRows, 'CODIGO_GRUPO'))));

            if (empty($groups)) {
                return ['log' => [['tipo' => 'skip', 'msg' => "ALUMNOS_GRUPOS: sin grupos para {$I}-{$F}-{$P}" ]], 'errors' => [], 'created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0];
            }

            $fbCols = $fbReader->getColumns('ALUMNOS_GRUPOS');
            $rows = [];
            foreach (array_chunk($groups, 500) as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $batch = $fbReader->fetchRows('ALUMNOS_GRUPOS', $fbCols, 'CODIGO_GRUPO IN (' . $placeholders . ')', $chunk);
                foreach ($batch as $row) {
                    $row['INICIAL'] = $I;
                    $row['FINAL'] = $F;
                    $row['PERIODO'] = $P;
                    $rows[] = $row;
                }
            }

            $result = $this->smartSync(
                $mysql,
                'ALUMNOS_GRUPOS',
                array_values(array_unique(array_merge($fbCols, ['INICIAL', 'FINAL', 'PERIODO']))),
                array_map(fn ($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, 'alumnos_grupos')),
                $rows,
                $deleteOrphans,
                null,
                [],
                $skipExisting,
            );
            $result['log'][] = ['tipo' => 'info', 'msg' => "ALUMNOS_GRUPOS: ciclo heredado desde " . count($groups) . ' grupos'];
            return $result;
        } catch (Throwable $e) {
            $errors[] = 'ALUMNOS_GRUPOS: ' . $e->getMessage();
            return ['log' => [['tipo' => 'error', 'msg' => end($errors)]], 'errors' => $errors, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0];
        }
    }

    protected function syncAlumnosCursosForCycle(
        FirebirdReader $fbReader,
        PDO $mysql,
        int $I,
        int $F,
        int $P,
        bool $deleteOrphans,
        bool $skipExisting,
        array $workset
    ): array {
        try {
            $fbCols = $fbReader->getColumns('ALUMNOS_CURSOS');
            $rows = $fbReader->fetchRows('ALUMNOS_CURSOS', $fbCols, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);
            $rows = array_values(array_filter($rows, fn ($row) => in_array((string) ($row['CODIGO_CURSO'] ?? ''), $workset['cursos'], true)
                && in_array((string) ($row['NUMEROALUMNO'] ?? ''), $workset['alumnos'], true)));

            foreach ($rows as &$row) {
                $row['INICIAL'] = $I;
                $row['FINAL'] = $F;
                $row['PERIODO'] = $P;
            }
            unset($row);

            return $this->smartSync(
                $mysql,
                'ALUMNOS_CURSOS',
                array_values(array_unique(array_merge($fbCols, ['INICIAL', 'FINAL', 'PERIODO']))),
                array_map(fn ($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, 'alumnos_cursos')),
                $rows,
                $deleteOrphans,
                null,
                [],
                $skipExisting,
            );
        } catch (Throwable $e) {
            return ['log' => [['tipo' => 'error', 'msg' => 'ALUMNOS_CURSOS: ' . $e->getMessage()]], 'errors' => [$e->getMessage()], 'created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0];
        }
    }

    protected function buildCycleWorkset(FirebirdReader $reader, int $I, int $F, int $P): array
    {
        $groups = array_values(array_unique(array_filter(array_column(
            $reader->fetchRows('GRUPOS', ['CODIGO_GRUPO'], 'INICIAL = ? AND FINAL = ? AND PERIODO = ?', [$I, $F, $P]),
            'CODIGO_GRUPO'
        ))));
        $groupStudents = [];
        foreach (array_chunk($groups, 500) as $chunk) {
            if (!$chunk) continue;
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $groupStudents = array_merge($groupStudents, $reader->fetchRows('ALUMNOS_GRUPOS', ['NUMEROALUMNO'], 'CODIGO_GRUPO IN (' . $placeholders . ')', $chunk));
        }
        $candidateIds = array_values(array_unique(array_filter(array_column($groupStudents, 'NUMEROALUMNO'))));
        $alumnoRows = [];
        foreach (array_chunk($candidateIds, 1000) as $chunk) {
            if (!$chunk) continue;
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $alumnoRows = array_merge($alumnoRows, $reader->fetchRows('ALUMNOS_CURSOS', ['NUMEROALUMNO', 'CODIGO_CURSO'], 'INICIAL = ? AND FINAL = ? AND PERIODO = ? AND NUMEROALUMNO IN (' . $placeholders . ')', array_merge([$I, $F, $P], $chunk)));
        }
        $courseCodes = array_values(array_unique(array_filter(array_column($alumnoRows, 'CODIGO_CURSO'))));
        $courses = $reader->fetchRows('CURSOS', ['CODIGO_CURSO', 'CLAVEASIGNATURA'], 'INICIAL = ? AND FINAL = ? AND PERIODO = ?', [$I, $F, $P]);
        $courses = array_values(array_filter($courses, fn ($row) => in_array((string) ($row['CODIGO_CURSO'] ?? ''), $courseCodes, true)));
        $courseCodes = array_values(array_unique(array_filter(array_column($courses, 'CODIGO_CURSO'))));
        $subjects = array_values(array_unique(array_filter(array_column($courses, 'CLAVEASIGNATURA'))));
        $levelRows = $reader->fetchRows('ALUMNOS_NIVELES', ['NUMEROALUMNO'], 'INICIAL = ? AND FINAL = ? AND PERIODO = ?', [$I, $F, $P]);
        $studentIds = array_values(array_intersect($candidateIds, array_column($levelRows, 'NUMEROALUMNO')));

        return ['grupos' => $groups, 'cursos' => $courseCodes, 'materias' => $subjects, 'alumnos' => $studentIds];
    }

    protected function worksetFilter(string $table, int $I, int $F, int $P, array $workset): array
    {
        $base = ['INICIAL = ?', 'FINAL = ?', 'PERIODO = ?'];
        $params = [$I, $F, $P];
        $field = match ($table) {
            'HORARIOS_DET' => 'CODIGO_GRUPO',
            'CURSOS_DET' => 'CODIGO_CURSO',
            'CURSOS' => 'CODIGO_CURSO',
            default => null,
        };
        $values = match ($table) {
            'HORARIOS_DET' => $workset['grupos'] ?? [],
            'CURSOS_DET' => $workset['cursos'] ?? [],
            'CURSOS' => $workset['cursos'] ?? [],
            'ALUMNOS_NIVELES' => $workset['alumnos'] ?? [],
            default => [],
        };
        if ($table === 'ALUMNOS_NIVELES' && $values) {
            $base[] = 'NUMEROALUMNO IN (' . implode(',', array_fill(0, count($values), '?')) . ')';
            array_push($params, ...$values);
        }
        if ($field && $values) {
            $base[] = $field . ' IN (' . implode(',', array_fill(0, count($values), '?')) . ')';
            array_push($params, ...$values);
        }
        return [implode(' AND ', $base), $params];
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
        $defaults = self::COLUMN_DEFAULTS[$tabla] ?? [];
        $fkValidation = self::FK_VALIDATION[$tabla] ?? [];
        $fkCache = [];
        $compositeFk = self::COMPOSITE_FK_RESOLVE[$tabla] ?? null;
        $compositeFkMap = [];

        // Precargar valores FK válidos
        foreach ($fkValidation as $column => $refTable) {
            $fkCache[$column] = $this->getValidFkValues($mysql, $refTable, $column);
        }

        // Precargar mapa de FK compuesta (ej: CURSOS_DET → curso_id desde cursos)
        if ($compositeFk) {
            $compositeFkMap = $this->buildCompositeFkMap(
                $mysql, $compositeFk['ref_table'], $compositeFk['ref_columns'],
                $compositeFk['ref_id'], $compositeFk['fb_keys']
            );
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: " . count($compositeFkMap) . " FKs compuestas resueltas"];
        }

        foreach ($datosFb as $row) {
            $m = [];
            foreach ($fbMap as $mk => $fk) {
                $val = $row[$fk] ?? null;
                // Aplicar default si el valor es NULL y hay un default definido
                if ($val === null && isset($defaults[$mk])) {
                    $val = $defaults[$mk];
                }
                // Validar FK: si el valor no existe en la tabla referenciada, set NULL
                if ($val !== null && isset($fkCache[$mk]) && !in_array((string)$val, $fkCache[$mk])) {
                    $val = null;
                }
                $m[$mk] = $val;
            }

            // Resolver FK compuesta (ej: curso_id desde CODIGO_CURSO+INICIAL+FINAL+PERIODO)
            if ($compositeFk && !empty($compositeFk['column'])) {
                $fkKey = '';
                foreach ($compositeFk['fb_keys'] as $fbKey) {
                    $fkKey .= '|' . ($row[$fbKey] ?? '');
                }
                $m[$compositeFk['column']] = $compositeFkMap[$fkKey] ?? null;
            }

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
        $existingSql = "SELECT {$allColsSelect} FROM `{$tabla}`";
        $existingParams = [];
        if ($whereDelete) {
            $existingSql .= " WHERE {$whereDelete}";
            $existingParams = $paramsDelete;
        }
        $stmt = $mysql->prepare($existingSql);
        $stmt->execute($existingParams);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pkKey = '';
            foreach ($pkCols as $pk) $pkKey .= '|' . ($row[$pk] ?? '');
            $existing[$pkKey] = $row;
        }

        // Filtrar registros con PK NULL después de FK validation (no se pueden insertar)
        $beforeCount = count($mappedFb);
        $mappedFb = array_filter($mappedFb, function ($row) use ($pkCols) {
            foreach ($pkCols as $pk) {
                if ($row[$pk] === null || $row[$pk] === '') {
                    return false;
                }
            }
            return true;
        });
        $mappedFb = array_values($mappedFb);
        $filteredCount = $beforeCount - count($mappedFb);
        if ($filteredCount > 0) {
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$filteredCount} registros con PK NULL/inválida filtrados"];
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

    /**
     * Obtener IDs de alumnos DIRECTAMENTE de Firebird (ALUMNOS_NIVELES).
     * Evita el chicken-and-egg: no necesita que ALUMNOS exista en MySQL primero.
     */
    protected function getAlumnoIdsFromFirebird(FirebirdReader $fbReader, int $I, int $F, int $P): array
    {
        try {
            $fbCols = $fbReader->getColumns('ALUMNOS_NIVELES');
            $datos = $fbReader->fetchRows('ALUMNOS_NIVELES', $fbCols, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);

            // Extraer NUMEROALUMNO único
            $ids = [];
            foreach ($datos as $row) {
                $num = $row['NUMEROALUMNO'] ?? null;
                if ($num !== null && $num !== '') {
                    $ids[] = (string) $num;
                }
            }
            return array_unique($ids);
        } catch (\Throwable $e) {
            Log::warning("No se pudieron obtener IDs de alumnos desde Firebird: " . $e->getMessage());
            return [];
        }
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

    /**
     * Obtener valores válidos de una columna FK para validar referencias.
     */
    protected function getValidFkValues(PDO $mysql, string $table, string $column): array
    {
        try {
            $stmt = $mysql->query("SELECT DISTINCT `{$column}` FROM `{$table}` WHERE `{$column}` IS NOT NULL");
            return array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), $column));
        } catch (\Throwable $e) {
            Log::warning("No se pudieron obtener valores FK de {$table}.{$column}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Construir mapa de resolución de FK compuesta.
     * Retorna array donde la key es la composite key de Firebird (pipe-separated)
     * y el value es el ID de la tabla referenciada.
     *
     * Ejemplo: CURSOS_DET
     *   ref_table: cursos
     *   ref_columns: [clave_curso, inicial, final, periodo]
     *   ref_id: id
     *   fb_keys: [CODIGO_CURSO, INICIAL, FINAL, PERIODO]
     *
     * Retorna: ['MAT-01|1|2025|1' => 42, ...]
     */
    protected function buildCompositeFkMap(
        PDO $mysql,
        string $refTable,
        array $refColumns,
        string $refId,
        array $fbKeys
    ): array {
        try {
            $selectCols = array_merge([$refId], $refColumns);
            $selectSql = implode(', ', array_map(fn($c) => "`{$c}`", $selectCols));
            $stmt = $mysql->query("SELECT {$selectSql} FROM `{$refTable}`");

            $map = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = '';
                foreach ($refColumns as $col) {
                    $key .= '|' . ($row[$col] ?? '');
                }
                $map[$key] = $row[$refId];
            }
            return $map;
        } catch (\Throwable $e) {
            Log::warning("No se pudo construir mapa FK compuesta para {$refTable}: " . $e->getMessage());
            return [];
        }
    }
}