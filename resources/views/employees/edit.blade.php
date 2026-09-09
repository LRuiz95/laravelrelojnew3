@extends('layouts.admin')

@section('title', 'Editar empleado')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <div class="employee-panel-kicker">Catálogo central</div>
        <h1 class="h4 mb-1">{{ $employee->name }}</h1>
        <p class="text-muted mb-0">Administra identidad, accesos y credenciales biométricas.</p>
    </div>
    <span class="employee-id-badge"><i class="bi bi-person-badge"></i>ID {{ $employee->user_id }}</span>
</div>
<div class="employee-edit-layout">
<div class="card employee-panel employee-access-panel">
    <div class="card-body">
        <div class="employee-panel-heading">
            <div><div class="employee-panel-kicker">Datos generales</div><h2>Identidad y acceso</h2></div>
            <i class="bi bi-person-lock text-tertiary-token fs-5"></i>
        </div>
        <div class="employee-credential-summary">
            <div class="employee-credential-stat"><strong>{{ $employee->fingerprints->unique('finger')->count() }}</strong><span>Huellas disponibles</span></div>
            <div class="employee-credential-stat"><strong>{{ $employee->devices->count() }}/{{ $totalDevices }}</strong><span>Dispositivos enrolados</span></div>
            <div class="employee-credential-stat"><strong>{{ $employee->devices->filter(fn ($device) => filled($device->pivot->card_number))->count() }}</strong><span>Tarjetas asignadas</span></div>
        </div>
        @php $enrollments = $employee->devices; @endphp
        @if ($enrollments->isNotEmpty())
            <div class="employee-subsection employee-enrolled-devices mt-0 pt-0 border-0">
                <div class="employee-subsection-title">Dispositivos enrolados · {{ $enrollments->count() }}</div>
                @foreach ($enrollments as $device)
                    <div class="employee-device-entry">
                        <div class="employee-device-meta">
                            <a href="{{ route('devices.show', $device) }}" class="ref-chip" title="UID {{ $device->pivot->device_uid }} en {{ $device->name }}">
                                <i class="bi bi-hdd-network"></i>{{ $device->name }}
                            </a>
                            <span class="mono text-secondary-token small">UID {{ $device->pivot->device_uid }}</span>
                        </div>
                    <form action="{{ route('employees.update-card', $employee) }}" method="POST" class="w-100 mt-2">
                        @csrf
                        <input type="hidden" name="device_id" value="{{ $device->id }}">
                        <div class="input-group input-group-sm" style="max-width:360px">
                            <label class="input-group-text" for="card-{{ $device->id }}">Tarjeta</label>
                            <input type="text" id="card-{{ $device->id }}" name="card_number" class="form-control"
                                value="{{ old('card_number', $device->pivot->card_number) }}" inputmode="numeric" maxlength="10" pattern="[0-9]+"
                                   placeholder="Sin tarjeta" aria-label="Código de tarjeta para {{ $device->name }}">
                            <button class="btn btn-outline-primary" type="submit">Guardar</button>
                        </div>
                    </form>
                    </div>
                @endforeach
            </div>
            <div class="alert alert-info employee-enrollment-note py-2 small">
                <i class="bi bi-info-circle me-1"></i>
                Nombre, rol y contraseña se aplicarán a los <strong>{{ $enrollments->count() }}</strong> checador(es) donde está enrolada esta persona.
            </div>
        @else
            <div class="alert alert-warning py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Este empleado no está enrolado en ningún checador; los cambios se guardarán solo en el catálogo.
            </div>
        @endif
        @if ($syncDevices->isNotEmpty())
            <form action="{{ route('employees.sync-devices', $employee) }}" method="POST" class="employee-subsection employee-device-distribution">
                @csrf
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label small fw-semibold mb-0">Enviar empleado a dispositivos</label>
                    <button type="button" class="btn btn-sm btn-link p-0" data-select-all-devices>Seleccionar todos</button>
                </div>
                <div class="employee-device-select-list">
                    @foreach ($syncDevices as $syncDevice)
                        <label class="employee-device-select">
                            <input type="checkbox" name="device_ids[]" value="{{ $syncDevice->id }}" @checked($employee->devices->contains('id', $syncDevice->id))>
                            <span><strong>{{ $syncDevice->name }}</strong><small>{{ $syncDevice->ip }} · {{ $employee->devices->contains('id', $syncDevice->id) ? 'Actualizar acceso' : 'Agregar acceso' }}</small></span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i> Sincronizar seleccionados</button>
                <div class="employee-action-note mt-2">Envía nombre, PIN, tarjeta, rol y todas las huellas. Cada checador tendrá su propia tarea en la cola.</div>
            </form>
        @endif

        <form action="{{ route('employees.update', $employee) }}" method="POST" class="employee-subsection employee-profile-form">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nombre completo</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $employee->name) }}" maxlength="24" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                          <label for="password" class="form-label">Acceso por contraseña (PIN)</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                              maxlength="8" inputmode="numeric" pattern="[0-9]{1,8}" placeholder="Conservar actual">
                          <div class="form-text">Solo números. Déjalo vacío para conservar el PIN actual.</div>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 mb-3">
                    <label for="role" class="form-label">Rol</label>
                    <small class="text-muted fw-normal">0 = Usuario (acceso básico), 13 = Supervisor (ver y editar), 14 = Admin (gestión completa)</small>
                    <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                        @php $currentRole = old('role', $enrollments->first()?->pivot->role ?? 0); @endphp
                        @foreach (\App\Models\Employee::roles() as $value => $label)
                            <option value="{{ $value }}" @selected($currentRole == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('employees.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
</div>

<div class="card employee-panel employee-biometric-panel">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0"><i class="bi bi-fingerprint me-1"></i> Huellas guardadas</h2>
            <span class="badge {{ $employee->fingerprints->count() ? 'cat-green' : 'cat-gray' }}">{{ $employee->fingerprints->count() }}</span>
        </div>
@if ($availableFingerprints->isNotEmpty())
            <p class="text-muted small">Reasignación de huellas: no disponible actualmente</p>
        @endif
        @forelse ($employee->fingerprints as $fingerprint)
            <div class="employee-fingerprint-row d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span>
                    <i class="bi bi-fingerprint text-success me-2"></i>Dedo {{ $fingerprint->finger }}
                    <span class="badge cat-gray ms-1">{{ $fingerprint->device?->name ?? 'Origen desconocido' }}</span>
                </span>
                <code class="small">{{ substr($fingerprint->template_hash, 0, 12) }}...</code>
                @if ($fingerprint->device_id && $enrollments->count() > 1)
                    <form action="{{ route('employees.copy-fingerprint', [$employee, $fingerprint]) }}" method="POST" class="d-flex gap-1">
                        @csrf
                        <select name="device_id" class="form-select form-select-sm" aria-label="Checador destino para dedo {{ $fingerprint->finger }}" required>
                            <option value="">Copiar a...</option>
                            @foreach ($enrollments as $targetDevice)
                                @if ($targetDevice->id !== $fingerprint->device_id)
                                    <option value="{{ $targetDevice->id }}">{{ $targetDevice->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="submit" title="Copiar huella y conservar el original" aria-label="Copiar huella y conservar el original">
                            <i class="bi bi-copy"></i>
                        </button>
                    </form>
                @endif
                <form action="{{ route('employees.delete-fingerprint', [$employee, $fingerprint]) }}" method="POST" class="d-inline"
                      data-confirm data-confirm-danger
                      data-confirm-title="¿Quitar esta huella?"
                      data-confirm-message="Se eliminará el dedo {{ $fingerprint->finger }} del empleado y del checador de origen.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-icon-danger" type="submit" title="Quitar huella" aria-label="Quitar huella">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
<p class="text-muted small">Eliminar huella: no disponible actualmente</p>
            </div>
        @empty
            <p class="text-muted mb-0">Este empleado no tiene huellas sincronizadas.</p>
        @endforelse
        @if (auth()->user()->isAdmin() && $enrollments->isNotEmpty())
            <div class="mt-3 d-flex flex-wrap gap-2">
                @foreach ($enrollments as $device)
                    <form action="{{ route('devices.sync-fingerprints', $device) }}" method="POST">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <button class="btn btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i> Actualizar desde {{ $device->name }}</button>
                    </form>
                @endforeach
            </div>
        @endif
        @if ($employee->syncs->isNotEmpty())
            <div class="employee-sync-history">
                <div class="employee-subsection-title">Historial reciente</div>
                @foreach ($employee->syncs as $sync)
                    @php $syncClass = ['completed' => 'cat-green', 'failed' => 'cat-red', 'running' => 'cat-amber', 'queued' => 'cat-gray'][$sync->status] ?? 'cat-gray'; @endphp
                    <div class="employee-sync-row">
                        <span class="badge {{ $syncClass }}">{{ strtoupper(substr($sync->status, 0, 1)) }}</span>
                        <div class="sync-copy">
                            <strong>{{ $sync->device?->name ?? 'Dispositivo eliminado' }} · {{ $sync->operation_label }}</strong>
                            <span>{{ $sync->stage }} · {{ $sync->created_at?->format('d/m/Y H:i') }}@if ($sync->error_message) · {{ $sync->error_message }}@endif</span>
                        </div>
                        <span class="mono text-secondary-token small">{{ $sync->processed }}/{{ $sync->total }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
</div>
<script>
    const fingerprintSearch = document.getElementById('fingerprintEmployeeSearch');
    const fingerprintOptions = document.querySelectorAll('#fingerprint_id option[data-search]');
    fingerprintSearch?.addEventListener('input', () => {
        const query = fingerprintSearch.value.trim().toLowerCase();
        fingerprintOptions.forEach((option) => {
            option.hidden = Boolean(query) && !option.dataset.search.includes(query);
        });
    });
</script>
<script>
    document.querySelector('[data-select-all-devices]')?.addEventListener('click', (event) => {
        const checkboxes = document.querySelectorAll('.employee-device-select input');
        const selectAll = [...checkboxes].some((checkbox) => !checkbox.checked);
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll; });
        event.currentTarget.textContent = selectAll ? 'Quitar selección' : 'Seleccionar todos';
    });
</script>
@endsection
