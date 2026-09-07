@extends('layouts.admin')

@section('title', 'Nuevo Plan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Plan de Estudio</h1>
    <a href="{{ route('academia.planes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.planes.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Plan <span class="text-danger">*</span></label>
                    <input type="number" name="id_plan" class="form-control" required min="1" value="{{ old('id_plan') }}">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Nombre del Plan <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_plan" class="form-control" required maxlength="100" value="{{ old('nombre_plan') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel <span class="text-danger">*</span></label>
                    <select name="nivel" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach ($niveles as $n)
                            <option value="{{ $n->nivel }}" {{ old('nivel') == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modalidad</label>
                    <input type="text" name="modalidad" class="form-control" maxlength="20" value="{{ old('modalidad') }}" placeholder="Ej: Escolarizada, Mixta, etc.">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duración (semestres)</label>
                    <input type="number" name="duracion_semestres" class="form-control" min="1" max="12" value="{{ old('duracion_semestres') }}">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.planes.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Crear Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection