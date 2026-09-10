@extends('layouts.admin')

@section('title', 'Editar empleado')

@section('content')
{{-- =========================================================
     HEADER — Breadcrumb + Identidad
     ========================================================= --}}
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ route('employees.index') }}" class="ref-chip text-decoration-none">Empleados</a>
            <i class="bi bi-chevron-right small text-tertiary-token"></i>
            <span class="small text-tertiary-token">Editar</span>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="avatar is-lg">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
            <div>
                <div class="employee-panel-kicker">Catálogo central · Edición</div>
                <h1 class="h4 mb-1 d-flex align-items-center gap-2 flex-wrap">
                    {{ $employee->name }}
                    @php $enrollment = $employee->devices->first(); @endphp
                    @if($employee->status_actual === 'B')
                        <span class="badge badge-with-dot cat-gray">Baja</span>
                    @elseif(!$enrollment)
                        <span class="badge badge-with-dot cat-gray">Sin enrolar</span>
                    @elseif($enrollment->pivot->active)
                        <span class="badge badge-with-dot cat-green">Activo</span>
                    @else
                        <span class="badge badge-with-dot cat-gray">Inactivo</span>
                    @endif
                </h1>
                <p class="text-muted mb-0 small">ID <code>{{ $employee->user_id }}</code>
                    <span class="mono text-tertiary-token ms-2">· {{ $employee->type_label ?? '—' }}</span>
                    @if($employee->departamento)<span class="text-tertiary-token"> · {{ $employee->departamento }}</span>@endif
                </p>
            </div>
        </div>
    </div>
    <span class="employee-id-badge"><i class="bi bi-person-badge"></i>ID {{ $employee->user_id }}</span>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card icon="bi-fingerprint" :value="$employee->fingerprints->unique('finger')->count()" label="Huellas disponibles" color="purple" />
    <x-stat-card icon="bi-hdd-network" :value="$employee->devices->count() . '/' . $totalDevices" label="Dispositivos enrolados" color="blue" />
    <x-stat-card icon="bi-card-text" :value="$employee->devices->filter(fn($d)=>filled($d->pivot->card_number))->count()" label="Tarjetas asignadas" color="green" />
</div>

<div class="row g-4 align-items-start">
    {{-- COLUMNA PRINCIPAL --}}
    <div class="col-12 col-xl-8">
        {{-- CARD A — Identidad --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-person me-2 text-tertiary-token"></i>Identidad</h2>
                <span class="small text-tertiary-token"><i class="bi bi-info-circle me-1"></i>Catálogo central</span>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.update', $employee) }}" method="POST" novalidate>
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $employee->name) }}" maxlength="24" required
                                   aria-describedby="name-help" autocomplete="name">
                            <div id="name-help" class="form-text">Máximo 24 caracteres. Se refleja en todos los checadores tras sincronizar.</div>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="user_id_display" class="form-label">ID / Badge</label>
                            <input type="text" id="user_id_display" class="form-control" value="{{ $employee->user_id }}" readonly disabled>
                            <div class="form-text">No editable aquí. Si necesitas cambiar badge, duplica/reenrola (escalar a soporte).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado catálogo</label>
                            <div class="pt-1">
                                <span class="badge badge-with-dot {{ $employee->status_actual === 'B' ? 'cat-gray' : 'cat-green' }}">
                                    {{ $employee->status_actual_label ?? ($employee->status_actual === 'B' ? 'Baja' : 'Activo') }}
                                </span>
                            </div>
                            <div class="form-text">Bajas se gestionan desde la tabla con confirmación.</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARD B — Credenciales --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-shield-lock me-2 text-tertiary-token"></i>Credenciales de acceso</h2>
                <span class="badge cat-amber"><i class="bi bi-exclamation-circle me-1"></i>Propaga a {{ $employee->devices->count() ?: '—' }} checadores al sincronizar</span>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.update', $employee) }}" method="POST" id="employee-credentials-form" novalidate>
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ old('name', $employee->name) }}" data-sync-name>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">PIN / Contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   maxlength="8" inputmode="numeric" pattern="[0-9]{1,8}"
                                   placeholder="Conservar actual" aria-describedby="password-help" autocomplete="off">
                            <div id="password-help" class="form-text">Solo números, 1–8 dígitos. Vacío = no cambia. Se aplica al sincronizar.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Rol en checador</label>
                            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" aria-describedby="role-help">
                                @php $currentRole = old('role', $employee->devices->first()?->pivot->role ?? 0); @endphp
                                @foreach(\App\Models\Employee::roles() as $value => $label)
                                    <option value="{{ $value }}" @selected($currentRole == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div id="role-help" class="form-text">0 = Usuario · 13 = Supervisor · 14 = Admin.</div>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Nombre, PIN y rol se guardan en el catálogo y se <strong>propagan a los {{ $employee->devices->count() }} checador(es) enrolados</strong> solo al ejecutar "Sincronizar". Si no está enrolado, quedan solo en catálogo.
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARD C — Enrolamientos --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-hdd-network me-2 text-tertiary-token"></i>Dispositivos enrolados · {{ $employee->devices->count() }}</h2>
                @if($employee->devices->isNotEmpty())
                    <span class="small text-tertiary-token">Tarjeta por checador</span>
                @endif
            </div>
            <div class="card-body">
                @forelse($employee->devices as $device)
                    <div class="employee-device-entry">
                        <div class="employee-device-meta">
                            <a href="{{ route('devices.show', $device) }}" class="ref-chip" title="UID {{ $device->pivot->device_uid }} en {{ $device->name }}">
                                <i class="bi bi-hdd-network"></i>{{ $device->name }}
                            </a>
                            <span class="mono text-secondary-token small">UID {{ $device->pivot->device_uid }}</span>
                        </div>
                        <form action="{{ route('employees.update-card', $employee) }}" method="POST" class="mt-2" novalidate>
                            @csrf
                            <input type="hidden" name="device_id" value="{{ $device->id }}">
                            <label class="form-label small mb-1" for="card-{{ $device->id }}">Código de tarjeta</label>
                            <div class="input-group" style="max-width: 380px">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" id="card-{{ $device->id }}" name="card_number"
                                       class="form-control @error('card_number') is-invalid @enderror"
                                       value="{{ old('card_number', $device->pivot->card_number) }}"
                                       inputmode="numeric" maxlength="10" pattern="[0-9]+"
                                       placeholder="Sin tarjeta" aria-label="Código de tarjeta para {{ $device->name }}">
                                <button class="btn btn-outline-primary" type="submit">Guardar</button>
                            </div>
                            @error('card_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text">Solo números, máx. 10 dígitos.</div>
                        </form>
                    </div>
                @empty
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Este empleado no está enrolado en ningún checador; los cambios se guardarán solo en el catálogo.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- CARD D — Distribuir --}}
        @if($syncDevices->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0"><i class="bi bi-send me-2 text-tertiary-token"></i>Enviar a dispositivos</h2>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all-devices>Seleccionar todos</button>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.sync-devices', $employee) }}" method="POST">
                    @csrf
                    <fieldset>
                        <legend class="visually-hidden">Selecciona checadores destino</legend>
                        <div class="employee-device-select-list">
                            @foreach($syncDevices as $syncDevice)
                                <label class="employee-device-select">
                                    <input type="checkbox" name="device_ids[]" value="{{ $syncDevice->id }}"
                                           @checked($employee->devices->contains('id', $syncDevice->id))>
                                    <span>
                                        <strong>{{ $syncDevice->name }}</strong>
                                        <small>{{ $syncDevice->ip }} · {{ $employee->devices->contains('id', $syncDevice->id) ? 'Actualizar acceso' : 'Agregar acceso' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i> Sincronizar seleccionados</button>
                    <div class="form-text mt-2">Envía nombre, PIN, tarjeta, rol y todas las huellas. Cada checador genera una tarea en la cola.</div>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- COLUMNA LATERAL --}}
    <div class="col-12 col-xl-4">
        {{-- CARD E — Huellas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-fingerprint me-2"></i>Huellas guardadas</h2>
                <span class="badge {{ $employee->fingerprints->count() ? 'cat-green' : 'cat-gray' }}">{{ $employee->fingerprints->count() }}</span>
            </div>
            <div class="card-body">
                @forelse($employee->fingerprints as $fingerprint)
                    <div class="employee-fingerprint-row">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar is-sm" style="background: var(--primary-soft); color: var(--primary)"><i class="bi bi-fingerprint"></i></span>
                                <div>
                                    <div class="fw-semibold small">Dedo {{ $fingerprint->finger }}</div>
                                    <span class="badge cat-gray">{{ $fingerprint->device?->name ?? 'Origen desconocido' }}</span>
                                </div>
                            </div>
                            <code class="small">{{ substr($fingerprint->template_hash, 0, 12) }}…</code>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($fingerprint->device_id && $employee->devices->count() > 1)
                                <form action="{{ route('employees.copy-fingerprint', [$employee, $fingerprint]) }}" method="POST" class="d-flex gap-1 flex-grow-1" style="max-width: 260px">
                                    @csrf
                                    <select name="device_id" class="form-select form-select-sm" aria-label="Checador destino para dedo {{ $fingerprint->finger }}" required>
                                        <option value="">Copiar a…</option>
                                        @foreach($employee->devices as $targetDevice)
                                            @if($targetDevice->id !== $fingerprint->device_id)
                                                <option value="{{ $targetDevice->id }}">{{ $targetDevice->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Copiar huella y conservar original"><i class="bi bi-copy"></i></button>
                                </form>
                            @endif
                            <form action="{{ route('employees.delete-fingerprint', [$employee, $fingerprint]) }}" method="POST"
                                  data-confirm data-confirm-danger
                                  data-confirm-title="¿Quitar esta huella?"
                                  data-confirm-message="Se eliminará el dedo {{ $fingerprint->finger }} del empleado y del checador de origen.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-icon-danger" type="submit" title="Quitar huella"><i class="bi bi-trash me-1"></i>Quitar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    @include('partials.empty-state', [
                        'icon' => 'bi-fingerprint',
                        'title' => 'Sin huellas sincronizadas',
                        'desc' => 'Este empleado no tiene huellas. Extrae desde el checador o enrola biométricamente.',
                    ])
                @endforelse

                @if(auth()->user()->isAdmin() && $employee->devices->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($employee->devices as $device)
                            <form action="{{ route('devices.sync-fingerprints', $device) }}" method="POST">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i> Actualizar desde {{ $device->name }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- CARD F — Historial --}}
        @if($employee->syncs->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h2 class="h6 mb-0"><i class="bi bi-clock-history me-2 text-tertiary-token"></i>Historial de sincronización</h2>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($employee->syncs as $sync)
                        @php $syncClass = ['completed'=>'cat-green','failed'=>'cat-red','running'=>'cat-amber','queued'=>'cat-gray'][$sync->status] ?? 'cat-gray'; @endphp
                        <div class="list-group-item d-flex align-items-center gap-3 py-3">
                            <span class="badge {{ $syncClass }}">{{ strtoupper(substr($sync->status,0,1)) }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-truncate">{{ $sync->device?->name ?? 'Dispositivo eliminado' }} · {{ $sync->operation_label }}</div>
                                <div class="small text-tertiary-token text-truncate">{{ $sync->stage }} · {{ $sync->created_at?->format('d/m/Y H:i') }}@if($sync->error_message) · {{ $sync->error_message }}@endif</div>
                            </div>
                            <span class="mono small text-secondary-token">{{ $sync->processed }}/{{ $sync->total }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer bg-transparent text-center py-2">
                    <a href="{{ route('operations.queue') }}" class="btn btn-sm btn-ghost">Ver cola completa <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        @endif

        {{-- CARD G — Zona de riesgo --}}
        <div class="card shadow-sm border" style="border-color: var(--border) !important;">
            <div class="card-body">
                <h3 class="h6 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Zona de riesgo</h3>
                <p class="small text-secondary-token mb-3">Dar de baja elimina accesos en todos los checadores. Se conserva histórico de checadas.</p>
                <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                      data-confirm data-confirm-danger
                      data-confirm-title="¿Quitar a {{ $employee->name }}?"
                      data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-person-x me-1"></i> Dar de baja empleado</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Sincronizar input name visible con hidden de credenciales
document.getElementById('name')?.addEventListener('input', e => {
    const h = document.querySelector('[data-sync-name]');
    if (h) h.value = e.target.value;
});
document.querySelector('[data-select-all-devices]')?.addEventListener('click', (event) => {
    const cbs = document.querySelectorAll('.employee-device-select input[type="checkbox"]');
    const anyUnchecked = [...cbs].some(cb => !cb.checked);
    cbs.forEach(cb => cb.checked = anyUnchecked);
    event.currentTarget.textContent = anyUnchecked ? 'Quitar selección' : 'Seleccionar todos';
});
// Spinner on submit para sync forms
document.querySelectorAll('form[action*="/sync-"]').forEach(form => {
    form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando…';
    });
});
</script>
@endpush
