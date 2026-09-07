@extends('layouts.admin')

@section('title', 'Cursos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Cursos</h1>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($cursos->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay cursos registrados para este ciclo</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Nivel</th>
                            <th>Turno</th>
                            <th>Sede</th>
                            <th class="text-center">Materias</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cursos as $c)
                            <tr>
                                <td class="fw-semibold">{{ $c->clave_curso }}</td>
                                <td>{{ $c->nombre_curso }}</td>
                                <td>{{ $c->nivel }}</td>
                                <td>{{ $c->turno }}</td>
                                <td>{{ $c->sede?->descripcion ?? $c->id_campus }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $c->materias_count }}</span>
                                </td>
                                <td>
                                    <span class="badge badge--status {{ $c->activo ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('academia.cursos.show', $c) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{ $cursos->withQueryString()->links() }}
@endsection
