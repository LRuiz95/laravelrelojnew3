@extends('layouts.admin')

@section('title', 'Alumnos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Alumnos</h1>
    <x-academia.ciclo-selector
        :ciclo="$ciclo"
        :ciclos="\App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get()"
    />
</div>

<div class="card mb-4">
    <div class="card-body">
        <x-filter-bar :action="route('academia.alumnos.index')" :clear-url="route('academia.alumnos.index')">
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
        </x-filter-bar>
    </div>
</div>

<x-data-table
    :headers="[
        ['label' => 'Control', 'field' => 'numero_alumno', 'class' => 'fw-semibold'],
        ['label' => 'Nombre', 'render' => fn($alumno) => '<a href=\"' . route('academia.alumnos.show', $alumno) . '\" class=\"text-decoration-none fw-semibold\">' . e($alumno->nombre_completo) . '</a>'],
        ['label' => 'Grupo', 'render' => function ($alumno) { $inscripcion = $alumno->inscripciones->first(); return $inscripcion && $inscripcion->grupo ? '<span class=\"badge bg-info text-dark\">' . e($inscripcion->grupo->codigo_grupo) . '</span>' : '<span class=\"text-muted\">—</span>'; }],
        ['label' => 'CURP', 'field' => 'curp', 'class' => 'small'],
        ['label' => 'Nivel', 'field' => 'nivel'],
        ['label' => 'Turno', 'render' => fn($alumno) => '<span class=\"badge ' . ($alumno->turnoRel && str_starts_with($alumno->turnoRel->descripcion_corta, 'V') ? 'bg-purple' : 'bg-warning') . '\">' . e($alumno->turnoRel?->descripcion_corta ?? $alumno->turno) . '</span>'],
        ['label' => 'Sede', 'render' => fn($alumno) => e($alumno->sede?->descripcion ?? '')],
        ['label' => 'Estatus', 'render' => fn($alumno) => '<span class=\"badge badge--status ' . (in_array($alumno->estatus, ['ACTIVO', 'REINSCRITO']) ? 'badge--active' : 'badge--inactive') . '\">' . e($alumno->estatus) . '</span>'],
    ]"
    :rows="$alumnos"
    :actions="[
        ['type' => 'link', 'url' => fn($alumno) => route('academia.alumnos.show', $alumno), 'style' => 'primary', 'title' => 'Ver', 'icon' => 'bi bi-eye'],
        ['type' => 'link', 'url' => fn($alumno) => route('academia.alumnos.kardex', $alumno), 'style' => 'success', 'title' => 'Kardex', 'icon' => 'bi bi-file-earmark-text'],
        ['type' => 'link', 'url' => fn($alumno) => route('academia.alumnos.historial', $alumno), 'style' => 'info', 'title' => 'Historial', 'icon' => 'bi bi-clock-history'],
    ]"
    :pagination="$alumnos"
    empty-message="No se encontraron alumnos"
/>
@endsection