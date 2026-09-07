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
    /** Catálogos sin filtro de ciclo (smart sync: INSERT/UPDATE/DELETE huérfanos) */
    protected const TABLAS_SIN_CICLO = [
        'CFGSEDES',
        'CFGPLANES_DET',
        'PROFESORES',
        'CFGSESIONES',
        'CFGTURNOS',
        'CFGNIVELES',
        'EMPLEADOS',
        'EMPLEADOS_ASISTENCIA',
        'EMPLEADOS_HORARIOS',
        'EMPLEADOS_CFGHORARIOS',
        'EMPLEADOS_CFGHORARIOS_DET',
        'EMPLEADOS_CONTRATOS_CAT',
        'CFGPLANES_MST',
        'CFGPLANES_EVAL',
        'CFGPLANES_ETAPAS',
        'CFGSTATUS',
        'CFGAULAS',
    ];

    public function execute(
        FirebirdReader $firebirdReader,
        FirebirdSync $sync,
        ?string $ciclo,
        bool $deleteOrphans,
        callable $progressCallback
    ): array {
        $log = [];
        $errors = [];
        $totals = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'processed' => 0, 'total' => 0];

        $mysql = DB::connection()->getPdo();

        $log[] = ['tipo' => 'info', 'msg' => "=== CATÁLOGOS (smart sync) ==="];
        $progressCallback(0, count(self::TABLAS_SIN_CICLO), 'Iniciando catálogos');

        foreach (self::TABLAS_SIN_CICLO as $index => $tabla) {
            $stage = "Catálogo: {$tabla}";
            $progressCallback($index + 1, count(self::TABLAS_SIN_CICLO), $stage);

            $result = $this->syncCatalogTable($firebirdReader, $mysql, $tabla, $deleteOrphans);
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];
        }

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

    protected function syncCatalogTable(
        FirebirdReader $fbReader,
        PDO $mysql,
        string $tabla,
        bool $deleteOrphans
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        try {
            $fbCols = $fbReader->getColumns($tabla);
            $myCols = array_map(fn($r) => strtolower($r['Field']), $this->getMysqlColumns($mysql, $tabla));
            $totalFb = $fbReader->countRows($tabla);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$totalFb} en FB"];

            $datos = $totalFb > 0 ? $fbReader->fetchRows($tabla, $fbCols) : [];

            $result = $this->smartSync($mysql, $tabla, $fbCols, $myCols, $datos, $deleteOrphans);
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

    protected function smartSync(
        PDO $mysql,
        string $tabla,
        array $fbCols,
        array $myCols,
        array $datosFb,
        bool $deleteOrphans
    ): array {
        $log = [];
        $errors = [];
        $created = $updated = $deleted = $processed = 0;

        if (empty($datosFb)) {
            $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros en FB"];
            return compact('log', 'errors', 'created', 'updated', 'deleted', 'processed');
        }

        // Mapear columnas FB → MySQL
        $fbMap = [];
        foreach ($fbCols as $fc) {
            $lc = strtolower($fc);
            if (in_array($lc, $myCols)) $fbMap[$lc] = $fc;
        }
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

        // PK MySQL
        $pkCols = $this->getMysqlPk($mysql, $tabla);
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
                if ($changed) $toUpdate[] = $row;
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

    protected function safeVal($v): ?string
    {
        if ($v === null) return null;
        if (is_string($v) && $v === '') return null;
        return (string)$v;
    }
}