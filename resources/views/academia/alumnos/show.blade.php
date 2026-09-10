@extends('layouts.admin')

@section('title', $alumno->nombre_completo . ' - ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $alumno->nombre_completo }}</h1>
        <p class="text-muted mb-0">Control: <strong>{{ $alumno->numero_alumno }}</strong> | CURP: {{ $alumno->curp }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.alumnos.kardex', $alumno) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-text me-1"></i> Kardex
        </a>
        <a href="{{ route('academia.alumnos.historial', $alumno) }}" class="btn btn-info">
            <i class="bi bi-clock-history me-1"></i> Historial
        </a>
    </div>
</div>

{{-- Info general --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="h4 mb-1">{{ $alumno->edad }}</div>
                <div class="text-muted small">Edad</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="h4 mb-1">{{ $inscripciones->count() }}</div>
                <div class="text-muted small">Grupos inscritos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="h4 mb-1">{{ $kardex['resumen']['aprobadas'] ?? 0 }}</div>
                <div class="text-muted small text-success">Aprobadas</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="h4 mb-1">{{ $kardex['resumen']['reprobadas'] ?? 0 }}</div>
                <div class="text-muted small text-danger">Reprobadas</div>
            </div>
        </div>
    </div>
</div>

{{-- Inscripciones --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Inscripciones del ciclo {{ $ciclo->label }}</span>
    </div>
    <div class="card-body p-0">
        @if ($inscripciones->isEmpty())
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-person-x fs-1 mb-2"></i>
                <p>No hay inscripciones en este ciclo</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Nivel</th>
                            <th>Turno</th>
                            <th>Inscritos</th>
                            <th>Estatus</th>
                            <th>Inscripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inscripciones as $ins)
                            <tr>
                                <td class="fw-semibold">{{ $ins->grupo->codigo_grupo }}</td>
                                <td>{{ $ins->grupo->nivel }}</td>
                                <td>
                                    <span class="badge {{ $ins->grupo->turnoRel && str_starts_with($ins->grupo->turnoRel->descripcion_corta, 'V') ? 'bg-purple' : 'bg-warning' }}">
                                        {{ $ins->grupo->turnoRel?->descripcion_corta }}
                                    </span>
                                </td>
                                <td>{{ $ins->grupo->inscritos }}</td>
                                <td>
                                    <span class="badge badge--status {{ ($ins->estatus ?? null) === 'INSCRITO' ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $ins->estatus ?? '—' }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $ins->fecha_inscripcion?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Kardex Resumen --}}
<div class="card">
    <div class="card-header">
        <span class="fw-bold">Resumen Académico del Ciclo</span>
    </div>
    <div class="card-body">
        @if (empty($kardex['materias']))
            <div class="text-center text-muted py-4">
                <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                <p>No hay calificaciones registradas en este ciclo</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Sem</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>P3</th>
                            <th>CF</th>
                            <th>EXR</th>
                            <th>CT</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kardex['materias'] as $clave => $m)
                            <tr>
                                <td class="fw-semibold">{{ $m['nombre'] ?? $clave }}</td>
                                <td>{{ $m['semestre'] ?? '' }}</td>
                                <td>{{ $m['P1'] ?? '—' }}</td>
                                <td>{{ $m['P2'] ?? '—' }}</td>
                                <td>{{ $m['P3'] ?? '—' }}</td>
                                <td>{{ $m['CF'] ?? '—' }}</td>
                                <td>{{ $m['EXR'] ?? '—' }}</td>
                                <td class="fw-semibold">{{ $m['CT'] ?? '—' }}</td>
                                <td>
                                    @php
                                        $estadoClass = match($m['ESTADO']) {
                                            'APROBADO' => 'bg-success',
                                            'REPROBADO' => 'bg-danger',
                                            'SIN DERECHO' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $estadoClass }}">
                                        {{ $m['ESTADO'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Resumen --}}
            <div class="row g-3 mt-3">
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <div class="h3 text-success">{{ $kardex['resumen']['aprobadas'] }}</div>
                            <small class="text-muted">Aprobadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <div class="h3 text-danger">{{ $kardex['resumen']['reprobadas'] }}</div>
                            <small class="text-muted">Reprobadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <div class="h3 text-warning">{{ $kardex['resumen']['sin_derecho'] }}</div>
                            <small class="text-muted">Sin derecho</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <div class="h3 text-info">{{ $kardex['resumen']['promedio'] ?? '—' }}</div>
                            <small class="text-muted">Promedio</small>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection