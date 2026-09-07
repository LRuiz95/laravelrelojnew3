@extends('layouts.admin')

@section('title', 'Editar Ciclo: ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Ciclo: {{ $ciclo->label }}</h1>
    <a href="{{ route('academia.ciclos.show', $ciclo) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.ciclos.update', $ciclo) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Año Inicial</label>
                    <input type="number" class="form-control" value="{{ $ciclo->inicial }}" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Año Final</label>
                    <input type="number" class="form-control" value="{{ $ciclo->final }}" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Periodo</label>
                    <input type="number" class="form-control" value="{{ $ciclo->periodo }}" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="{{ $ciclo->descripcion }}" placeholder="Ej: Ciclo 2025-2026">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha Inicial</label>
                    <input type="date" name="fecha_inicial" class="form-control" value="{{ $ciclo->fecha_inicial }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha Final</label>
                    <input type="date" name="fecha_final" class="form-control" value="{{ $ciclo->fecha_final }}">
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" {{ $ciclo->activo ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.ciclos.show', $ciclo) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection