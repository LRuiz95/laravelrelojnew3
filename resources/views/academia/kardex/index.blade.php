@extends('layouts.admin')

@section('title', 'Kardex')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kardex de Alumnos</h1>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>

{{-- Buscador --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('academia.kardex.show') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Buscar alumno</label>
                <input type="text" name="buscar" class="form-control" placeholder="Número de control, nombre, CURP..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

{{-- Accesos rápidos --}}
<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('academia.kardex.show') }}" class="card text-decoration-none border-primary h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-search fs-1 text-primary mb-2"></i>
                <h5 class="card-title text-dark">Consultar Kardex</h5>
                <p class="card-text text-muted small">Busca un alumno para ver su kardex del ciclo actual</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('academia.kardex.historial') }}" class="card text-decoration-none border-info h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-clock-history fs-1 text-info mb-2"></i>
                <h5 class="card-title text-dark">Historial</h5>
                <p class="card-text text-muted small">Consulta el historial completo de un alumno</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('academia.kardex.print') }}" class="card text-decoration-none border-success h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-printer fs-1 text-success mb-2"></i>
                <h5 class="card-title text-dark">Imprimir Kardex</h5>
                <p class="card-text text-muted small">Genera un PDF con el kardex de un alumno</p>
            </div>
        </a>
    </div>
</div>
@endsection
