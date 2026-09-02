<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SyncProgressUpdated;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Models\DeviceSyncItem;
use App\Models\Employee;
use App\Services\EmployeeDeviceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncEmployeeToDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public Employee $employee,
        public Device $device,
        public DeviceSync $sync,
    ) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('employee-device-sync:'.$this->employee->id.':'.$this->device->id))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(EmployeeDeviceSyncService $service): void
    {
        if ($this->sync->fresh()?->status === 'cancelled') {
            return;
        }

        $this->sync->update([
            'status' => 'running',
            'stage' => 'Preparando',
            'started_at' => now(),
            'total' => max(1, $this->sync->total),
            'error_message' => null,
        ]);
        event(new SyncProgressUpdated($this->sync, 'running'));

        try {
            $package = $service->packageFor($this->employee, $this->device);
            $this->prepareItems($package->fingerprints);
            $this->sync->update(['stage' => 'Enviando empleado', 'total' => max(1, $this->sync->items()->count())]);
            event(new SyncProgressUpdated($this->sync->fresh(), 'running'));

            $result = $service->synchronize($package, $this->device);
            $this->markBasicItems($result['user']);
            $this->markFingerprintItems($result['fingerprints'], $result['total_fingerprints']);
            $processed = $this->sync->items()->where('status', 'completed')->count();
            $total = max(1, $this->sync->items()->count());
            $completed = $result['user'] && $result['fingerprints'] === $result['total_fingerprints'];

            $this->sync->update([
                'status' => $completed ? 'completed' : 'failed',
                'stage' => $completed ? 'Terminado' : 'Error',
                'processed' => $processed,
                'total' => $total,
                'created_count' => $result['user'] ? 1 : 0,
                'updated_count' => $result['fingerprints'],
                'finished_at' => now(),
                'error_message' => $completed ? null : 'El empleado o alguna de sus huellas no pudo sincronizarse.',
            ]);
            event(new SyncProgressUpdated($this->sync->fresh(), $completed ? 'completed' : 'failed'));
        } catch (Throwable $exception) {
            Log::warning('Employee-device synchronization failed.', [
                'employee_id' => $this->employee->id,
                'device_id' => $this->device->id,
                'sync_id' => $this->sync->id,
                'error' => $exception->getMessage(),
            ]);
            $this->sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'finished_at' => now(),
                'error_message' => 'No se pudo sincronizar con el dispositivo. Verifica su conexión.',
            ]);
            event(new SyncProgressUpdated($this->sync->fresh(), 'failed'));
            throw $exception;
        }
    }

    private function prepareItems($fingerprints): void
    {
        foreach (['employee', 'pin', 'card'] as $type) {
            $this->sync->items()->updateOrCreate(
                ['credential_type' => $type, 'finger' => null],
                ['status' => 'pending', 'message' => null]
            );
        }

        foreach ($fingerprints as $fingerprint) {
            $this->sync->items()->updateOrCreate(
                ['credential_type' => 'fingerprint', 'finger' => $fingerprint->finger],
                ['fingerprint_id' => $fingerprint->id, 'status' => 'pending', 'message' => null]
            );
        }
    }

    private function markBasicItems(bool $userSaved): void
    {
        $status = $userSaved ? 'completed' : 'failed';
        $message = $userSaved ? 'Usuario enviado al dispositivo.' : 'No se pudo enviar el usuario al dispositivo.';

        $this->sync->items()->whereIn('credential_type', ['employee', 'pin', 'card'])->update([
            'status' => $status,
            'attempts' => DB::raw('attempts + 1'),
            'message' => $message,
        ]);
    }

    private function markFingerprintItems(int $uploaded, int $total): void
    {
        $success = $total > 0 && $uploaded === $total;
        $this->sync->items()->where('credential_type', 'fingerprint')->update([
            'status' => $success ? 'completed' : 'failed',
            'attempts' => DB::raw('attempts + 1'),
            'message' => $success ? 'Huella enviada al dispositivo.' : 'No se pudo enviar la huella completa.',
        ]);
    }
}
