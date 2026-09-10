<?php

declare(strict_types=1);

namespace App\Services\SyncStrategies;

use App\Models\FirebirdSync;
use App\Services\FirebirdReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class CustomSyncStrategy implements SyncStrategyInterface
{
    /**
     * Mapeo de tabla Firebird → estrategia que la maneja
     */
    public const TABLE_STRATEGY_MAP = [
        // Catálogos base (CatalogSmartSync) - NO requieren ciclo
        'CFGSEDES' => 'catalog',
        'CFGNIVELES' => 'catalog',
        'CFGTURNOS' => 'catalog',
        'CICLOS' => 'catalog',
        'CFGPLANES_MST' => 'catalog',
        'CFGPLANES_DET' => 'catalog',
        'CFGSESIONES' => 'catalog',
        'CFGTIPOSEVALUACION' => 'catalog',
        'EMPLEADOS_CONTRATOS_CAT' => 'catalog',
        'ALUMNOS' => 'catalog',
        'PROFESORES' => 'catalog',
        'EMPLEADOS' => 'catalog',

        // Tablas de ciclo (CycleDirectSync) - REQUIEREN filtro por ciclo
        'GRUPOS' => 'cycle',
        'ALUMNOS_GRUPOS' => 'cycle',  // Filtrado por ciclo (inicial, final, periodo)
        'ALUMNOS_CURSOS' => 'cycle',
        'HORARIOS_DET' => 'cycle',
        'CURSOS' => 'cycle',
        'CURSOS_DET' => 'cycle',

        // Alumnos (CycleDirectSync fase 2) - REQUIEREN ciclo para obtener IDs
        'ALUMNOS_NIVELES' => 'alumnos',

        // ALUMNOS_KARDEX se excluyó del sync — no se sincroniza
    ];

    /**
     * Dependencias de tablas: tabla → [tablas que DEBEN existir en MySQL antes de sincronizar esta].
     * Si falta alguna dependencia, MySQL lanzará error FK (RESTRICT/CASCADE).
     * Solo se listan dependencias con FK real en BD, no dependencias lógicas.
     */
    public const TABLE_DEPENDENCIES = [
        // Ciclo → depende de catálogos base
        'GRUPOS'         => ['CICLOS', 'CFGNIVELES', 'CFGTURNOS', 'CFGSEDES'],
            'CURSOS'         => ['CICLOS', 'CFGPLANES_MST'],
            'CURSOS_DET'     => ['CURSOS'],
        'ALUMNOS_GRUPOS' => ['ALUMNOS', 'GRUPOS'],
        'ALUMNOS_CURSOS' => ['ALUMNOS', 'CURSOS'],
        'HORARIOS_DET'   => ['CICLOS', 'GRUPOS', 'PROFESORES', 'CFGPLANES_DET', 'CFGSEDES'],

        // Alumnos → dependen de catálogos
        'ALUMNOS_NIVELES' => ['ALUMNOS'],
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
        if (empty($tables)) {
            return $this->errorResult('sync_custom requiere al menos una tabla en el parámetro tables');
        }

        $log = [];
        $errors = [];
        $totals = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0];

        // Agrupar tablas por estrategia
        $byStrategy = ['catalog' => [], 'cycle' => [], 'alumnos' => []];
        $unknownTables = [];

        foreach ($tables as $fbTable) {
            $strategy = self::TABLE_STRATEGY_MAP[$fbTable] ?? null;
            if ($strategy && isset($byStrategy[$strategy])) {
                $byStrategy[$strategy][] = $fbTable;
            } else {
                $unknownTables[] = $fbTable;
            }
        }

        if (!empty($unknownTables)) {
            $log[] = ['tipo' => 'warning', 'msg' => 'Tablas no reconocidas (se ignorarán): ' . implode(', ', $unknownTables)];
        }

        // Validar dependencias FK antes de procesar
        $depWarnings = $this->validateDependencies($tables);
        foreach ($depWarnings as $warning) {
            $log[] = ['tipo' => 'warning', 'msg' => $warning];
        }

        $log[] = ['tipo' => 'info', 'msg' => '=== SYNC CUSTOM ==='];
        $log[] = ['tipo' => 'info', 'msg' => 'Catálogos: ' . (count($byStrategy['catalog']) ? implode(', ', $byStrategy['catalog']) : 'ninguno')];
        $log[] = ['tipo' => 'info', 'msg' => 'Ciclo: ' . (count($byStrategy['cycle']) ? implode(', ', $byStrategy['cycle']) : 'ninguno')];
        $log[] = ['tipo' => 'info', 'msg' => 'Alumnos: ' . (count($byStrategy['alumnos']) ? implode(', ', $byStrategy['alumnos']) : 'ninguno')];

        $totalTables = count($byStrategy['catalog']) + count($byStrategy['cycle']) + count($byStrategy['alumnos']);
        $currentTable = 0;

        // 1. Ejecutar catálogos base
        if (!empty($byStrategy['catalog'])) {
            $catalogSync = new CatalogSmartSync();
            // No modificar la constante, pasar las tablas filtradas por parámetro
            $result = $catalogSync->execute($firebirdReader, $sync, null, $deleteOrphans, $byStrategy['catalog'], $skipExisting, function ($p, $t, $stage) use ($progressCallback, &$currentTable, $totalTables) {
                $progressCallback($currentTable + $p, $totalTables, $stage);
            });

            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];
            $currentTable += count($byStrategy['catalog']);
        }

        // 2. Ejecutar tablas de ciclo (requieren $ciclo)
        if (!empty($byStrategy['cycle'])) {
            if (! $ciclo) {
                $log[] = ['tipo' => 'error', 'msg' => 'Tablas de ciclo requieren parámetro ciclo'];
                $errors[] = 'Tablas de ciclo requieren parámetro ciclo';
            } else {
                $cycleSync = new CycleDirectSync();
                // Pasar las tablas filtradas por parámetro
                $result = $cycleSync->execute($firebirdReader, $sync, $ciclo, $deleteOrphans, $byStrategy['cycle'], $skipExisting, function ($p, $t, $stage) use ($progressCallback, &$currentTable, $totalTables) {
                    $progressCallback($currentTable + $p, $totalTables, $stage);
                });

                $log = array_merge($log, $result['log']);
                $errors = array_merge($errors, $result['errors']);
                $totals['created'] += $result['created'];
                $totals['updated'] += $result['updated'];
                $totals['deleted'] += $result['deleted'];
                $totals['processed'] += $result['processed'];
                $currentTable += count($byStrategy['cycle']);
            }
        }

        // 3. Ejecutar tablas de alumnos (requieren $ciclo para obtener IDs)
        if (!empty($byStrategy['alumnos'])) {
            if (! $ciclo) {
                $log[] = ['tipo' => 'error', 'msg' => 'Tablas de alumnos requieren parámetro ciclo'];
                $errors[] = 'Tablas de alumnos requieren parámetro ciclo';
            } else {
                $cycleSync = new CycleDirectSync();
                // Pasar las tablas filtradas por parámetro
                $result = $cycleSync->execute($firebirdReader, $sync, $ciclo, $deleteOrphans, $byStrategy['alumnos'], $skipExisting, function ($p, $t, $stage) use ($progressCallback, &$currentTable, $totalTables) {
                    $progressCallback($currentTable + $p, $totalTables, $stage);
                });

                $log = array_merge($log, $result['log']);
                $errors = array_merge($errors, $result['errors']);
                $totals['created'] += $result['created'];
                $totals['updated'] += $result['updated'];
                $totals['deleted'] += $result['deleted'];
                $totals['processed'] += $result['processed'];
                $currentTable += count($byStrategy['alumnos']);
            }
        }

        $log[] = ['tipo' => 'info', 'msg' => '=== FIN SYNC CUSTOM ==='];

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

    /**
     * Valida dependencias de forma estática (para usar desde controladores).
     */
    public static function validateDependenciesStatic(array $tables): array
    {
        $warnings = [];
        $selectedSet = array_flip($tables);

        foreach ($tables as $table) {
            $deps = self::TABLE_DEPENDENCIES[$table] ?? [];
            $missing = [];
            foreach ($deps as $dep) {
                if (!isset($selectedSet[$dep])) {
                    $missing[] = $dep;
                }
            }
            if (!empty($missing)) {
                $warnings[] = "{$table} requiere: " . implode(', ', $missing);
            }
        }

        return $warnings;
    }

    protected function errorResult(string $msg): array
    {
        return [
            'created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0,
            'log' => [['tipo' => 'error', 'msg' => $msg]], 'errors' => [$msg],
        ];
    }

    /**
     * Valida que las dependencias FK estén cubiertas.
     * Retorna warnings (no bloquea, pero informa al usuario).
     */
    protected function validateDependencies(array $tables): array
    {
        $warnings = [];
        $selectedSet = array_flip($tables);

        foreach ($tables as $table) {
            $deps = self::TABLE_DEPENDENCIES[$table] ?? [];
            $missing = [];
            foreach ($deps as $dep) {
                if (!isset($selectedSet[$dep])) {
                    $missing[] = $dep;
                }
            }
            if (!empty($missing)) {
                $warnings[] = "⚠️ {$table} requiere que existan en MySQL: "
                    . implode(', ', $missing)
                    . ". Si no existen, la sincronización fallará por FK constraint.";
            }
        }

        return $warnings;
    }
}