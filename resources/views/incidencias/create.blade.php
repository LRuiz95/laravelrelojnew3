@extends('layouts.admin')

@section('title', 'Registrar incidencia')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('incidencias.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="tipo_persona" class="form-label">Tipo de persona</label>
                    <select id="tipo_persona" name="tipo_persona" class="form-select @error('tipo_persona') is-invalid @enderror" required>
                        <option value="empleado" {{ old('tipo_persona', 'empleado') === 'empleado' ? 'selected' : '' }}>Empleado</option>
                        <option value="profesor" {{ old('tipo_persona') === 'profesor' ? 'selected' : '' }}>Profesor</option>
                    </select>
                    @error('tipo_persona') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4" data-person-type="empleado">
                    <label for="empleado_id" class="form-label">Empleado</label>
                    <select id="empleado_id" name="empleado_id" class="form-select @error('empleado_id') is-invalid @enderror">
                        <option value="">Selecciona un empleado</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}" {{ old('empleado_id') == $empleado->id ? 'selected' : '' }}>
                                {{ $empleado->name }} ({{ $empleado->user_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('empleado_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 d-none" data-person-type="profesor">
                    <label for="profesor_clave" class="form-label">Profesor</label>
                    <select id="profesor_clave" name="profesor_clave" class="form-select @error('profesor_clave') is-invalid @enderror">
                        <option value="">Selecciona un profesor</option>
                        @foreach ($profesores as $profesor)
                            <option value="{{ $profesor->clave_profesor }}" {{ old('profesor_clave') == $profesor->clave_profesor ? 'selected' : '' }}>
                                {{ trim("{$profesor->paterno} {$profesor->materno} {$profesor->nombre_profesor}") }}
                            </option>
                        @endforeach
                    </select>
                    @error('profesor_clave') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="asunto" class="form-label">Asunto</label>
                    <input type="text" id="asunto" name="asunto" value="{{ old('asunto') }}" class="form-control @error('asunto') is-invalid @enderror" required>
                    @error('asunto') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="tipo_justificacion" class="form-label">Tipo de justificación</label>
                    <input type="text" id="tipo_justificacion" name="tipo_justificacion" value="{{ old('tipo_justificacion') }}" class="form-control @error('tipo_justificacion') is-invalid @enderror" required>
                    @error('tipo_justificacion') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="fecha_justificacion" class="form-label">Fecha que se justifica</label>
                    <input type="date" id="fecha_justificacion" name="fecha_justificacion" value="{{ old('fecha_justificacion', now()->format('Y-m-d')) }}" class="form-control @error('fecha_justificacion') is-invalid @enderror" required>
                    @error('fecha_justificacion') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="area_id" class="form-label">Área</label>
                    <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                        <option value="">Sin área</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                {{ $area->identificador }} - {{ $area->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('area_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="puesto_id" class="form-label">Puesto</label>
                    <select id="puesto_id" name="puesto_id" class="form-select @error('puesto_id') is-invalid @enderror">
                        <option value="">Sin puesto</option>
                        @foreach ($puestos as $puesto)
                            <option value="{{ $puesto->id }}" {{ old('puesto_id') == $puesto->id ? 'selected' : '' }}>
                                {{ $puesto->identificador }} - {{ $puesto->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('puesto_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="director_id" class="form-label">Director de carrera / responsable</label>
                    <select id="director_id" name="director_id" class="form-select @error('director_id') is-invalid @enderror">
                        <option value="">Sin director</option>
                        @foreach ($directores as $director)
                            <option value="{{ $director->id }}" {{ old('director_id') == $director->id ? 'selected' : '' }}>
                                {{ $director->name }} ({{ $director->user_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('director_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label for="motivo" class="form-label">Motivo</label>
                    <textarea id="motivo" name="motivo" rows="4" class="form-control @error('motivo') is-invalid @enderror" required>{{ old('motivo') }}</textarea>
                    @error('motivo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('incidencias.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar incidencia</button>
            </div>
        </form>
    </div>
</div>

<script>
    const tipoPersona = document.getElementById('tipo_persona');
    const tipoPersonaBlocks = document.querySelectorAll('[data-person-type]');

    function syncTipoPersona() {
        const value = tipoPersona.value;
        tipoPersonaBlocks.forEach((element) => {
            const show = element.dataset.personType === value;
            element.classList.toggle('d-none', !show);
            const field = element.querySelector('select');
            if (field) {
                field.disabled = !show;
                field.required = show;
            }
        });
    }

    tipoPersona.addEventListener('change', syncTipoPersona);
    syncTipoPersona();
</script>
@endsection
