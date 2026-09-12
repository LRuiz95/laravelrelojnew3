@extends('layouts.admin')

@section('title', 'Permisos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Permisos</h1>
        <p class="text-muted mb-0">Administra módulos, acciones y su asociación con grupos.</p>
    </div>
    <a href="{{ route('permissions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nuevo permiso
    </a>
</div>

<div class="row g-4">
    @forelse ($modules as $module)
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $module->name }}</h2>
                            <p class="text-muted small mb-0">{{ $module->slug }}</p>
                        </div>
                        <span class="badge bg-light text-dark">{{ $module->permissions->count() }} permisos</span>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($module->permissions as $permission)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $permission->name }}</div>
                                        <small class="text-muted">{{ $permission->action }} · {{ $permission->slug }}</small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('permissions.assign-groups', $permission) }}" class="btn btn-outline-primary">Grupos</a>
                                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-warning">Editar</a>
                                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este permiso?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3">Sin permisos definidos.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">No hay módulos configurados.</div>
            </div>
        </div>
    @endforelse
</div>
@endsection
