<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncEmployeeToDeviceJob;
use App\Models\DeviceSync;
use App\View\Composers\AdminLayoutComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function queue(Request $request): View
    {
        $query = DeviceSync::with('device')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($operation = $request->query('operation')) {
            $query->where('operation', $operation);
        }

        $syncs = $query->paginate(20);

        return view('operations.queue', compact('syncs'));
    }

    public function notifications(): View
    {
        return view('operations.notifications', [
            'notifications' => (new AdminLayoutComposer)->notifications(),
        ]);
    }

    public function queueData(Request $request): JsonResponse
    {
        $query = DeviceSync::with('device')->latest();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($operation = $request->query('operation')) {
            $query->where('operation', $operation);
        }

        return response()->json([
            'syncs' => $query->limit(50)->get()->map(fn (DeviceSync $sync): array => [
                'id' => $sync->id,
                'device' => $sync->device?->name ?? 'Dispositivo eliminado',
                'device_url' => $sync->device ? route('devices.show', $sync->device) : null,
                'operation' => $sync->operation,
                'operation_label' => $sync->operation_label,
                'status' => $sync->status,
                'stage' => $sync->stage,
                'processed' => $sync->processed,
                'total' => $sync->total,
                'created' => $sync->created_count,
                'updated' => $sync->updated_count,
                'error' => $sync->error_message,
                'retry_url' => $sync->status === 'failed' && $sync->operation === 'sync_full' ? route('operations.retry', $sync) : null,
                'items' => $sync->items()->orderBy('id')->get()->map(fn ($item): array => [
                    'label' => $item->label(),
                    'status' => $item->status,
                    'message' => $item->message,
                ])->values(),
                'created_at' => $sync->created_at?->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function cancel(Request $request, DeviceSync $sync): JsonResponse|RedirectResponse
    {
        if (! in_array($sync->status, ['queued', 'running'], true)) {
            return response()->json(['message' => 'Esta sincronización ya terminó.'], 422);
        }

        $sync->update([
            'status' => 'cancelled',
            'stage' => 'Cancelada por el usuario',
            'finished_at' => now(),
            'error_message' => 'La sincronización fue cancelada por el usuario.',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sincronización cancelada.']);
        }

        return redirect()->route('operations.queue')->with('success', 'Sincronización cancelada.');
    }

    public function delete(Request $request, DeviceSync $sync): JsonResponse|RedirectResponse
    {
        if (in_array($sync->status, ['queued', 'running'], true)) {
            return response()->json(['message' => 'Cancela la sincronización antes de eliminarla.'], 422);
        }

        $sync->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Registro eliminado.']);
        }

        return redirect()->route('operations.queue')->with('success', 'Registro eliminado.');
    }

    public function retry(Request $request, DeviceSync $sync): JsonResponse|RedirectResponse
    {
        if ($sync->status !== 'failed' || $sync->operation !== 'sync_full' || ! $sync->employee || ! $sync->device) {
            $message = 'Solo se pueden reintentar sincronizaciones completas fallidas con empleado y dispositivo válidos.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->route('operations.queue')->with('error', $message);
        }

        if (DeviceSync::query()
            ->where('employee_id', $sync->employee_id)
            ->where('device_id', $sync->device_id)
            ->where('operation', 'sync_full')
            ->whereIn('status', ['queued', 'running'])
            ->exists()) {
            $message = 'Este dispositivo ya tiene una sincronización pendiente.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->route('operations.queue')->with('info', $message);
        }

        $retrySync = DeviceSync::create([
            'device_id' => $sync->device_id,
            'employee_id' => $sync->employee_id,
            'status' => 'queued',
            'operation' => 'sync_full',
            'stage' => 'Preparando',
            'total' => max(1, $sync->total),
        ]);
        SyncEmployeeToDeviceJob::dispatch($sync->employee, $sync->device, $retrySync);
        $message = 'Se reintentará únicamente este dispositivo.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'sync_id' => $retrySync->id])
            : redirect()->route('operations.queue')->with('success', $message);
    }
}
