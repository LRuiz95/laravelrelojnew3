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

        // FirebirdSync para la cola unificada (Centro de Operaciones)
        $firebirdSyncs = \App\Models\FirebirdSync::latest()->limit(20)->get();

        return view('operations.queue', compact('syncs', 'firebirdSyncs'));
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
                'type' => 'device',
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

    public function queueDataUnified(Request $request): JsonResponse
    {
        $typeFilter = $request->query('type');
        $statusFilter = $request->query('status');
        $operationFilter = $request->query('operation');

        $firebird = \App\Models\FirebirdSync::latest()->limit(50)->get()->map(fn (\App\Models\FirebirdSync $s): array => [
            'id' => $s->id,
            'type' => 'firebird',
            'device' => 'Firebird: '.$s->operationLabel,
            'device_url' => route('firebird.sync', $s),
            'operation' => $s->operation,
            'operation_label' => $s->operationLabel,
            'status' => $s->status === 'pending' ? 'queued' : $s->status,
            'stage' => $s->stage ?? '-',
            'processed' => $s->processed ?? 0,
            'total' => $s->total ?? 0,
            'created' => $s->created_count ?? 0,
            'updated' => $s->updated_count ?? 0,
            'error' => $s->error_message,
            'retry_url' => $s->status === 'failed' ? route('firebird.retry', $s) : null,
            'cancel_url' => in_array($s->status, ['pending','running'], true) ? route('firebird.cancel', $s) : null,
            'delete_url' => !in_array($s->status, ['pending','running'], true) ? route('firebird.delete', $s) : null,
            'items' => $s->items()->limit(5)->get()->map(fn ($i): array => [
                'label' => $i->table_name ?? ('#'.$i->id),
                'status' => $i->status,
                'message' => $i->error_message ?? '',
            ])->values()->toArray(),
            'created_at' => $s->created_at?->format('d/m/Y H:i'),
        ]);

        $device = DeviceSync::with('device')->latest()->limit(50)->get()->map(fn (DeviceSync $s): array => [
            'id' => $s->id,
            'type' => 'device',
            'device' => $s->device?->name ?? 'Dispositivo eliminado',
            'device_url' => $s->device ? route('devices.show', $s->device) : null,
            'operation' => $s->operation,
            'operation_label' => $s->operation_label,
            'status' => $s->status,
            'stage' => $s->stage,
            'processed' => $s->processed,
            'total' => $s->total,
            'created' => $s->created_count,
            'updated' => $s->updated_count,
            'error' => $s->error_message,
            'retry_url' => $s->status === 'failed' && $s->operation === 'sync_full' ? route('operations.retry', $s) : null,
            'cancel_url' => in_array($s->status, ['queued','running'], true) ? url('/sync-queue/'.$s->id.'/cancel') : null,
            'delete_url' => !in_array($s->status, ['queued','running'], true) ? url('/sync-queue/'.$s->id) : null,
            'items' => $s->items()->orderBy('id')->get()->map(fn ($i): array => [
                'label' => $i->label(),
                'status' => $i->status,
                'message' => $i->message,
            ])->values()->toArray(),
            'created_at' => $s->created_at?->format('d/m/Y H:i'),
        ]);

        $merged = $firebird->concat($device)->sortByDesc('created_at')->values();

        if ($typeFilter) {
            $merged = $merged->where('type', $typeFilter)->values();
        }
        if ($statusFilter) {
            // pending en firebird se normaliza a queued
            $merged = $merged->where('status', $statusFilter === 'pending' ? 'queued' : $statusFilter)->values();
        }
        if ($operationFilter) {
            $merged = $merged->where('operation', $operationFilter)->values();
        }

        return response()->json(['syncs' => $merged->take(50)->values()]);
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
