<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncDeviceJob;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Models\Employee;
use App\Models\Fingerprint;
use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devices = Device::withCount(['employees', 'attendances'])->orderBy('name')->paginate(10);

        // Serie semanal [d-6 … hoy]. El conteo se hace en PHP sobre los timestamps
        // de la ventana porque DATE_SUB/CURDATE() es exclusivo de MySQL y rompía
        // los tests que corren sobre SQLite.
        $oldest = now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s');
        $weeklyTables = ['devices' => 'created_at', 'employees' => 'created_at', 'attendances' => 'recorded_at'];
        $spark = [];
        foreach ($weeklyTables as $table => $column) {
            $spark[$table] = self::weeklyBuckets(
                DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '>=', $oldest)
                    ->pluck($column)
            );
        }

        return view('devices.index', [
            'devices' => $devices,
            'stats' => [
                'devices' => Device::count(),
                'online' => Device::where('status', 'online')->count(),
                'employees' => Employee::count(),
                'attendances' => Attendance::count(),
                'fingerprints' => Fingerprint::count(),
            ],
            'spark' => $spark,
        ]);
    }

    public function create(): View
    {
        return view('devices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'ip' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'password' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:50', 'unique:devices,serial_number'],
        ]);

        $candidate = new Device($data);
        $info = (new ZktecoService($candidate))->info();

        $serial = $info['serial'] ?? null;
        $device = $serial
            ? Device::where('serial_number', $serial)->first()
            : null;

        if ($device) {
            $device->update(array_merge($data, [
                'serial_number' => $serial,
                'device_name' => $info['device_name'] ?? $device->device_name,
            ]));
        } else {
            $device = Device::create(array_merge($data, [
                'serial_number' => $serial,
                'device_name' => $info['device_name'] ?? null,
            ]));
        }

        $service = new ZktecoService($device);
        $service->deviceStatus();

        return Redirect::route('devices.index')
            ->with('success', $device->wasRecentlyCreated
                ? "Dispositivo {$device->name} registrado."
                : "Dispositivo {$device->name} actualizado; ya estaba registrado.");
    }

    public function show(Device $device): View
    {
        $device->loadCount(['employees', 'attendances', 'fingerprints']);
        $device->load('latestSync');
        // La vista trabaja con la BD; consultar el checador aquí bloqueaba el
        // render hasta 60 segundos cuando el equipo estaba apagado. La red se
        // usa únicamente en acciones explícitas o jobs de sincronización.
        $info = [
            'device_name' => $device->device_name,
            'serial' => $device->serial_number,
            'vendor' => 'Datos locales',
            'version' => 'Verificar bajo demanda',
            'time' => 'No consultada',
        ];

        return view('devices.show', [
            'device' => $device,
            'info' => $info,
            'employees' => $device->employees()->orderBy('name')->get(),
            'attendances' => $device->attendances()->with('employee')->latest('recorded_at')->limit(15)->get(),
            'recentSyncs' => $device->syncs()->latest()->limit(4)->get(),
            'fingerprints' => Fingerprint::query()
                ->where('device_id', $device->id)
                ->with('employee:id,user_id,name')
                ->latest()
                ->limit(50)
                ->get(),
            'spark' => $this->deviceSpark($device),
        ]);
    }

    /**
     * Serie semanal [d-6 … hoy] de altas por tabla, acotada al dispositivo.
     * Misma semántica y bucketing portable que la usada en index().
     */
    private function deviceSpark(Device $device): array
    {
        return [
            // Altas de enrolamientos: la fecha relevante es la de la pivote.
            'employees' => self::weeklyBuckets(
                DB::table('device_employee')->where('device_id', $device->id)->pluck('created_at')
            ),
            'attendances' => self::weeklyBuckets($device->attendances()->pluck('recorded_at')),
            'fingerprints' => self::weeklyBuckets(
                Fingerprint::query()->where('device_id', $device->id)->pluck('created_at')
            ),
        ];
    }

    /**
     * Convierte una colección de timestamps en 7 buckets acumulativos
     * [d-6 … hoy], replicando la semántica de los antiguos SUM(CASE …) de MySQL
     * pero sin SQL específico de un motor (comparación lexicográfica de
     * 'Y-m-d H:i:s', válida en MySQL, SQLite y PostgreSQL).
     *
     * @param  \Illuminate\Support\Collection<int, string|null>  $timestamps
     * @return list<int>
     */
    private static function weeklyBuckets(\Illuminate\Support\Collection $timestamps): array
    {
        $thresholds = [];
        for ($i = 6; $i >= 0; $i--) {
            $thresholds[] = now()->subDays($i)->startOfDay()->format('Y-m-d H:i:s');
        }

        return array_map(
            fn (string $since): int => $timestamps->filter(
                fn (?string $ts): bool => $ts !== null && $ts >= $since
            )->count(),
            $thresholds
        );
    }

    /**
     * Devuelve los contadores, la tabla de empleados y las asistencias recientes
     * actualizados. Se usa tras completar una sincronización para refrescar la
     * vista sin recargar la página completa.
     */
    public function refreshData(Device $device): JsonResponse
    {
        $device->loadCount(['employees', 'attendances', 'fingerprints']);

        $employees = $device->employees()->orderBy('name')->get();
        $attendances = $device->attendances()->with('employee')->latest('recorded_at')->limit(15)->get();

        return response()->json([
            'counts' => [
                'employees' => $device->employees_count,
                'attendances' => $device->attendances_count,
                'fingerprints' => $device->fingerprints_count,
                'status' => $device->status,
                'status_label' => Device::states()[$device->status] ?? $device->status,
            ],
            // Las claves del payload se conservan idénticas (uid/card_no/role…)
            // para no romper el JS de la vista; los valores ahora provienen de
            // la pivote del enrolamiento con este dispositivo.
            'employees' => $employees->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'user_id' => $employee->user_id,
                'uid' => $employee->pivot->device_uid,
                'name' => $employee->name,
                'role' => $employee->pivot->role,
                'role_label' => $employee->pivot->roleLabel(),
                'card_no' => $employee->pivot->card_number,
                'fingerprints_count' => $employee->pivot->fingerprint_count,
                'edit_url' => route('employees.edit', $employee),
                'upload_url' => route('devices.employees.upload-fingerprints', [$device, $employee]),
                'destroy_url' => route('devices.employees.remove', [$device, $employee]),
                'sync_fingerprint_url' => route('devices.sync-fingerprints', $device),
            ])->values(),
            'attendances' => $attendances->map(fn (Attendance $attendance): array => [
                'recorded_at' => $attendance->recorded_at->format('d/m/Y H:i:s'),
                'employee_name' => $attendance->employee?->name ?? 'Sin asignar',
                'user_id' => $attendance->user_id,
                'state' => $attendance->state,
                'state_label' => $attendance->shortStateLabel(),
                'state_color' => $attendance->stateColorClass(),
            ])->values(),
            'recent_syncs' => $device->syncs()->latest()->limit(4)->get()->map(fn (DeviceSync $sync): array => [
                'operation_label' => $sync->operation_label,
                'status' => $sync->status,
                'stage' => $sync->stage,
                'created' => $sync->created_count,
                'updated' => $sync->updated_count,
                'error' => $sync->error_message,
                'finished_at' => $sync->finished_at?->format('d/m/Y H:i'),
            ])->values(),
            'fingerprints' => Fingerprint::query()
                ->where('device_id', $device->id)
                ->with('employee:id,user_id,name')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Fingerprint $fingerprint): array => [
                    'user_id' => $fingerprint->employee?->user_id,
                    'employee_name' => $fingerprint->employee?->name ?? 'Sin asignar',
                    'finger' => $fingerprint->finger,
                    'registered_at' => $fingerprint->created_at?->format('d/m/Y H:i'),
                ])->values(),
        ]);
    }

    public function progress(Device $device): JsonResponse
    {
        $sync = $device->syncs()->latest()->first();

        return response()->json([
            'status' => $sync?->status ?? 'never',
            'operation' => $sync?->operation,
            'stage' => $sync?->stage,
            'processed' => $sync?->processed ?? 0,
            'total' => $sync?->total ?? 0,
            'created' => $sync?->created_count ?? 0,
            'updated' => $sync?->updated_count ?? 0,
            'error' => $sync?->error_message,
        ]);
    }

    public function syncStatus(Device $device): JsonResponse
    {
        $sync = $device->syncs()->latest()->first();

        return response()->json([
            'status' => $sync?->status ?? 'never',
            'operation' => $sync?->operation,
            'stage' => $sync?->stage,
            'processed' => $sync?->processed ?? 0,
            'total' => $sync?->total ?? 0,
            'created' => $sync?->created_count ?? 0,
            'updated' => $sync?->updated_count ?? 0,
            'finished_at' => $sync?->finished_at?->toIso8601String(),
            'error' => $sync?->error_message,
        ]);
    }

    public function edit(Device $device): View
    {
        return view('devices.edit', ['device' => $device]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'ip' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'password' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:50', 'unique:devices,serial_number,'.$device->id],
        ]);

        $device->update($data);

        $service = new ZktecoService($device);
        $service->deviceStatus();

        return Redirect::route('devices.show', $device)
            ->with('success', 'Dispositivo actualizado.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return Redirect::route('devices.index')
            ->with('success', "Dispositivo {$device->name} eliminado.");
    }

    public function deduplicate(): RedirectResponse
    {
        [$employeesDeleted, $attendancesDeleted] = DB::transaction(function (): array {
            $employeesDeleted = 0;
            $attendancesDeleted = 0;

            Employee::query()
                ->select(['user_id'])
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->each(function (Employee $group) use (&$employeesDeleted): void {
                    $employees = Employee::where('user_id', $group->user_id)
                        ->orderBy('id')
                        ->get();
                    $keeper = $employees->shift();

                    foreach ($employees as $duplicate) {
                        Attendance::where('employee_id', $duplicate->id)
                            ->update(['employee_id' => $keeper->id]);

                        Fingerprint::where('employee_id', $duplicate->id)
                            ->get()
                            ->each(function (Fingerprint $fingerprint) use ($keeper): void {
                                $alreadyExists = Fingerprint::where('employee_id', $keeper->id)
                                    ->where('device_id', $fingerprint->device_id)
                                    ->where('finger', $fingerprint->finger)
                                    ->exists();

                                if ($alreadyExists) {
                                    $fingerprint->delete();
                                } else {
                                    $fingerprint->update(['employee_id' => $keeper->id]);
                                }
                            });

                        $duplicate->delete();
                        $employeesDeleted++;
                    }
                });

            Attendance::query()
                ->select(['device_id', 'user_id', 'recorded_at', 'state'])
                ->groupBy(['device_id', 'user_id', 'recorded_at', 'state'])
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->each(function (Attendance $group) use (&$attendancesDeleted): void {
                    $duplicates = Attendance::where('device_id', $group->device_id)
                        ->where('user_id', $group->user_id)
                        ->where('recorded_at', $group->recorded_at)
                        ->where('state', $group->state)
                        ->orderBy('id')
                        ->skip(1)
                        ->pluck('id');

                    $attendancesDeleted += Attendance::whereIn('id', $duplicates)->delete();
                });

            return [$employeesDeleted, $attendancesDeleted];
        });

        return Redirect::route('devices.index')->with(
            'success',
            "Limpieza completada: {$employeesDeleted} empleados y {$attendancesDeleted} asistencias duplicadas eliminados."
        );
    }

    public function checkStatus(Device $device): JsonResponse|RedirectResponse
    {
        $service = new ZktecoService($device);
        $status = $service->deviceStatus();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => Device::states()[$status],
                'message' => "{$device->name}: ".Device::states()[$status],
            ]);
        }

        return Redirect::route('devices.index')
            ->with('success', "{$device->name}: ".Device::states()[$status]);
    }

    public function syncUsers(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $this->queueSync($device, 'users');

        if (! $request->expectsJson()) {
            return Redirect::back()->with('success', 'Sincronización de usuarios enviada a la cola.');
        }

        return response()->json([
            'status' => 'queued',
            'message' => 'Sincronización de usuarios enviada a la cola.',
        ]);
    }

    public function syncAttendances(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $this->queueSync($device, 'attendances');

        if (! $request->expectsJson()) {
            return Redirect::back()->with('success', 'Sincronización de asistencias enviada a la cola.');
        }

        return response()->json([
            'status' => 'queued',
            'message' => 'Sincronización de asistencias enviada a la cola.',
        ]);
    }

    public function syncFingerprints(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $data = request()->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'batch' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $employeeId = isset($data['employee_id']) ? (int) $data['employee_id'] : null;
        if ($employeeId && ! $device->employees()->whereKey((int) $employeeId)->exists()) {
            abort(422, 'El empleado no está enrolado en este checador.');
        }
        $this->queueSync($device, 'fingerprints', $employeeId ?: null, (int) ($data['batch'] ?? 25));

        if (! $request->expectsJson()) {
            return Redirect::back()->with('success', 'Extracción de huellas enviada a la cola.');
        }

        return response()->json([
            'status' => 'queued',
            'message' => 'Extracción de huellas enviada a la cola.',
        ]);
    }

    public function syncAll(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $this->queueSync($device, 'all');

        if (! $request->expectsJson()) {
            return Redirect::back()->with('success', 'Sincronización enviada a la cola.');
        }

        return response()->json([
            'status' => 'queued',
            'message' => 'Sincronización enviada a la cola. Puedes continuar trabajando mientras se procesa.',
        ]);
    }

    protected function queueSync(Device $device, string $operation, ?int $employeeId = null, int $batchSize = 25): DeviceSync
    {
        $activeSync = $device->syncs()
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();

        if ($activeSync) {
            return $activeSync;
        }

        // Calculate total based on what the sync job will actually process.
        // We use the device's employee count as a baseline, but the Job's run() method
        // will recalculate 'processed' based on actual created/updated records,
        // avoiding the mismatch between DeviceSync.total and actual sync progress.
        // This prevents the "7778 de 288" mismatch error.
        $total = match ($operation) {
            'fingerprints' => $employeeId ? 1 : max(1, (int) $device->employees()->count()),
            'all' => max(1, (int) $device->employees()->count() + $device->attendances()->count()),
            'users' => max(1, (int) $device->employees()->count()),
            'attendances' => max(1, (int) $device->attendances()->count()),
            default => 0,
        };

        // Use a more robust total calculation: the greater of BD count or 1.
        // The real total will be validated in the Job's run() method where
        // 'processed' is calculated from actual created/updated counts,
        // not from the Service's internal totals.
        if ($total < 1) {
            $total = 1;
        }

        $sync = DeviceSync::create([
            'device_id' => $device->id,
            'status' => 'queued',
            'operation' => $operation,
            'stage' => $operation === 'all' ? 'Preparando' : $operation,
            'employee_id' => $employeeId,
            'total' => $total,
        ]);
        SyncDeviceJob::dispatch($device, $sync, $operation, $employeeId, $batchSize);

        return $sync;
    }

    public function setTime(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate(['datetime' => ['required', 'date']]);

        $service = new ZktecoService($device);
        $ok = $service->setTime(date('Y-m-d H:i:s', strtotime($data['datetime'])));

        return Redirect::route('devices.show', $device)
            ->with($ok ? 'success' : 'error', $ok ? 'Hora sincronizada.' : 'No se pudo actualizar la hora del dispositivo.');
    }

    public function syncNow(Device $device): JsonResponse|RedirectResponse
    {
        $service = new ZktecoService($device);

        if ($ok = $service->setTime(now()->format('Y-m-d H:i:s'))) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'completed',
                    'message' => 'La hora del equipo fue sincronizada con la del servidor.',
                ]);
            }

            return Redirect::route('devices.show', $device)
                ->with('success', 'La hora del equipo fue sincronizada con la del servidor.');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo sincronizar la hora del dispositivo.',
            ]);
        }

        return Redirect::route('devices.show', $device)
            ->with('error', 'No se pudo sincronizar la hora del equipo.');
    }

    public function clearAttendance(Device $device): RedirectResponse
    {
        $service = new ZktecoService($device);
        $ok = $service->clearAttendance();

        return Redirect::route('devices.show', $device)
            ->with($ok ? 'success' : 'error', $ok ? 'Bitácora de asistencias del equipo limpiada.' : 'No se pudo limpiar la bitácora.');
    }

    public function restore(Device $device): RedirectResponse
    {
        $service = new ZktecoService($device);
        $ok = $service->restoreDevice();

        return Redirect::route('devices.show', $device)
            ->with($ok ? 'success' : 'error', $ok ? 'Dispositivo habilitado.' : 'No se pudo habilitar el dispositivo.');
    }
}
