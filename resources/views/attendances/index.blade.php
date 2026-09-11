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

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#employee-attendance">Checadas de empleados</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#class-attendance">Asistencia por clase</button></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="employee-attendance">

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <x-filter-bar id="attendanceFilters" :action="route('attendances.index')" :clear-url="route('attendances.index')">
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
            </div>
            <div class="col-md-auto ms-auto d-flex gap-2 align-items-end">
                <a href="{{ route('attendances.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Excel/CSV
                </a>
                <a href="{{ route('attendances.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> PDF/Imprimir
                </a>
            </div>
        </x-filter-bar>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>ID</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Descanso</th>
                        <th>Regreso</th>
                        <th>Extra entrada</th>
                        <th>Extra salida</th>
                        <th>Tipo de empleado</th>
                        <th>Puesto / área</th>
                        <th>Dispositivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td data-label="Fecha">
                                <span class="mono text-secondary-token" style="font-size:12px">{{ \Carbon\Carbon::parse($attendance->date)->locale('es')->isoFormat('D MMM YYYY') }}</span>
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
                            @foreach ([0 => 'Entrada', 1 => 'Salida', 2 => 'Descanso', 3 => 'Regreso', 4 => 'Extra entrada', 5 => 'Extra salida'] as $status => $label)
                                <td data-label="{{ $label }}">
                                    @if ($attendance->punches->has($status))
                                        {{ $attendance->punches->get($status)->recorded_at->format('H:i:s') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td data-label="Tipo de empleado">
                                <span class="badge bg-light text-dark border">{{ $attendance->employee?->type_label ?? 'Sin clasificar' }}</span>
                            </td>
                            <td data-label="Puesto / área">
                                @if ($attendance->employee)
                                    {{ $attendance->employee->cargo ?: 'Sin puesto' }}
                                    <small class="d-block text-muted">{{ $attendance->employee->departamento ?: 'Sin área' }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Dispositivo">
                                {{ $attendance->device_names->join(', ') ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
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
</div>

<div class="tab-pane fade" id="class-attendance">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Asistencia docente por clase o sección</span>
            <small class="text-muted">Fecha, grupo, materia, sede y estado</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead><tr><th>Fecha</th><th>Registro</th><th>Docente</th><th>Grupo / carrera</th><th>Materia</th><th>Día</th><th>Sesión</th><th>Horario</th><th>Ubicación</th><th>Estado</th><th>Observaciones</th></tr></thead>
                <tbody>
                    @forelse ($classAttendances as $classAttendance)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($classAttendance->fecha)->locale('es')->isoFormat('D MMM YYYY') }}</td>
                            <td>{{ $classAttendance->created_at ? \Carbon\Carbon::parse($classAttendance->created_at)->locale('es')->isoFormat('D MMM YYYY HH:mm:ss') : '—' }}</td>
                            <td><strong>{{ trim(($classAttendance->profesor_paterno ?? '').' '.($classAttendance->profesor_materno ?? '').' '.($classAttendance->nombre_profesor ?? '')) ?: $classAttendance->clave_profesor }}</strong><small class="d-block text-muted">{{ $classAttendance->clave_profesor }}</small></td>
                            <td><strong>{{ $classAttendance->codigo_grupo }}</strong><small class="d-block text-muted">{{ $classAttendance->carrera ?? 'Carrera no definida' }}</small><small class="d-block text-muted">{{ $classAttendance->nivel ?? 'Nivel no definido' }} · {{ $classAttendance->turno ?? 'Turno no definido' }}</small></td>
                            <td>{{ $classAttendance->nombre_asignatura ?? $classAttendance->clave_asignatura }}</td>
                            <td>{{ [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'][$classAttendance->dia] ?? 'Día '.$classAttendance->dia }}</td>
                            <td>{{ $classAttendance->sesion }}</td>
                            <td>{{ $classAttendance->hora_inicio ? \Carbon\Carbon::parse($classAttendance->hora_inicio)->format('H:i') : '—' }} - {{ $classAttendance->hora_fin ? \Carbon\Carbon::parse($classAttendance->hora_fin)->format('H:i') : '—' }}</td>
                            <td>{{ $classAttendance->sede_nombre ?? $classAttendance->id_campus ?? 'Sede no definida' }}<small class="d-block text-muted">Edificio {{ $classAttendance->edificio ?? '—' }} · Aula {{ $classAttendance->aula ?? '—' }}</small><small class="d-block text-muted">Ciclo {{ $classAttendance->inicial }}-{{ $classAttendance->final }}-{{ $classAttendance->periodo }}</small></td>
                            <td><span class="badge bg-{{ $classAttendance->estado === 'PRESENTE' ? 'success' : ($classAttendance->estado === 'AUSENTE' ? 'danger' : 'warning') }}">{{ $classAttendance->estado }}</span></td>
                            <td>{{ $classAttendance->observaciones ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11">@include('partials.empty-state', ['icon' => 'bi-calendar-x', 'title' => 'Sin asistencia por clase', 'desc' => 'Las capturas docentes aparecerán aquí cuando se registren.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $classAttendances->links() }}</div>
</div>
</div>

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