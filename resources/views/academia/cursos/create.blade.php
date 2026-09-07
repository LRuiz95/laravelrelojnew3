@extends('layouts.admin')

@section('title', 'Nuevo Curso')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Curso</h1>
    <a href="{{ route('academia.cursos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.cursos.store') }}" method="POST">
            @csrf
            <input type="hidden" name="inicial" value="{{ $ciclo->inicial }}">
            <input type="hidden" name="final" value="{{ $ciclo->final }}">
            <input type="hidden" name="periodo" value="{{ $ciclo->periodo }}">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Clave Curso <span class="text-danger">*</span></label>
                    <input type="text" name="clave_curso" class="form-control" required maxlength="20" value="{{ old('clave_curso') }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre del Curso <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_curso" class="form-control" required maxlength="100" value="{{ old('nombre_curso') }}">
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
                    <label class="form-label">Turno <span class="text-danger">*</span></label>
                    <select name="turno" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach ($turnos as $t)
                            <option value="{{ $t->turno }}" {{ old('turno') == $t->turno ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sede</label>
                    <select name="id_campus" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($sedes as $s)
                            <option value="{{ $s->id_campus }}" {{ old('id_campus') == $s->id_campus ? 'selected' : '' }}>{{ $s->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.cursos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Crear Curso
                </button>
            </div>
        </form>
    </div>
</div>
@endsection