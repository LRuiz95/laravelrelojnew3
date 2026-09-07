@extends('layouts.admin')

@section('title', 'Nuevo Ciclo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Ciclo Escolar</h1>
    <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('academia.ciclos.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Año Inicial <span class="text-danger">*</span></label>
                    <input type="number" name="inicial" class="form-control" required min="2000" max="2100" value="{{ old('inicial', now()->year) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Año Final <span class="text-danger">*</span></label>
                    <input type="number" name="final" class="form-control" required min="2000" max="2100" value="{{ old('final', now()->year) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Periodo <span class="text-danger">*</span></label>
                    <select name="periodo" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="1">Semestral</option>
                        <option value="2">Cuatrimestral</option>
                        <option value="3" selected>Anual</option>
                        <option value="4">Otro</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" placeholder="Ej: Ciclo 2025-2026" value="{{ old('descripcion') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha Inicial</label>
                    <input type="date" name="fecha_inicial" class="form-control" value="{{ old('fecha_inicial') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha Final</label>
                    <input type="date" name="fecha_final" class="form-control" value="{{ old('fecha_final') }}">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academia.ciclos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Crear Ciclo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection