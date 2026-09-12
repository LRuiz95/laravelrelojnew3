@extends('layouts.admin')

@section('title', 'Editar grupo de permisos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Editar grupo de permisos</h1>
        <p class="text-muted mb-0">Actualiza el nombre, descripción y tipo del grupo.</p>
    </div>
    <a href="{{ route('permission-groups.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('permission-groups.update', $permissionGroup) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $permissionGroup->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" {{ old('is_default', $permissionGroup->is_default) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Grupo predeterminado</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $permissionGroup->description) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('permission-groups.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
