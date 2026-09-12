@extends('layouts.admin')

@section('title', 'Grupos de permisos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Grupos de permisos</h1>
        <p class="text-muted mb-0">Define perfiles reutilizables por área, responsable o tipo de acceso.</p>
    </div>
    <a href="{{ route('permission-groups.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nuevo grupo
    </a>
</div>

<div class="row g-4">
    @forelse ($permissionGroups as $group)
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $group->name }}</h2>
                            <p class="text-muted small mb-0">{{ $group->description ?? 'Sin descripción' }}</p>
                        </div>
                        @if ($group->is_default)
                            <span class="badge bg-primary-subtle text-primary">Predeterminado</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-light text-dark">{{ $group->permissions_count }} permisos</span>
                        <span class="badge bg-light text-dark">{{ $group->employees->count() }} empleados</span>
                        <span class="badge bg-light text-dark">{{ $group->profesores->count() }} profesores</span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('permission-groups.assign-employees', $group) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-people me-1"></i> Asignar empleados
                        </a>
                        <a href="{{ route('permission-groups.assign-profesores', $group) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-person-badge me-1"></i> Asignar profesores
                        </a>
                        <a href="{{ route('permission-groups.edit', $group) }}" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </a>

                        <form action="{{ route('permission-groups.destroy', $group) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este grupo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    No hay grupos de permisos configurados.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
