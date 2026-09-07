<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Device;
use App\Models\Employee;
use App\Models\DeviceSync;
use App\Services\ZktecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeprovisionEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Employee $employee,
        public Device $device,
        public ?DeviceSync $sync = null,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('deprovision-employee:' . $this->employee->id . ':' . $this->device->id))
                ->releaseAfter(60)
                ->expireAfter($this->timeout + 60),
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
        Log::info('DeprovisionEmployeeJob started', [
            'employee_id' => $this->employee->id,
            'device_id' => $this->device->id,
            'sync_id' => $this->sync?->id,
        ]);

        if ($this->sync) {
            $this->sync->update([
                'status' => 'running',
                'started_at' => now(),
                'stage' => 'Eliminando del checador',
                'error_message' => null,
            ]);
        }

        $service = new ZktecoService($this->device);
        $uid = (int) $this->employee->devices()->where('device_id', $this->device->id)->first()?->pivot?->device_uid;

        if (! $uid) {
            throw new \RuntimeException('No se encontró device_uid para el enrolamiento');
        }

        $service->removeUser($uid);

        // Detach from pivot (will cascade if no other pivot rows)
        $this->employee->devices()->detach($this->device->id);

        if ($this->sync) {
            $this->sync->update([
                'status' => 'completed',
                'stage' => 'Terminado',
                'finished_at' => now(),
                'processed' => 1,
                'total' => 1,
            ]);
        }

        // Check if employee has any remaining enrolments
        $remaining = $this->employee->fresh()->devices()->count();
        if ($remaining === 0 && $this->employee->status_actual === 'B') {
            // All devices deprovisioned and employee is marked for deletion
            // The catalog entry will be deleted by a separate cleanup job or manually
            Log::info('Employee deprovisioned from all devices, ready for catalog deletion', [
                'employee_id' => $this->employee->id,
            ]);
        }

        Log::info('DeprovisionEmployeeJob completed', [
            'employee_id' => $this->employee->id,
            'device_id' => $this->device->id,
        ]);
    }

    protected function handleFailure(Throwable $exception): void
    {
        if ($this->sync) {
            $this->sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        Log::error('DeprovisionEmployeeJob failed', [
            'employee_id' => $this->employee->id,
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}