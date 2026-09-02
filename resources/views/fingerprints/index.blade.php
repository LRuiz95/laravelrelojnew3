@extends('layouts.admin')

@section('title', 'Huellas por empleado')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Huellas biométricas</h2>
        <p class="text-muted mb-0">Consulta qué empleados tienen plantillas guardadas y en qué dispositivo.</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-ghost"><i class="bi bi-people me-1"></i> Ver empleados</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-5">
                <label for="q" class="form-label small mb-1">Empleado o ID</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por nombre o ID">
            </div>
            <div class="col-md-4">
                <label for="device_id" class="form-label small mb-1">Dispositivo</label>
                <select id="device_id" name="device_id" class="form-select">
                    <option value="">Todos los dispositivos</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(request('device_id') == $device->id)>{{ $device->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                <a href="{{ route('fingerprints.index') }}" class="btn btn-ghost">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead><tr><th>Empleado</th><th>Dispositivo</th><th>Huellas</th><th>Dedos registrados</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td data-label="Empleado"><span class="fw-semibold">{{ $employee->name }}</span><div class="small text-muted">ID {{ $employee->user_id }} · {{ $employee->devices->count() }} equipo(s)</div></td>
                            <td data-label="Dispositivo">
                                @forelse ($employee->devices as $device)
                                    <a href="{{ route('devices.show', $device) }}" class="ref-chip me-1 mb-1" title="UID {{ $device->pivot->device_uid }}"><i class="bi bi-hdd-network"></i>{{ $device->name }}</a>
                                @empty
                                    <span class="text-secondary-token small">Sin enrolamiento</span>
                                @endforelse
                            </td>
                            <td data-label="Huellas"><span class="badge {{ $employee->fingerprints_count ? 'cat-green' : 'cat-gray' }}">{{ $employee->fingerprints_count }} guardadas</span></td>
                            <td data-label="Dedos registrados">
                                @forelse ($employee->fingerprints as $fingerprint)
                                    <span class="badge cat-blue me-1">Dedo {{ $fingerprint->finger }}</span>
                                @empty
                                    <span class="text-muted small">Sin plantillas</span>
                                @endforelse
                            </td>
                            <td class="text-end"><a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-ghost" title="Ver empleado y huellas"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('partials.empty-state', ['icon' => 'bi-fingerprint', 'title' => 'No hay huellas guardadas', 'desc' => 'Sincroniza las huellas desde el detalle de un dispositivo para verlas aquí.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
