@extends('layouts.admin')

@section('title', 'Editar Curso: ' . $curso->nombre_curso)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Curso: {{ $curso->nombre_curso }}</h1>
    <a href="{{ route('academia.cursos.show', $curso) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.cursos.update', $curso) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Clave Curso</label>
                    <input type="text" class="form-control" value="{{ $curso->clave_curso }}" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre del Curso <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_curso" class="form-control" required maxlength="100" value="{{ $curso->nombre_curso }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel <span class="text-danger">*</span></label>
                    <select name="nivel" class="form-select" required>
                        @foreach ($niveles as $n)
                            <option value="{{ $n->nivel }}" {{ $curso->nivel == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Turno <span class="text-danger">*</span></label>
                    <select name="turno" class="form-select" required>
                        @foreach ($turnos as $t)
                            <option value="{{ $t->turno }}" {{ $curso->turno == $t->turno ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sede</label>
                    <select name="id_campus" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($sedes as $s)
                            <option value="{{ $s->id_campus }}" {{ $curso->id_campus == $s->id_campus ? 'selected' : '' }}>{{ $s->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" {{ $curso->activo ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.cursos.show', $curso) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection