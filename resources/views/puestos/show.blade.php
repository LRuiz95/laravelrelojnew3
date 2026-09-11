@extends('layouts.admin')

@section('title', $puesto->identificador)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">{{ $puesto->identificador }}</h1>
        <p class="text-muted mb-0">{{ $puesto->descripcion ?? 'Sin descripción' }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('puestos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('puestos.edit', $puesto) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Editar</a>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Detalle del puesto</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Identificador</dt>
                    <dd class="col-sm-7">{{ $puesto->identificador }}</dd>

                    <dt class="col-sm-5">Descripción</dt>
                    <dd class="col-sm-7">{{ $puesto->descripcion ?? '—' }}</dd>

                    <dt class="col-sm-5">Área</dt>
                    <dd class="col-sm-7">{{ $puesto->area?->identificador ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Empleados vinculados</strong></div>
            <div class="card-body">
                @if ($puesto->empleados->isEmpty())
                    <p class="text-muted mb-0">No hay empleados vinculados a este puesto.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($puesto->empleados as $empleado)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ $empleado->name }}</strong><br>
                                    <small class="text-muted">{{ $empleado->user_id }}</small>
                                </span>
                                <a href="{{ route('employees.edit', $empleado) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
