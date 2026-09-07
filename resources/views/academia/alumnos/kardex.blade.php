@extends('layouts.admin')

@section('title', 'Kardex: ' . $alumno->nombre_completo . ' - ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kardex: {{ $alumno->nombre_completo }}</h1>
        <p class="text-muted mb-0">Control: <strong>{{ $alumno->numero_alumno }}</strong> | Ciclo: {{ $ciclo->label }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.alumnos.show', $alumno) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="{{ route('academia.alumnos.kardex.print', ['alumno_id' => $alumno->numero_alumno]) }}" target="_blank" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Imprimir
        </a>
    </div>
</div>

@if (empty($kardex['materias']))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
            <p>No hay calificaciones registradas en este ciclo</p>
        </div>
    </div>
@else
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">Calificaciones del Ciclo</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Sem</th>
                            <th class="text-center">P1</th>
                            <th class="text-center">P2</th>
                            <th class="text-center">P3</th>
                            <th class="text-center">CF</th>
                            <th class="text-center">EXR</th>
                            <th class="text-center">EXRS</th>
                            <th class="text-center">CT</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kardex['materias'] as $clave => $m)
                            <tr>
                                <td class="fw-semibold">{{ $m['nombre'] ?? $clave }}</td>
                                <td>{{ $m['semestre'] ?? '' }}</td>
                                <td class="text-center">{{ $m['P1'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['P2'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['P3'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['CF'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['EXR'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['EXRS'] ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $m['CT'] ?? '—' }}</td>
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
        </div>
    </div>

    {{-- Resumen --}}
    <div class="row g-3 mb-4">
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

    {{-- Detalle por evaluación --}}
    <div class="card">
        <div class="card-header">
            <span class="fw-bold">Detalle por Evaluación</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>P3</th>
                            <th>CF</th>
                            <th>EXR</th>
                            <th>EXRS</th>
                            <th>CT</th>
                            <th>Literal</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kardex['materias'] as $clave => $m)
                            <tr>
                                <td class="fw-semibold small">{{ $m['nombre'] ?? $clave }}</td>
                                <td class="text-center">{{ $m['P1'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['P2'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['P3'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['CF'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['EXR'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['EXRS'] ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $m['CT'] ?? '—' }}</td>
                                <td class="text-center">{{ $m['LITERAL'] }}</td>
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
        </div>
    </div>
@endif
@endsection