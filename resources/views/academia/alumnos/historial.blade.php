@extends('layouts.admin')

@section('title', 'Historial: ' . $alumno->nombre_completo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Historial Académico: {{ $alumno->nombre_completo }}</h1>
        <p class="text-muted mb-0">Control: <strong>{{ $alumno->numero_alumno }}</strong></p>
    </div>
    <a href="{{ route('academia.alumnos.show', $alumno) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

@if (empty($historial))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-clock-history fs-1 mb-2"></i>
            <p>No hay historial académico registrado</p>
        </div>
    </div>
@else
    @foreach ($historial as $cicloLabel => $materias)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Ciclo: {{ $cicloLabel }}</span>
                    @php
                        $cicloMaterias = collect($materias);
                        $aprobadas = $cicloMaterias->where('ESTADO', 'APROBADO')->count();
                        $reprobadas = $cicloMaterias->where('ESTADO', 'REPROBADO')->count();
                        $promedio = $cicloMaterias->where('CT', '!=', null)->where('CT', '!=', '')->avg('CT');
                    @endphp
                    <div class="d-flex gap-3 small">
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>{{ $aprobadas }} aprobadas</span>
                        <span class="badge bg-danger"><i class="bi bi-x me-1"></i>{{ $reprobadas }} reprobadas</span>
                        @if ($promedio)
                            <span class="badge bg-info">Promedio: {{ number_format($promedio, 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>P3</th>
                                <th>CF</th>
                                <th>EXR</th>
                                <th>CT</th>
                                <th>Literal</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materias as $m)
                                <tr>
                                    <td class="fw-semibold">{{ $m['nombre'] ?? $m['clave'] }}</td>
                                    <td class="text-center">{{ $m['P1'] ?? '—' }}</td>
                                    <td class="text-center">{{ $m['P2'] ?? '—' }}</td>
                                    <td class="text-center">{{ $m['P3'] ?? '—' }}</td>
                                    <td class="text-center">{{ $m['CF'] ?? '—' }}</td>
                                    <td class="text-center">{{ $m['EXR'] ?? '—' }}</td>
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
    @endforeach
@endif
@endsection