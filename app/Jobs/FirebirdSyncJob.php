<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FirebirdSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebirdSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hora max para ETL completo

    public function __construct(
        public FirebirdSync $sync,
        public string $operation = 'sync_all', // sync_ciclo, sync_catalogos, sync_all, sync_custom
        public ?string $ciclo = null,
        public bool $deleteOrphans = false,
        public array $tables = [], // lista de tablas FB para sync_custom
        public bool $skipExisting = true,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900]; // 1min, 5min, 15min
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('firebird-sync:' . ($this->ciclo ?? 'global')))
                ->releaseAfter(60)
                ->expireAfter($this->timeout + 120),
        ];
    }

    public function handle(): void
    {
        try {
            $this->run();
        } catch (Throwable $e) {
            $this->handleFailure($e);
        }
    }

    protected function run(): void
    {
        Log::info('FirebirdSyncJob started', [
            'sync_id' => $this->sync->id,
            'operation' => $this->operation,
            'ciclo' => $this->ciclo,
            'tables' => $this->tables,
        ]);

        $this->sync->update([
            'status' => 'running',
            'started_at' => now(),
            'stage' => 'Iniciando',
            'error_message' => null,
        ]);

        $firebirdReader = app(\App\Services\FirebirdReader::class);
        $strategy = $this->resolveStrategy();

        $result = $strategy->execute(
            $firebirdReader,
            $this->sync,
            $this->ciclo,
            $this->deleteOrphans,
            $this->tables,
            $this->skipExisting,
            function (int $processed, int $total, string $stage): void {
                $this->updateProgress($processed, $total, $stage);
            }
        );

        $this->finalizeSync($result);
    }

    protected function resolveStrategy(): \App\Services\SyncStrategies\SyncStrategyInterface
    {
        return match ($this->operation) {
            'sync_ciclo' => app(\App\Services\SyncStrategies\CycleDirectSync::class),
            'sync_catalogos' => app(\App\Services\SyncStrategies\CatalogSmartSync::class),
            'sync_all' => app(\App\Services\SyncStrategies\FullSyncStrategy::class),
            'sync_custom' => app(\App\Services\SyncStrategies\CustomSyncStrategy::class),
            default => app(\App\Services\SyncStrategies\FullSyncStrategy::class),
        };
    }

    protected function updateProgress(int $processed, int $total, string $stage): void
    {
        $safeTotal = max(1, $total, $processed);
        $safeProcessed = min($processed, $safeTotal);

        $this->sync->update([
            'stage' => $stage,
            'processed' => $safeProcessed,
            'total' => $safeTotal,
        ]);
    }

    protected function finalizeSync(array $result): void
    {
        $savedRecords = ($result['created'] ?? 0) + ($result['updated'] ?? 0) + ($result['deleted'] ?? 0);
        $hasErrors = !empty($result['errors'] ?? []);
        $status = $savedRecords > 0 && ! $hasErrors ? 'completed' : ($hasErrors ? 'failed' : 'completed');

        $this->sync->update([
            'status' => $status,
            'stage' => $status === 'completed' ? 'Terminado' : 'Error',
            'finished_at' => now(),
            'processed' => $result['processed'] ?? 0,
            'total' => $result['total'] ?? 0,
            'created_count' => $result['created'] ?? 0,
            'updated_count' => $result['updated'] ?? 0,
            'deleted_count' => $result['deleted'] ?? 0,
            'error_message' => $hasErrors ? implode('; ', $result['errors']) : null,
            'log' => $result['log'] ?? [],
        ]);
    }

    protected function handleFailure(Throwable $exception): void
    {
        if ($this->sync->fresh()?->status === 'cancelled') {
            return;
        }

        $sync = $this->sync->fresh();
        $saved = ($sync?->created_count ?? 0) + ($sync?->updated_count ?? 0) + ($sync?->deleted_count ?? 0);
        $status = $saved > 0 ? 'completed' : 'failed';

        $sync?->update([
            'status' => $status,
            'stage' => $status === 'completed' ? 'Terminado con advertencias' : 'Error',
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('FirebirdSyncJob failed', [
            'sync_id' => $this->sync->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
