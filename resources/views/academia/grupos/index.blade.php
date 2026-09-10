@extends('layouts.admin')

@section('title', 'Grupos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Grupos</h1>
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
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Academia\Nivel::activo()->get() as $n)
                        <option value="{{ $n->nivel }}" {{ request('nivel') == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Academia\Turno::activo()->get() as $t)
                        <option value="{{ $t->turno }}" {{ request('turno') == $t->turno ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Academia\Sede::activo()->get() as $s)
                        <option value="{{ $s->id_campus }}" {{ request('sede') == $s->id_campus ? 'selected' : '' }}>{{ $s->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Nivel</th>
                        <th>Turno</th>
                        <th>Grado</th>
                        <th>Modalidad</th>
                        <th>Inscritos</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupos as $grupo)
                        <tr>
                            <td class="fw-semibold">{{ $grupo->codigo_grupo }}</td>
                            <td>{{ $grupo->nivelRel?->descripcion ?? $grupo->nivel }}</td>
                            <td>
                                {{ $grupo->turno_nombre }}
                            </td>
                            <td>{{ $grupo->grado }}°</td>
                            <td>
                                {{ $grupo->modalidad_nombre }}
                                @if ($grupo->codigo_grupo_partes['nivel_superior'])
                                    <small class="d-block text-muted">Ingeniería/Licenciatura</small>
                                @endif
                            </td>
                            <td>{{ $grupo->inscritos }}</td>
                            <td>{{ $grupo->sede?->descripcion ?? $grupo->id_campus }}</td>
                            <td>
                                <span class="badge badge--status {{ $grupo->activo ? 'badge--active' : 'badge--inactive' }}">
                                    {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('academia.grupos.show', $grupo) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $grupos->links() }}
@endsection