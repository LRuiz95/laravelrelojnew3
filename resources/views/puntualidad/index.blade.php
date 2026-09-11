@extends('layouts.admin')

@section('title', 'Puntualidad')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Puntualidad</h4>
        <small class="text-muted">Control de llegadas, salidas y incidencias autorizadas</small>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <x-filter-bar id="puntualidadFilters" :action="route('puntualidad.index')" :clear-url="route('puntualidad.index')">
            <div class="col-md-2">
                <label for="from" class="form-label small mb-1">Desde</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ request('from', $from->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small mb-1">Hasta</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ request('to', $to->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <label for="gracia_minutos" class="form-label small mb-1">Lapso permitido</label>
                <input type="number" id="gracia_minutos" name="gracia_minutos" class="form-control" min="0" value="{{ request('gracia_minutos', $graciaMinutos) }}">
            </div>
            <div class="col-md-2">
                <label for="hora_entrada_base" class="form-label small mb-1">Hora entrada base</label>
                <input type="time" id="hora_entrada_base" name="hora_entrada_base" class="form-control" value="{{ request('hora_entrada_base', $horaEntradaBase) }}">
            </div>
            <div class="col-md-2">
                <label for="hora_salida_base" class="form-label small mb-1">Hora salida base</label>
                <input type="time" id="hora_salida_base" name="hora_salida_base" class="form-control" value="{{ request('hora_salida_base', $horaSalidaBase) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            </div>
        </x-filter-bar>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Empleados</span>
                <small class="text-muted">Llegada, salida e incidencias</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Área</th>
                            <th>Puesto</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                            <th>Incidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row['fecha'])->locale('es')->isoFormat('D MMM YYYY') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['empleado'] }}</div>
                                    <small class="text-muted">{{ $row['tipo_persona'] }} · {{ $row['user_id'] }}</small>
                                </td>
                                <td>{{ $row['area'] }}</td>
                                <td>{{ $row['puesto'] }}</td>
                                <td>{{ $row['entrada'] ?? '—' }}</td>
                                <td>{{ $row['salida'] ?? '—' }}</td>
                                <td>
                                    @php
                                        $stateClasses = [
                                            'puntual' => 'bg-success',
                                            'atraso' => 'bg-warning text-dark',
                                            'llego_temprano' => 'bg-info text-dark',
                                            'falta' => 'bg-danger',
                                            'se_fue_tarde' => 'bg-primary',
                                            'salio_temprano' => 'bg-secondary',
                                            'sin_salida' => 'bg-light text-dark border',
                                        ];
                                        $stateLabels = [
                                            'puntual' => 'Puntual',
                                            'atraso' => 'Atraso',
                                            'llego_temprano' => 'Llegó temprano',
                                            'falta' => 'Falta',
                                            'se_fue_tarde' => 'Se fue tarde',
                                            'salio_temprano' => 'Salió temprano',
                                            'sin_salida' => 'Sin salida',
                                        ];
                                    @endphp
                                    <span class="badge {{ $stateClasses[$row['llegada_estado']] ?? 'bg-light text-dark border' }}">
                                        {{ $stateLabels[$row['llegada_estado']] ?? $row['llegada_estado'] }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $incidenciaClasses = [
                                            'autorizada' => 'bg-success',
                                            'pendiente' => 'bg-warning text-dark',
                                            'ninguna' => 'bg-light text-dark border',
                                        ];
                                        $incidenciaLabels = [
                                            'autorizada' => 'Autorizada',
                                            'pendiente' => 'Pendiente',
                                            'ninguna' => 'Sin incidencia',
                                        ];
                                    @endphp
                                    <span class="badge {{ $incidenciaClasses[$row['incidencia_estado']] ?? 'bg-light text-dark border' }}">
                                        {{ $incidenciaLabels[$row['incidencia_estado']] ?? $row['incidencia_estado'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay registros de puntualidad para ese periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Profesores</span>
                <small class="text-muted">Estado de asistencia por clase</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Profesor</th>
                            <th>Área</th>
                            <th>Puesto</th>
                            <th>Estado</th>
                            <th>Incidencia</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($professorRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row['fecha'])->locale('es')->isoFormat('D MMM YYYY') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['profesor'] }}</div>
                                    <small class="text-muted">{{ $row['clave_profesor'] }}</small>
                                </td>
                                <td>{{ $row['area'] }}</td>
                                <td>{{ $row['puesto'] }}</td>
                                <td>
                                    @php
                                        $profState = [
                                            'presente' => 'bg-success',
                                            'retardo' => 'bg-warning text-dark',
                                            'justificado' => 'bg-info text-dark',
                                            'ausente' => 'bg-danger',
                                        ];
                                    @endphp
                                    <span class="badge {{ $profState[$row['estado']] ?? 'bg-light text-dark border' }}">
                                        {{ ucfirst($row['estado']) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $incClase = [
                                            'autorizada' => 'bg-success',
                                            'pendiente' => 'bg-warning text-dark',
                                            'ninguna' => 'bg-light text-dark border',
                                        ];
                                    @endphp
                                    <span class="badge {{ $incClase[$row['incidencia_estado']] ?? 'bg-light text-dark border' }}">
                                        {{ ucfirst($row['incidencia_estado']) }}
                                    </span>
                                </td>
                                <td>{{ $row['detalle'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay registros de profesores para ese periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
