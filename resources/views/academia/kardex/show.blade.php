@extends('layouts.admin')

@section('title', 'Kardex: ' . $alumnoSeleccionado->nombre_completo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kardex</h1>
        <p class="text-muted mb-0">{{ $alumnoSeleccionado->nombre_completo }} | Control: {{ $alumnoSeleccionado->numero_alumno }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.alumnos.show', $alumnoSeleccionado) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="{{ route('academia.kardex.print', ['alumno_id' => $alumnoSeleccionado->numero_alumno]) }}" target="_blank" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Imprimir
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
            <div class="col-md-3">
                <label class="form-label">Ciclo</label>
                <select name="ciclo" class="form-select">
                    @foreach ($ciclos as $c)
                        <option value="{{ $c->label }}" {{ request('ciclo') == $c->label ? 'selected' : '' }}>{{ $c->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

@if ($alumnos && $alumnos->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">Resultados: {{ $alumnos->count() }} alumno(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Control</th><th>Nombre</th><th>Nivel</th><th>Turno</th><th>Ciclo</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($alumnos as $a)
                            <tr>
                                <td>{{ $a->numero_alumno }}</td>
                                <td>{{ $a->nombre_completo }}</td>
                                <td>{{ $a->nivel }}</td>
                                <td>{{ $a->turno }}</td>
                                <td>{{ request('ciclo') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('academia.kardex.show', ['alumno_id' => $a->numero_alumno, 'ciclo' => request('ciclo')]) }}" class="btn btn-sm btn-primary">Ver Kardex</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
@endif

@if ($kardex)
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">Kardex Ciclo: {{ $ciclo->label }}</span>
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

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body"><div class="h3 text-success">{{ $kardex['resumen']['aprobadas'] }}</div><small class="text-muted">Aprobadas</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body"><div class="h3 text-danger">{{ $kardex['resumen']['reprobadas'] }}</div><small class="text-muted">Reprobadas</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body"><div class="h3 text-warning">{{ $kardex['resumen']['sin_derecho'] }}</div><small class="text-muted">Sin derecho</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body"><div class="h3 text-info">{{ $kardex['resumen']['promedio'] ?? '—' }}</div><small class="text-muted">Promedio</small></div>
            </div>
        </div>
    </div>
@endif
@endsection