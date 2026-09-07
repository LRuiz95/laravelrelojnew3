@extends('layouts.admin')

@section('title', 'Alumnos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Alumnos</h1>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Control, nombre, CURP..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estatus</label>
                <select name="estatus" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['ACTIVO','BAJA','EGRESADO','TITULADO','IRREGULAR'] as $e)
                        <option value="{{ $e }}" {{ request('estatus') == $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Academia\Nivel::activo()->get() as $n)
                        <option value="{{ $n->nivel }}" {{ request('nivel') == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Academia\Turno::activo()->get() as $t)
                        <option value="{{ $t->turno }}" {{ request('turno') == $t->turno ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <a href="{{ route('academia.alumnos.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($alumnos->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-people fs-1 mb-2"></i>
                <p>No se encontraron alumnos</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Control</th>
                            <th>Nombre</th>
                            <th>CURP</th>
                            <th>Nivel</th>
                            <th>Turno</th>
                            <th>Sede</th>
                            <th>Estatus</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alumnos as $alumno)
                            <tr>
                                <td class="fw-semibold">{{ $alumno->numero_alumno }}</td>
                                <td>
                                    <a href="{{ route('academia.alumnos.show', $alumno) }}" class="text-decoration-none fw-semibold">
                                        {{ $alumno->nombre_completo }}
                                    </a>
                                </td>
                                <td class="small">{{ $alumno->curp }}</td>
                                <td>{{ $alumno->nivel }}</td>
                                <td>
                                    <span class="badge {{ $alumno->turnoRel && str_starts_with($alumno->turnoRel->descripcion_corta, 'V') ? 'bg-purple' : 'bg-warning' }}">
                                        {{ $alumno->turnoRel?->descripcion_corta ?? $alumno->turno }}
                                    </span>
                                </td>
                                <td>{{ $alumno->sede?->descripcion }}</td>
                                <td>
                                    <span class="badge badge--status {{ in_array($alumno->estatus, ['ACTIVO','REINSCRITO']) ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $alumno->estatus }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('academia.alumnos.show', $alumno) }}" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('academia.alumnos.kardex', $alumno) }}" class="btn btn-outline-success" title="Kardex">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                        <a href="{{ route('academia.alumnos.historial', $alumno) }}" class="btn btn-outline-info" title="Historial">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{ $alumnos->links() }}
@endsection