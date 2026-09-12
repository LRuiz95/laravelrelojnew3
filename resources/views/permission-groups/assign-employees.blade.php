@extends('layouts.admin')

@section('title', 'Asignar empleados al grupo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Asignar empleados</h1>
        <p class="text-muted mb-0">Grupo: {{ $permissionGroup->name }}</p>
    </div>
    <a href="{{ route('permission-groups.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('permission-groups.save-employees', $permissionGroup) }}" method="POST">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-employees" class="form-check-input"></th>
                            <th>Nombre</th>
                            <th>ID</th>
                            <th>Área</th>
                            <th>Puesto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="form-check-input employee-checkbox" {{ in_array($employee->id, $selectedEmployeeIds, true) ? 'checked' : '' }}>
                                </td>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->user_id }}</td>
                                <td>{{ $employee->area?->identificador ?? '—' }}</td>
                                <td>{{ $employee->puesto?->identificador ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay empleados registrados.</td>
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
    document.getElementById('select-all-employees')?.addEventListener('change', function () {
        document.querySelectorAll('.employee-checkbox').forEach(function (checkbox) {
            checkbox.checked = this.checked;
        }, this);
    });
</script>
@endsection
@endsection
