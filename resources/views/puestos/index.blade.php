@extends('layouts.admin')

@section('title', 'Puestos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Puestos</h1>
        <p class="text-muted mb-0">Catálogo de puestos y su vinculación con áreas.</p>
    </div>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('puestos.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo puesto
        </a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Identificador</th>
                        <th>Descripción</th>
                        <th>Área</th>
                        <th>Empleados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($puestos as $puesto)
                        <tr>
                            <td><span class="fw-semibold">{{ $puesto->identificador }}</span></td>
                            <td>{{ $puesto->descripcion ?? '—' }}</td>
                            <td>{{ $puesto->area?->identificador ?? '—' }}</td>
                            <td>{{ $puesto->empleados->count() }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('puestos.show', $puesto) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('puestos.edit', $puesto) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('puestos.destroy', $puesto) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este puesto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay puestos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
