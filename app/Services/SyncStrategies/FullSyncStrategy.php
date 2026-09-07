<?php

declare(strict_types=1);

namespace App\Services\SyncStrategies;

use App\Models\FirebirdSync;
use App\Services\FirebirdReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class FullSyncStrategy implements SyncStrategyInterface
{
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

        // Si no hay ciclo, intentar obtener el último de horarios_det
        if (! $ciclo) {
            $ciclo = $this->getLatestCiclo(DB::connection()->getPdo());
            $log[] = ['tipo' => 'info', 'msg' => "Ciclo detectado automáticamente: {$ciclo}"];
        }

        if ($ciclo) {
            // Ejecutar sync_ciclo primero
            $cycleSync = new CycleDirectSync();
            $result = $cycleSync->execute($firebirdReader, $sync, $ciclo, $deleteOrphans, [], $skipExisting, $progressCallback);
            $log = array_merge($log, $result['log']);
            $errors = array_merge($errors, $result['errors']);
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['deleted'] += $result['deleted'];
            $totals['processed'] += $result['processed'];
        } else {
            $log[] = ['tipo' => 'skip', 'msg' => 'No hay ciclo disponible, saltando sync_ciclo'];
        }

        // Luego sync_catalogos
        $catalogSync = new CatalogSmartSync();
        $result = $catalogSync->execute($firebirdReader, $sync, null, $deleteOrphans, [], $skipExisting, $progressCallback);
        $log = array_merge($log, $result['log']);
        $errors = array_merge($errors, $result['errors']);
        $totals['created'] += $result['created'];
        $totals['updated'] += $result['updated'];
        $totals['deleted'] += $result['deleted'];
        $totals['processed'] += $result['processed'];

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

    protected function getLatestCiclo(PDO $mysql): ?string
    {
        try {
            $stmt = $mysql->query("SELECT INICIAL, FINAL, PERIODO FROM horarios_det ORDER BY INICIAL DESC, FINAL DESC, PERIODO DESC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return "{$row['INICIAL']}-{$row['FINAL']}-{$row['PERIODO']}";
            }
        } catch (Throwable) {
            // ignore
        }
        return null;
    }
}