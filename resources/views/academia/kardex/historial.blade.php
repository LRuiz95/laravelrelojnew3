@extends('layouts.admin')

@section('title', 'Historial Académico')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Historial Académico</h1>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.kardex.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

{{-- Buscador --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Buscar alumno</label>
                <input type="text" name="buscar" class="form-control" placeholder="Control, nombre, CURP..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="{{ route('academia.kardex.historial') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

@if ($alumno)
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">{{ $alumno->nombre_completo }} | Control: {{ $alumno->numero_alumno }}</span>
        </div>
    </div>
@endif

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