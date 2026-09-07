@extends('layouts.admin')

@section('title', 'Conflictos de Aula')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Conflictos de Aula</h1>
    <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar me-1"></i> Cambiar ciclo
    </a>
</div>

@if (empty($conflictos))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
            <p>No se detectaron conflictos de aula en el ciclo {{ $ciclo->label }}</p>
        </div>
    </div>
@else
    <div class="card mb-4">
        <div class="card-header bg-danger-subtle">
            <span class="fw-bold text-danger">Se detectaron {{ count($conflictos) }} conflicto(s) de aula</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Sesión</th>
                            <th>Sede</th>
                            <th>Edificio</th>
                            <th>Aula</th>
                            <th>Clases en conflicto</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conflictos as $c)
                            <tr>
                                <td>{{ ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$c->dia-1] }}</td>
                                <td>{{ $c->sesion }}</td>
                                <td>{{ $c->id_campus }}</td>
                                <td>{{ $c->edificio }}</td>
                                <td>{{ $c->aula }}</td>
                                <td><span class="badge bg-danger">{{ $c->total }}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalleConflicto({{ $c->dia }}, {{ $c->sesion }}, '{{ $c->id_campus }}', '{{ $c->edificio }}', '{{ $c->aula }}')">
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection