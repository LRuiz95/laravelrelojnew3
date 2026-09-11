@extends('layouts.admin')

@section('title', 'Nueva área')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Nueva área</h1>
        <p class="text-muted mb-0">Crea un área con identificador, descripción y responsable.</p>
    </div>
    <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('areas.store') }}" method="POST">
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
                    <label class="form-label" for="empleado_responsable_id">Empleado responsable</label>
                    <select id="empleado_responsable_id" name="empleado_responsable_id" class="form-select">
                        <option value="">Sin responsable</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}" {{ old('empleado_responsable_id') == $empleado->id ? 'selected' : '' }}>
                                {{ $empleado->name }} ({{ $empleado->user_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('areas.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar área</button>
            </div>
        </form>
    </div>
</div>
@endsection
