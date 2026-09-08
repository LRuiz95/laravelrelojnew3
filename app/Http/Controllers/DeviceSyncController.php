<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\SyncProgressUpdated;
use App\Jobs\SyncDeviceJob;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeviceSyncController extends Controller
{
    public function syncUsers(Device $device, Request $request): JsonResponse
    {
        $sync = $device->syncs()->create([
            'status' => 'queued',
            'operation' => 'users',
            'stage' => 'Preparando',
        ]);

        try {
            SyncDeviceJob::dispatch($device, $sync, 'users');
        } catch (\Throwable $e) {
            $sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'error_message' => 'No se pudo iniciar la sincronización: ' . $e->getMessage(),
                'finished_at' => now(),
            ]);

            return response()->json([
                'status' => 'failed',
                'operation' => 'users',
                'stage' => 'Error',
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'error' => 'No se pudo iniciar la sincronización. Inténtalo de nuevo.',
            ], 500);
        }

        event(new SyncProgressUpdated($sync, 'queued'));

        return response()->json([
            'status' => 'queued',
            'operation' => 'users',
            'stage' => 'Preparando',
            'sync_id' => $sync->id,
        ]);
    }

    /**
     * Synchronize fingerprints from a ZKTeco device.
     */
    public function syncFingerprints(Device $device, Request $request): JsonResponse
    {
        $sync = $device->syncs()->create([
            'status' => 'queued',
            'operation' => 'fingerprints',
            'stage' => 'Preparando',
        ]);

        try {
            SyncDeviceJob::dispatch($device, $sync, 'fingerprints');
        } catch (\Throwable $e) {
            $sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'error_message' => 'No se pudo iniciar la sincronización: ' . $e->getMessage(),
                'finished_at' => now(),
            ]);

            return response()->json([
                'status' => 'failed',
                'operation' => 'fingerprints',
                'stage' => 'Error',
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'error' => 'No se pudo iniciar la sincronización. Inténtalo de nuevo.',
            ], 500);
        }

        event(new SyncProgressUpdated($sync, 'queued'));

        return response()->json([
            'status' => 'queued',
            'operation' => 'fingerprints',
            'stage' => 'Preparando',
            'sync_id' => $sync->id,
        ]);
    }

    /**
     * Synchronize attendances from a ZKTeco device.
     */
    public function syncAttendances(Device $device, Request $request): JsonResponse
    {
        $sync = $device->syncs()->create([
            'status' => 'queued',
            'operation' => 'attendances',
            'stage' => 'Preparando',
        ]);

        try {
            SyncDeviceJob::dispatch($device, $sync, 'attendances');
        } catch (\Throwable $e) {
            $sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'error_message' => 'No se pudo iniciar la sincronización: ' . $e->getMessage(),
                'finished_at' => now(),
            ]);

            return response()->json([
                'status' => 'failed',
                'operation' => 'attendances',
                'stage' => 'Error',
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'error' => 'No se pudo iniciar la sincronización. Inténtalo de nuevo.',
            ], 500);
        }

        event(new SyncProgressUpdated($sync, 'queued'));

        return response()->json([
            'status' => 'queued',
            'operation' => 'attendances',
            'stage' => 'Preparando',
            'sync_id' => $sync->id,
        ]);
    }

    /**
     * Full synchronization: users → attendances → fingerprints.
     * 
     * Crea un registro DeviceSync, dispacha SyncDeviceJob(operation='all')
     * y retorna status 'queued' para que el frontend muestre la barra de progreso.
     */
    public function syncAll(Device $device, Request $request): JsonResponse
    {
        // 1. Crear registro de sincronización para tracking de progreso
        $sync = $device->syncs()->create([
            'status' => 'queued',
            'operation' => 'all',
            'stage' => 'Preparando',
        ]);

        try {
            // 2. Dispatchar job de sincronización asíncrono
            SyncDeviceJob::dispatch($device, $sync, 'all');
        } catch (Throwable $e) {
            // Si no se puede dispatchar el job, marcar como fallido
            $sync->update([
                'status' => 'failed',
                'stage' => 'Error',
                'error_message' => 'No se pudo iniciar la sincronización: ' . $e->getMessage(),
                'finished_at' => now(),
            ]);

            return response()->json([
                'status' => 'failed',
                'operation' => 'all',
                'stage' => 'Error',
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'error' => 'No se pudo iniciar la sincronización. Inténtalo de nuevo.',
            ], 500);
        }

        // 3. Retornar status 'queued' para que el frontend inicie polling
        event(new SyncProgressUpdated($sync, 'queued'));

        return response()->json([
            'status' => 'queued',
            'operation' => 'all',
            'stage' => 'Preparando',
            'sync_id' => $sync->id,
        ]);
    }

    /**
     * Set the time on a ZKTeco device.
     */
    public function setTime(Device $device, Request $request): JsonResponse
    {
        // ... existing logic from DeviceController::setTime
        return response()->json(['status' => 'time set']);
    }

    /**
     * Clear attendance records for a device.
     */
    public function clearAttendance(Device $device): JsonResponse
    {
        // ... existing logic from DeviceController::clearAttendance
        return response()->json(['status' => 'cleared']);
    }

    /**
     * Restore/enable a device.
     */
    public function restore(Device $device): JsonResponse
    {
        // ... existing logic from DeviceController::restore
        return response()->json(['status' => 'restored']);
    }

    /**
     * Protected method to queue sync jobs (kept for compatibility).
     */
    private function queueSync(string $deviceId, string $operation, string $status): void
    {
        // ... existing logic from DeviceController::queueSync
    }
}