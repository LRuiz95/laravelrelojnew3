@extends('layouts.admin')

@section('title', $area->identificador)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">{{ $area->identificador }}</h1>
        <p class="text-muted mb-0">{{ $area->descripcion ?? 'Sin descripción' }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('areas.edit', $area) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Editar</a>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Detalle del área</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Identificador</dt>
                    <dd class="col-sm-7">{{ $area->identificador }}</dd>

                    <dt class="col-sm-5">Descripción</dt>
                    <dd class="col-sm-7">{{ $area->descripcion ?? '—' }}</dd>

                    <dt class="col-sm-5">Responsable</dt>
                    <dd class="col-sm-7">{{ $area->empleadoResponsable?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Puestos asociados</strong></div>
            <div class="card-body">
                @if ($area->puestos->isEmpty())
                    <p class="text-muted mb-0">No hay puestos vinculados a esta área.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($area->puestos as $puesto)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ $puesto->identificador }}</strong><br>
                                    <small class="text-muted">{{ $puesto->descripcion ?? 'Sin descripción' }}</small>
                                </span>
                                <a href="{{ route('puestos.show', $puesto) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
