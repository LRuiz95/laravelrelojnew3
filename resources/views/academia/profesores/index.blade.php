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

<div class="card mb-4">
    <div class="card-body">
        <x-filter-bar :action="route('academia.profesores.index')" :clear-url="route('academia.profesores.index')">
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
        </x-filter-bar>
    </div>
</div>

<x-data-table
    :headers="[
        ['label' => 'Clave', 'field' => 'clave_profesor', 'class' => 'fw-semibold'],
        ['label' => 'Nombre', 'field' => 'nombre_completo'],
        ['label' => 'Departamento', 'render' => fn($p) => e($p->departamento ?? '—')],
        ['label' => 'Origen', 'render' => fn($p) => '<span class=\"badge ' . ($p->esPTC ? 'bg-purple' : 'bg-info') . '\">' . e($p->origen_horario_label) . '</span>'],
        ['label' => 'Contrato', 'field' => 'tipo_contrato'],
        ['label' => 'Sede', 'render' => fn($p) => e($p->sede?->descripcion ?? $p->id_campus)],
        ['label' => 'Estatus', 'render' => fn($p) => '<span class=\"badge badge--status ' . ($p->status_actual === 'A' ? 'badge--active' : 'badge--inactive') . '\">' . e($p->status_label) . '</span>'],
    ]"
    :rows="$profesores"
    :actions="[
        ['type' => 'link', 'url' => fn($p) => route('academia.profesores.show', $p), 'style' => 'primary', 'title' => 'Ver', 'icon' => 'bi bi-eye'],
        ['type' => 'link', 'url' => fn($p) => route('academia.profesores.horario', $p), 'style' => 'secondary', 'title' => 'Horario', 'icon' => 'bi bi-calendar-week'],
    ]"
    :pagination="$profesores"
    empty-message="No se encontraron profesores"
/>
@endsection
