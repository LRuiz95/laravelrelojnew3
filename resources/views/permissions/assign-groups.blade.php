@extends('layouts.admin')

@section('title', 'Asignar grupos al permiso')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Asignar grupos</h1>
        <p class="text-muted mb-0">Permiso: {{ $permission->name }}</p>
    </div>
    <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('permissions.save-groups', $permission) }}" method="POST">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-groups" class="form-check-input"></th>
                            <th>Grupo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr>
                                <td>
                                    <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" class="form-check-input group-checkbox" {{ in_array($group->id, $selectedGroupIds, true) ? 'checked' : '' }}>
                                </td>
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->description ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No hay grupos disponibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">Guardar grupos</button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
    document.getElementById('select-all-groups')?.addEventListener('change', function () {
        document.querySelectorAll('.group-checkbox').forEach(function (checkbox) {
            checkbox.checked = this.checked;
        }, this);
    });
</script>
@endsection
@endsection
