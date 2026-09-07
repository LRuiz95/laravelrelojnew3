@extends('layouts.admin')

@section('title', 'Asistencias')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Asistencias</h4>
    <small class="text-muted">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Estados:</strong> <span class="badge cat-blue">Entrada</span> <span class="badge cat-green">Salida</span> <span class="badge cat-orange">Descanso</span> <span class="badge cat-purple">Regreso</span> <span class="badge cat-pink">Extra entrada</span> <span class="badge cat-lavender">Extra salida</span>
    </small>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="attendanceFilters" class="filter-row" method="GET">
            <div class="col-md-3">
                <label for="device_id" class="form-label small mb-1">Dispositivo</label>
                <select name="device_id" id="device_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(request('device_id') == $device->id)>{{ $device->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label small mb-1">Tipo de marcado</label>
                <select name="type" id="type" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($states as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') !== null && request('type') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="from" class="form-label small mb-1">Desde</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small mb-1">Hasta</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="{{ route('attendances.index') }}" class="btn btn-link small w-100 mt-1">Limpiar filtros</a>
            </div>
            <div class="col-md-auto ms-auto d-flex gap-2">
                <a href="{{ route('attendances.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Excel/CSV
                </a>
                <a href="{{ route('attendances.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> PDF/Imprimir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Empleado</th>
                        <th>ID</th>
                        <th>Marcado</th>
                        <th>Dispositivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td data-label="Fecha y hora">
                                <span class="mono text-secondary-token" style="font-size:12px">
                                    {{ $attendance->recorded_at->locale('es')->isoFormat('D MMM YYYY · HH:mm:ss') }}
                                </span>
                            </td>
                            <td data-label="Empleado">
                                @if ($attendance->employee)
                                    <span class="avatar is-sm me-2">{{ strtoupper(substr($attendance->employee->name, 0, 1)) }}</span>
                                    <span class="fw-semibold">{{ $attendance->employee->name }}</span>
                                @else
                                    <span class="badge cat-orange">Sin asignar</span>
                                @endif
                            </td>
                            <td data-label="ID"><code>{{ $attendance->user_id }}</code></td>
                            <td data-label="Marcado">
                                <span class="badge {{ $attendance->stateColorClass() }}">{{ $attendance->stateLabel() }}</span>
                            </td>
                            <td data-label="Dispositivo">
                                @if ($attendance->device)
                                    <a href="{{ route('devices.show', $attendance->device) }}" class="ref-chip">
                                        <i class="bi bi-hdd-network"></i>{{ $attendance->device->name }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                @include('partials.empty-state', [
                                    'icon'     => request('type') || request('from') || request('to') || request('device_id')
                                        ? 'bi-search'
                                        : 'bi-calendar-x',
                                    'title'    => request('type') || request('from') || request('to') || request('device_id')
                                        ? 'Sin resultados para los filtros'
                                        : 'Aún no hay registros',
                                    'desc'     => request('type') || request('from') || request('to') || request('device_id')
                                        ? 'Prueba con otros criterios o limpia los filtros aplicados.'
                                        : 'Los registros aparecerán aquí cuando se sincronicen desde los checadores.',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $attendances->links() }}</div>

<script>
document.getElementById('attendanceFilters').addEventListener('change', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(form.action + '?' + new URLSearchParams(formData), {
            headers: { 'Accept': 'text/html' }
        });
        if (!response.ok) throw new Error('Error en la petición');
        
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTbody = doc.querySelector('table tbody').innerHTML;
        
        // Reemplazar solo el tbody para mantener los enlaces de paginación
        document.querySelector('table tbody').innerHTML = newTbody;
    } catch (error) {
        console.error('Error filtrando asistencias:', error);
    }
});
</script>

@endsection