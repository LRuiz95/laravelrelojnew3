<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Device;
use App\Services\ZktecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyDeviceConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Device $device,
    ) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('verify-device-connection:' . $this->device->id))
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
        Log::info('VerifyDeviceConnectionJob started', [
            'device_id' => $this->device->id,
            'device_ip' => $this->device->ip,
        ]);

        $this->device->update([
            'status' => 'checking',
        ]);

        $service = new ZktecoService($this->device);

        try {
            $info = $service->info();
            $status = $service->deviceStatus();

            $serial = $info['serial'] ?? null;

            $updateData = [
                'status' => $status,
                'device_name' => $info['device_name'] ?? $this->device->device_name,
            ];

            if ($serial && $serial !== $this->device->serial_number) {
                // Check if serial already exists on another device
                $existing = Device::where('serial_number', $serial)
                    ->where('id', '!=', $this->device->id)
                    ->first();

                if (! $existing) {
                    $updateData['serial_number'] = $serial;
                } else {
                    Log::warning('VerifyDeviceConnectionJob: serial_number already exists on another device', [
                        'device_id' => $this->device->id,
                        'serial' => $serial,
                        'existing_device_id' => $existing->id,
                    ]);
                }
            }

            $this->device->update($updateData);

            Log::info('VerifyDeviceConnectionJob completed', [
                'device_id' => $this->device->id,
                'status' => $status,
                'serial' => $serial,
            ]);
        } catch (Throwable $e) {
            $this->device->update(['status' => 'offline']);
            throw $e;
        }
    }

    protected function handleFailure(Throwable $exception): void
    {
        if ($this->device->fresh()?->status === 'checking') {
            $this->device->update([
                'status' => 'offline',
                'device_name' => null,
            ]);
        }

        Log::error('VerifyDeviceConnectionJob failed', [
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}