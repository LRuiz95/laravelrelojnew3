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

<div class="card mb-4">
    <div class="card-body">
        <x-filter-bar :action="route('academia.grupos.index')" :clear-url="route('academia.grupos.index')">
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
        </x-filter-bar>
    </div>
</div>

<x-data-table
    :headers="[
        ['label' => 'Grupo', 'field' => 'codigo_grupo', 'class' => 'fw-semibold'],
        ['label' => 'Nivel', 'render' => fn($grupo) => e($grupo->nivelRel?->descripcion ?? $grupo->nivel)],
        ['label' => 'Turno', 'render' => fn($grupo) => e($grupo->turno_nombre)],
        ['label' => 'Grado', 'render' => fn($grupo) => e($grupo->grado . '°')],
        ['label' => 'Modalidad', 'render' => function ($grupo) { $html = e($grupo->modalidad_nombre); if ($grupo->codigo_grupo_partes['nivel_superior']) { $html .= '<small class=\"d-block text-muted\">Ingeniería/Licenciatura</small>'; } return $html; }],
        ['label' => 'Inscritos', 'field' => 'inscritos'],
        ['label' => 'Sede', 'render' => fn($grupo) => e($grupo->sede?->descripcion ?? $grupo->id_campus)],
        ['label' => 'Estado', 'render' => fn($grupo) => '<span class=\"badge badge--status ' . ($grupo->activo ? 'badge--active' : 'badge--inactive') . '\">' . ($grupo->activo ? 'Activo' : 'Inactivo') . '</span>'],
    ]"
    :rows="$grupos"
    :actions="[
        ['type' => 'link', 'url' => fn($grupo) => route('academia.grupos.show', $grupo), 'style' => 'primary', 'title' => 'Ver', 'icon' => 'bi bi-eye'],
    ]"
    :pagination="$grupos"
    empty-message="No se encontraron grupos"
/>
@endsection