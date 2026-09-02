<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ZktecoConnectionException;
use App\Jobs\SyncEmployeeToDeviceJob;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Models\Employee;
use App\Models\Fingerprint;
use App\Services\ZktecoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Employee::query()
            ->with('devices')
            ->orderByRaw('LOWER(name)')
            ->orderBy('id');

        if ($deviceId = $request->query('device_id')) {
            $query->whereHas('devices', fn ($q) => $q->where('devices.id', $deviceId));
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('user_id', $search)
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(25)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $employee->load([
            'devices',
            'fingerprints' => fn ($query) => $query->with('device:id,name')->orderBy('finger'),
            'syncs' => fn ($query) => $query->with('device:id,name')->latest()->limit(8),
        ]);
        $availableDevices = Device::query()
            ->whereNotIn('id', $employee->devices->pluck('id'))
            ->orderBy('name')
            ->get();
        $syncDevices = Device::query()->orderBy('name')->get();
        $totalDevices = $syncDevices->count();

        $availableFingerprints = Fingerprint::query()
            ->where('employee_id', '!=', $employee->id)
            ->with(['employee:id,user_id,name', 'device:id,name'])
            ->get()
            ->sortBy(fn (Fingerprint $fingerprint): string => sprintf(
                '%s|%s|%02d|%s',
                mb_strtolower($fingerprint->employee?->name ?? ''),
                mb_strtolower($fingerprint->device?->name ?? ''),
                $fingerprint->finger,
                $fingerprint->template_hash
            ))
            ->unique(fn (Fingerprint $fingerprint): string => implode('|', [
                $fingerprint->employee_id,
                $fingerprint->device_id ?? 'legacy',
                $fingerprint->finger,
                $fingerprint->template_hash,
            ]))
            ->values();

        return view('employees.edit', compact('employee', 'availableFingerprints', 'availableDevices', 'syncDevices', 'totalDevices'));
    }

    public function fingerprints(Request $request): View
    {
        $employees = Employee::query()
            ->with(['devices', 'fingerprints'])
            ->withCount('fingerprints')
            ->when($request->query('device_id'), fn ($query, $deviceId) => $query->whereHas(
                'devices',
                fn ($q) => $q->where('devices.id', $deviceId)
            ))
            ->when(trim((string) $request->query('q', '')), function ($query, $search): void {
                $search = trim((string) $search);
                $query->where(fn ($employeeQuery) => $employeeQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('user_id', $search)
                    ->orWhere('user_id', 'like', "%{$search}%"));
            })
            ->orderByRaw('LOWER(name)')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('fingerprints.index', [
            'employees' => $employees,
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    /**
     * El nombre pertenece al catálogo; rol y contraseña pertenecen a cada
     * enrolamiento, por lo que se propagan a TODOS los checadores donde la
     * persona está dada de alta.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:24'],
            'password' => ['nullable', 'digits_between:1,8'],
            'role' => ['required', 'in:0,13,14'],
        ]);

        $enrollments = $employee->devices()->get();
        $ok = true;

        foreach ($enrollments as $device) {
            $service = new ZktecoService($device);

            $ok = $service->setUser([
                'uid' => $device->pivot->device_uid,
                'user_id' => $employee->user_id,
                'name' => $data['name'],
                'password' => $data['password'] ?: ((string) $device->pivot->password ?: '1234'),
                'role' => (int) $data['role'],
                'card_number' => $device->pivot->card_number,
            ]) && $ok;
        }

        if ($employee->name !== $data['name']) {
            $employee->update(['name' => $data['name']]);
        }

        $message = match (true) {
            ! $ok => 'No se pudo actualizar el empleado en todos los checadores.',
            $enrollments->isEmpty() => 'Empleado actualizado en el catálogo (sin enrolamientos activos).',
            default => 'Empleado actualizado en el catálogo y en '.$enrollments->count().' checador(es).',
        };

        return Redirect::route('employees.index')->with($ok ? 'success' : 'error', $message);
    }

    public function uploadFingerprints(Employee $employee): RedirectResponse
    {
        // Sin dispositivo explícito se usa el primer enrolamiento; el flujo
        // normal por dispositivo vive en devices.show.
        $device = $employee->devices()->first();

        $ok = $device
            ? (new ZktecoService($device))->uploadFingerprints($employee)
            : false;

        return Redirect::route('employees.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Huellas subidas al checador.' : 'Empleado sin enrolamiento activo - no se pueden subir huellas.');
    }

    public function uploadFingerprintsOnDevice(Device $device, Employee $employee): RedirectResponse
    {
        if (! $device->employees()->whereKey($employee->id)->exists()) {
            return Redirect::back()->with('error', 'El empleado no está enrolado en este checador.');
        }

        try {
            $ok = (new ZktecoService($device))->uploadFingerprints($employee);
        } catch (\Throwable $exception) {
            return Redirect::back()->with('error', 'No se pudo conectar con '.$device->name.'.');
        }

        return Redirect::back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Huellas subidas a '.$device->name.'.' : 'No se pudieron subir las huellas a '.$device->name.'.'
        );
    }

    public function removeFromDevice(Device $device, Employee $employee): RedirectResponse
    {
        $enrollment = $device->employees()->whereKey($employee->id)->first();
        if (! $enrollment) {
            return Redirect::back()->with('error', 'El empleado no está enrolado en este checador.');
        }

        try {
            $ok = (new ZktecoService($device))->removeUser((int) $enrollment->pivot->device_uid);
        } catch (\Throwable $exception) {
            return Redirect::back()->with('error', 'No se pudo conectar con '.$device->name.'.');
        }

        return Redirect::back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Empleado retirado de '.$device->name.'.' : 'No se pudo retirar el empleado de '.$device->name.'.'
        );
    }

    public function assignFingerprint(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'fingerprint_id' => ['required', 'integer', 'exists:fingerprints,id'],
        ]);

        $fingerprint = Fingerprint::findOrFail($data['fingerprint_id']);
        if ($fingerprint->employee_id === $employee->id) {
            return Redirect::back()->with('error', 'La huella ya pertenece a este empleado.');
        }

        $duplicate = Fingerprint::query()
            ->where('employee_id', $employee->id)
            ->where('device_id', $fingerprint->device_id)
            ->where('finger', $fingerprint->finger)
            ->exists();

        if ($duplicate) {
            return Redirect::back()->with('error', 'El empleado ya tiene ese dedo registrado en el mismo dispositivo.');
        }

        DB::transaction(function () use ($employee, $fingerprint): void {
            $fingerprint->update(['employee_id' => $employee->id]);

            if ($fingerprint->device_id) {
                foreach ([$employee->id, $fingerprint->getOriginal('employee_id')] as $employeeId) {
                    DB::table('device_employee')
                        ->where('device_id', $fingerprint->device_id)
                        ->where('employee_id', $employeeId)
                        ->update([
                            'fingerprint_count' => Fingerprint::query()
                                ->where('device_id', $fingerprint->device_id)
                                ->where('employee_id', $employeeId)
                                ->count(),
                        ]);
                }
            }
        });

        return Redirect::back()->with('success', 'Huella asignada al empleado.');
    }

    public function deleteFingerprint(Employee $employee, Fingerprint $fingerprint): RedirectResponse
    {
        if ($fingerprint->employee_id !== $employee->id) {
            abort(404);
        }

        if ($fingerprint->device_id) {
            $device = Device::find($fingerprint->device_id);
            if (! $device || ! (new ZktecoService($device))->removeFingerprint($employee, $fingerprint)) {
                return Redirect::back()->with('error', 'No se pudo quitar la huella del checador.');
            }
        }

        $deviceId = $fingerprint->device_id;
        $fingerprint->delete();

        if ($deviceId) {
            DB::table('device_employee')
                ->where('device_id', $deviceId)
                ->where('employee_id', $employee->id)
                ->update([
                    'fingerprint_count' => Fingerprint::query()
                        ->where('device_id', $deviceId)
                        ->where('employee_id', $employee->id)
                        ->count(),
                ]);
        }

        return Redirect::back()->with('success', 'Huella eliminada correctamente.');
    }

    public function copyFingerprint(Request $request, Employee $employee, Fingerprint $fingerprint): RedirectResponse
    {
        if ($fingerprint->employee_id !== $employee->id) {
            abort(404);
        }

        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
        ]);

        if ((int) $fingerprint->device_id === (int) $data['device_id']) {
            return Redirect::back()->with('error', 'Selecciona un checador distinto al origen.');
        }

        $device = $employee->devices()->whereKey($data['device_id'])->first();
        if (! $device) {
            return Redirect::back()->with('error', 'El empleado no está enrolado en el checador destino.');
        }

        if (Fingerprint::query()
            ->where('employee_id', $employee->id)
            ->where('device_id', $device->id)
            ->where('finger', $fingerprint->finger)
            ->exists()) {
            return Redirect::back()->with('error', 'Ese dedo ya está registrado en el checador destino.');
        }

        try {
            $copied = (new ZktecoService($device))->uploadFingerprint($employee, $fingerprint);
        } catch (\Throwable $exception) {
            return Redirect::back()->with('error', 'No se pudo leer la huella desde el checador de origen.');
        }

        if (! $copied) {
            return Redirect::back()->with('error', 'No se pudo copiar la huella al checador destino.');
        }

        Fingerprint::create([
            'employee_id' => $employee->id,
            'device_id' => $device->id,
            'finger' => $fingerprint->finger,
            'template' => $fingerprint->template,
            'template_hash' => $fingerprint->template_hash,
        ]);

        $employee->devices()->updateExistingPivot($device->id, [
            'fingerprint_count' => Fingerprint::query()
                ->where('device_id', $device->id)
                ->where('employee_id', $employee->id)
                ->count(),
        ]);

        return Redirect::back()->with('success', 'Huella copiada a '.$device->name.'.');
    }

    public function updateCard(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'card_number' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
        ]);

        $device = $employee->devices()->whereKey($data['device_id'])->first();
        if (! $device) {
            return Redirect::back()->with('error', 'El empleado no está enrolado en ese checador.');
        }

        $cardNumber = $data['card_number'] ?: null;
        $cardTaken = DB::table('device_employee')
            ->where('device_id', $device->id)
            ->where('card_number', $cardNumber)
            ->where('employee_id', '!=', $employee->id)
            ->exists();

        if ($cardNumber !== null && $cardTaken) {
            return Redirect::back()->with('error', 'Ese código de tarjeta ya está asignado en este checador.');
        }

        $ok = (new ZktecoService($device))->setUser([
            'uid' => $device->pivot->device_uid,
            'user_id' => $employee->user_id,
            'name' => $employee->name,
            'password' => (string) ($device->pivot->password ?: '1234'),
            'role' => (int) $device->pivot->role,
            'card_number' => $cardNumber,
        ]);

        if (! $ok) {
            return Redirect::back()->with('error', 'No se pudo actualizar la tarjeta en el checador.');
        }

        $employee->devices()->updateExistingPivot($device->id, ['card_number' => $cardNumber]);

        return Redirect::back()->with('success', 'Código de tarjeta actualizado en '.$device->name.'.');
    }

    public function syncToDevices(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
        ]);

        $created = 0;
        foreach (array_unique(array_map('intval', $data['device_ids'])) as $deviceId) {
            $device = Device::findOrFail($deviceId);
            $alreadyQueued = DeviceSync::query()
                ->where('employee_id', $employee->id)
                ->where('device_id', $device->id)
                ->where('operation', 'sync_full')
                ->whereIn('status', ['queued', 'running'])
                ->exists();

            if ($alreadyQueued) {
                continue;
            }

            $fingerprintCount = $employee->fingerprints()->select('finger')->distinct()->count('finger');
            $sync = DeviceSync::create([
                'device_id' => $device->id,
                'employee_id' => $employee->id,
                'status' => 'queued',
                'operation' => 'sync_full',
                'stage' => 'Preparando',
                'total' => max(1, $fingerprintCount + 1),
            ]);
            SyncEmployeeToDeviceJob::dispatch($employee, $device, $sync);
            $created++;
        }

        return Redirect::back()->with(
            $created ? 'success' : 'info',
            $created ? "Se enviaron {$created} tarea(s) a la cola." : 'Los dispositivos seleccionados ya tienen una sincronización pendiente.'
        );
    }

    public function enrollOnDevice(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
        ]);

        $device = Device::findOrFail($data['device_id']);
        $sourceEnrollment = $employee->devices()->first();
        $cardNumber = $sourceEnrollment?->pivot->card_number;
        if ($cardNumber !== null && DB::table('device_employee')
            ->where('device_id', $device->id)
            ->where('card_number', $cardNumber)
            ->where('employee_id', '!=', $employee->id)
            ->exists()) {
            return Redirect::back()->with('error', 'La tarjeta '.$cardNumber.' ya está asignada en '.$device->name.'. Asigna otra tarjeta después de enrolar.');
        }

        try {
            $ok = (new ZktecoService($device))->enrollEmployee(
                $employee,
                $sourceEnrollment?->pivot->password,
                $cardNumber,
                (int) ($sourceEnrollment?->pivot->role ?? 0)
            );
        } catch (ZktecoConnectionException $exception) {
            return Redirect::back()->with('error', 'No se pudo conectar con '.$device->name.' ('.$exception->deviceIp.':'.$device->port.'). Verifica que esté encendido, en la misma red y que la IP sea correcta.');
        }

        return Redirect::back()->with(
            $ok ? 'success' : 'error',
            $ok
                ? 'Empleado y huellas actualizados en '.$device->name.'.'
                : 'No se pudo actualizar el empleado en '.$device->name.'.'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:24'],
            'user_id' => ['required', 'digits_between:1,9'],
            'password' => ['nullable', 'digits_between:1,8'],
            'card_number' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'role' => ['required', 'in:0,13,14'],
        ]);

        $device = Device::findOrFail($data['device_id']);
        $service = new ZktecoService($device);

        // setUser() sincroniza al terminar y el nuevo flujo crea el catálogo
        // central + la fila pivote automáticamente.
        $ok = $service->setUser([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'password' => $data['password'] ?: '1234',
            'role' => (int) $data['role'],
            'card_number' => $data['card_number'] ?: null,
        ]);

        return Redirect::to('/employees')
            ->with($ok ? 'success' : 'error', $ok ? 'Empleado agregado al catálogo y enrolado en el dispositivo.' : 'No se pudo agregar el empleado al dispositivo.');
    }

    /**
     * Da de baja a la persona en TODOS los checadores donde está enrolada.
     * removeUser() desacopla la pivote por equipo y elimina el registro del
     * catálogo solo cuando queda sin ningún enrolamiento.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $enrollments = $employee->devices()->get();

        foreach ($enrollments as $device) {
            (new ZktecoService($device))->removeUser((int) $device->pivot->device_uid);
        }

        // Si algún checador no respondió puede quedar un enrolamiento vivo;
        // el catálogo se conserva para no perder la trazabilidad del PIN.
        // La consulta es fresca: la instancia en memoria puede estar obsoleta
        // si removeUser ya eliminó al empleado del catálogo.
        if (! $employee->devices()->exists()) {
            return Redirect::to('/employees')
                ->with('success', 'Empleado dado de baja de todos los equipos.');
        }

        return Redirect::to('/employees')
            ->with('error', 'Algunos checadores no respondieron; el empleado conserva enrolamientos activos.');
    }
}
