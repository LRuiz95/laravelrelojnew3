@extends('layouts.admin')

@section('title', 'Nuevo puesto')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Nuevo puesto</h1>
        <p class="text-muted mb-0">Registra un puesto y su relación con un área.</p>
    </div>
    <a href="{{ route('puestos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('puestos.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="identificador">Identificador</label>
                    <input type="text" id="identificador" name="identificador" class="form-control" value="{{ old('identificador') }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <input type="text" id="descripcion" name="descripcion" class="form-control" value="{{ old('descripcion') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="area_id">Área</label>
                    <select id="area_id" name="area_id" class="form-select">
                        <option value="">Sin área</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                {{ $area->identificador }} - {{ $area->descripcion ?? 'Sin descripción' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('puestos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar puesto</button>
            </div>
        </form>
    </div>
</div>
@endsection
