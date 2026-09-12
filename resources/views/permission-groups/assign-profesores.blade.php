@extends('layouts.admin')

@section('title', 'Asignar profesores al grupo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Asignar profesores</h1>
        <p class="text-muted mb-0">Grupo: {{ $permissionGroup->name }}</p>
    </div>
    <a href="{{ route('permission-groups.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('permission-groups.save-profesores', $permissionGroup) }}" method="POST">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-profesores" class="form-check-input"></th>
                            <th>Nombre</th>
                            <th>Clave</th>
                            <th>Departamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profesores as $profesor)
                            <tr>
                                <td>
                                    <input type="checkbox" name="profesor_keys[]" value="{{ $profesor->clave_profesor }}" class="form-check-input profesor-checkbox" {{ in_array($profesor->clave_profesor, $selectedProfesorKeys, true) ? 'checked' : '' }}>
                                </td>
                                <td>{{ $profesor->nombre_completo }}</td>
                                <td>{{ $profesor->clave_profesor }}</td>
                                <td>{{ $profesor->departamento ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay profesores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">Guardar asignación</button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
    document.getElementById('select-all-profesores')?.addEventListener('change', function () {
        document.querySelectorAll('.profesor-checkbox').forEach(function (checkbox) {
            checkbox.checked = this.checked;
        }, this);
    });
</script>
@endsection
@endsection
