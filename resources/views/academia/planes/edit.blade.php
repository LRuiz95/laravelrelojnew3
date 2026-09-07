@extends('layouts.admin')

@section('title', 'Editar Plan: ' . $plan->nombre_plan)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Plan: {{ $plan->nombre_plan }}</h1>
    <a href="{{ route('academia.planes.show', $plan) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.planes.update', $plan) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Plan</label>
                    <input type="number" class="form-control" value="{{ $plan->id_plan }}" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Nombre del Plan <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_plan" class="form-control" required maxlength="100" value="{{ $plan->nombre_plan }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel <span class="text-danger">*</span></label>
                    <select name="nivel" class="form-select" required>
                        @foreach ($niveles as $n)
                            <option value="{{ $n->nivel }}" {{ $plan->nivel == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modalidad</label>
                    <input type="text" name="modalidad" class="form-control" maxlength="20" value="{{ $plan->modalidad }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duración (semestres)</label>
                    <input type="number" name="duracion_semestres" class="form-control" min="1" max="12" value="{{ $plan->duracion_semestres }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" {{ $plan->activo ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.planes.show', $plan) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection