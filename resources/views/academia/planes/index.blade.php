@extends('layouts.admin')

@section('title', 'Planes de Estudio')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Planes de Estudio</h1>
    <div class="d-flex align-items-center gap-3">
        <x-academia.ciclo-selector
            :ciclo="$ciclo"
            :ciclos="\App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get()"
            :showBadge="false"
        />
        <a href="{{ route('academia.planes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Plan
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre o ID plan..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Academia\Nivel::activo()->get() as $n)
                        <option value="{{ $n->nivel }}" {{ request('nivel') == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="{{ route('academia.planes.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($planes->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-journal-bookmark fs-1 mb-2"></i>
                <p>No hay planes registrados</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID Plan</th>
                            <th>Nombre del Plan</th>
                            <th>Nivel</th>
                            <th>Modalidad</th>
                            <th>Duración</th>
                            <th>Materias</th>
                            <th>Ciclos donde se usa</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($planes as $plan)
                            <tr>
                                <td class="fw-semibold">{{ $plan->id_plan }}</td>
                                <td>{{ $plan->nombre_plan }}</td>
                                <td>{{ $plan->nivel }}</td>
                                <td>{{ $plan->modalidad }}</td>
                                <td>{{ $plan->duracion_semestres }} semestres</td>
                                <td>{{ $plan->materias_count }}</td>
                                <td>
                                    @php
                                        $ciclosCount = $ciclosPorPlan[$plan->id_plan] ?? 0;
                                    @endphp
                                    <span class="badge {{ $ciclosCount > 0 ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                        {{ $ciclosCount }} {{ $ciclosCount === 1 ? 'ciclo' : 'ciclos' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--status {{ $plan->activo ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $plan->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('academia.planes.show', $plan) }}" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('academia.planes.edit', $plan) }}" class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('academia.planes.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este plan?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
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

{{ $planes->links() }}
@endsection