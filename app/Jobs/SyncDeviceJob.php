<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SyncProgressUpdated;
use App\Exceptions\SyncCancelledException;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Services\ZktecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public Device $device,
        public DeviceSync $sync,
        public string $operation = 'all',
        public ?int $employeeId = null,
        public int $batchSize = 25,
    ) {
        $this->queue = 'device-sync';
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('device-sync:'.$this->device->id))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(): void
    {
        try {
            $this->run();
        } catch (SyncCancelledException) {
            // El estado ya fue actualizado por el usuario.
        }
    }

    protected function run(): void
    {
        Log::info('SyncDeviceJob started', [
            'device' => $this->device->id,
            'operation' => $this->operation,
            'sync_id' => $this->sync->id,
        ]);

        $this->stopIfCancelled();
        $service = new ZktecoService($this->device);
        $this->sync->update([
            'status' => 'running',
            'started_at' => now(),
            'stage' => $this->operation === 'all' ? 'Preparando' : $this->operation,
            'error_message' => null,
        ]);

        $users = ['created' => 0, 'updated' => 0, 'total' => 0];
        $attendances = ['created' => 0, 'total' => 0];
        $fingerprints = ['created' => 0, 'updated' => 0, 'processed' => 0];
        $warnings = [];

        if ($this->operation === 'all') {
            $allData = $this->runAllSync($service, $warnings);
            $users = $allData['users'];
            $attendances = $allData['attendances'];
            $fingerprints = $allData['fingerprints'];
        } else {
            if (in_array($this->operation, ['users'], true)) {
                $users = $this->runStage('users', 'usuarios', function () use ($service): array {
                    $dbTotal = max(1, (int) $this->device->employees()->count());
                    $this->markDownloading('usuarios', $dbTotal);

                    return $service->syncUsers(function (int $processed, int $total) use ($dbTotal): void {
                        $mappedProcessed = $dbTotal > 0 ? min($dbTotal, (int) round(($processed / max(1, $total)) * $dbTotal)) : $processed;
                        $this->updateProgress($mappedProcessed, $dbTotal);
                    });
                }, $warnings);
            }

            if (in_array($this->operation, ['attendances'], true)) {
                $attendances = $this->runStage('attendances', 'asistencias', function () use ($service): array {
                    $dbTotal = max(1, (int) $this->device->attendances()->count());
                    $this->markDownloading('asistencias', $dbTotal);

                    return $service->syncAttendances(function (int $processed, int $total) use ($dbTotal): void {
                        $mappedProcessed = $dbTotal > 0 ? min($dbTotal, (int) round(($processed / max(1, $total)) * $dbTotal)) : $processed;
                        $this->updateProgress($mappedProcessed, $dbTotal);
                    });
                }, $warnings);
            }

            if (in_array($this->operation, ['fingerprints'], true)) {
                $fingerprints = $this->runStage('fingerprints', 'huellas', function () use ($service): array {
                    return $service->syncFingerprints($this->employeeId, $this->batchSize, function (int $processed, int $total): void {
                        $this->updateProgress($processed, $total);
                    });
                }, $warnings);
            }
        }

        $this->finalizeSync($users, $attendances, $fingerprints, $warnings);
    }

    protected function runAllSync(ZktecoService $service, array &$warnings): array
    {
        $this->stopIfCancelled();
        $this->setStage('Preparando');

        $users = $this->runStage('all', 'usuarios', function () use ($service): array {
            $dbTotal = max(1, (int) $this->device->employees()->count());
            $this->markDownloading('usuarios', $dbTotal);

            return $service->syncUsers(function (int $processed, int $total) use ($dbTotal): void {
                $mappedProcessed = $dbTotal > 0 ? min($dbTotal, (int) round(($processed / max(1, $total)) * $dbTotal)) : $processed;
                $this->updateProgress($mappedProcessed, $dbTotal);
            });
        }, $warnings);

        $attendances = $this->runStage('all', 'asistencias', function () use ($service): array {
            $dbTotal = max(1, (int) $this->device->attendances()->count());
            $this->markDownloading('asistencias', $dbTotal);

            return $service->syncAttendances(function (int $processed, int $total) use ($dbTotal): void {
                $mappedProcessed = $dbTotal > 0 ? min($dbTotal, (int) round(($processed / max(1, $total)) * $dbTotal)) : $processed;
                $this->updateProgress($mappedProcessed, $dbTotal);
            });
        }, $warnings);

        $fingerprints = $this->runStage('all', 'huellas', function () use ($service): array {
            $this->setStage('huellas');

            return $service->syncFingerprints($this->employeeId, $this->batchSize, function (int $processed, int $total): void {
                $this->updateProgress($processed, $total);
            });
        }, $warnings);

        return [
            'users' => $users,
            'attendances' => $attendances,
            'fingerprints' => $fingerprints,
        ];
    }

    protected function runStage(string $operation, string $stageName, callable $callback, array &$warnings): array
    {
        $this->stopIfCancelled();
        $this->setStage($stageName);

        try {
            return $callback();
        } catch (Throwable $e) {
            Log::warning('Stage failed: '.$stageName.' | operation: '.$operation.' | device: '.$this->device->id.' | error: '.$e->getMessage(), [
                'device' => $this->device->ip,
                'stage' => $stageName,
                'operation' => $operation,
            ]);
            $warnings[] = $stageName;

            return match ($stageName) {
                'usuarios' => ['created' => 0, 'updated' => 0, 'total' => 0],
                'asistencias' => ['created' => 0, 'total' => 0],
                'huellas' => ['created' => 0, 'updated' => 0, 'processed' => 0],
                default => [],
            };
        }
    }

    protected function finalizeSync(array $users, array $attendances, array $fingerprints, array $warnings): void
    {
        $savedRecords = ((int) ($users['created'] ?? 0)) + ((int) ($users['updated'] ?? 0)) + ((int) ($attendances['created'] ?? 0)) + ((int) ($fingerprints['created'] ?? 0)) + ((int) ($fingerprints['updated'] ?? 0));
        $operationFailed = ! empty($warnings) && $savedRecords === 0;
        $status = $savedRecords > 0 || empty($warnings) ? 'completed' : 'failed';

        if ($this->operation === 'fingerprints' && empty($warnings) && ((int) ($fingerprints['created'] ?? 0) + (int) ($fingerprints['updated'] ?? 0)) === 0) {
            $status = 'completed';
        }

        if ($operationFailed) {
            $status = 'failed';
        }

        $message = null;
        if ($status === 'failed') {
            $message = 'No se pudo completar la sincronización. Revisa que el checador esté encendido y en la red.';
        } elseif (! empty($warnings)) {
            $message = 'Sincronización completada con advertencias.';
        }

        $this->sync->update([
            'status' => $status,
            'stage' => $status === 'completed' ? 'Terminado' : 'Error',
            'finished_at' => now(),
            'processed' => max(0, ($users['total'] ?? 0) + ($attendances['total'] ?? 0) + ($fingerprints['processed'] ?? 0)),
            'created_count' => max(0, ($users['created'] ?? 0) + ($attendances['created'] ?? 0) + ($fingerprints['created'] ?? 0)),
            'updated_count' => max(0, ($users['updated'] ?? 0) + ($fingerprints['updated'] ?? 0)),
            'error_message' => $message,
        ]);

        event(new SyncProgressUpdated($this->sync, $status));
    }

    public function failed(Throwable $exception): void
    {
        if ($this->sync->fresh()?->status === 'cancelled') {
            return;
        }

        $sync = $this->sync->fresh();
        $saved = ((int) ($sync?->created_count ?? 0)) + ((int) ($sync?->updated_count ?? 0));
        $status = $saved > 0 ? 'completed' : 'failed';

        $sync?->update([
            'status' => $status,
            'stage' => $status === 'completed' ? 'Terminado' : 'Error',
            'finished_at' => now(),
            'error_message' => $status === 'completed' ? 'Sincronización completada con advertencias.' : 'No se pudo completar la sincronización. Revisa que el checador esté encendido y en la red.',
        ]);

        if ($sync && $status === 'completed') {
            event(new SyncProgressUpdated($sync, 'completed'));
        }
    }

    protected function setStage(string $stage): void
    {
        $this->sync->update(['stage' => $stage, 'processed' => 0]);
    }

    /**
     * Marca el inicio de la fase de descarga desde el dispositivo. El protocolo
     * ZKTeco no reporta avance parcial mientras se traen los registros (getUsers()/
     * getAttendances() devuelven el lote completo de una sola vez), así que sin esto
     * la barra de progreso queda congelada en 0% durante toda la descarga, que suele
     * ser la parte más lenta de la sincronización.
     *
     * Fijamos $total con el conteo actual en BD como estimado, para que la UI
     * muestre "0 de N" en vez de "Calculando registros…" mientras se descarga.
     */
    protected function markDownloading(string $stage, int $estimatedTotal): void
    {
        $this->sync->update([
            'stage' => $stage,
            'processed' => 0,
            'total' => max(1, $estimatedTotal),
        ]);

        event(new SyncProgressUpdated($this->sync, 'running'));
    }

    protected function updateProgress(int $processed, int $total): void
    {
        $this->stopIfCancelled();

        $safeTotal = max(1, $total, $processed);
        $safeProcessed = min($processed, $safeTotal);

        $this->sync->update([
            'processed' => $safeProcessed,
            'total' => $safeTotal,
        ]);

        event(new SyncProgressUpdated($this->sync, 'running'));
    }

    protected function stopIfCancelled(): void
    {
        if ($this->sync->fresh()?->status === 'cancelled') {
            throw new SyncCancelledException('Sincronización cancelada.');
        }
    }
}
