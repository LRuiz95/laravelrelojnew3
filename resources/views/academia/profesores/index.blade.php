@extends('layouts.admin')

@section('title', 'Profesores')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Profesores</h1>
    <x-academia.ciclo-selector
        :ciclo="$ciclo"
        :ciclos="\App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get()"
    />
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Clave, nombre..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estatus</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($statusOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Origen</label>
                <select name="origen" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($origenOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('origen') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Departamento</label>
                <input type="text" name="departamento" class="form-control" value="{{ request('departamento') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="soloCiclo" name="solo_ciclo" value="1" {{ $soloCiclo ? 'checked' : '' }}>
                    <label class="form-check-label small" for="soloCiclo">Solo con horarios en ciclo</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($profesores->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-person-badge fs-1 mb-2"></i>
                <p>No se encontraron profesores</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Origen</th>
                            <th>Contrato</th>
                            <th>Sede</th>
                            <th>Estatus</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profesores as $p)
                            <tr>
                                <td class="fw-semibold">{{ $p->clave_profesor }}</td>
                                <td>{{ $p->nombre_completo }}</td>
                                <td>{{ $p->departamento ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $p->esPTC ? 'bg-purple' : 'bg-info' }}">
                                        {{ $p->origen_horario_label }}
                                    </span>
                                </td>
                                <td>{{ $p->tipo_contrato }}</td>
                                <td>{{ $p->sede?->descripcion ?? $p->id_campus }}</td>
                                <td>
                                    <span class="badge badge--status {{ $p->status_actual === 'A' ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $p->status_label }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('academia.profesores.show', $p) }}" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('academia.profesores.horario', $p) }}" class="btn btn-outline-secondary" title="Horario">
                                            <i class="bi bi-calendar-week"></i>
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

{{ $profesores->withQueryString()->links() }}
@endsection
