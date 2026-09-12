@extends('layouts.admin')

@section('title', 'Incidencias')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    @if (auth()->user()->canAccessModule('incidencias', 'create'))
        <a href="{{ route('incidencias.create') }}" class="btn btn-primary">Nueva incidencia</a>
    @endif
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('incidencias.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Asunto, tipo, motivo, número">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ $estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="aprobada" {{ $estado === 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                    <option value="rechazada" {{ $estado === 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="{{ route('incidencias.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Persona</th>
                        <th>Área</th>
                        <th>Puesto</th>
                        <th>Director / Responsable</th>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidencias as $incidencia)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $incidencia->empleado ? 'Empleado' : 'Profesor' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    @if ($incidencia->empleado)
                                        {{ $incidencia->empleado->name }}
                                    @else
                                        {{ trim("{$incidencia->profesor?->paterno} {$incidencia->profesor?->materno} {$incidencia->profesor?->nombre_profesor}") }}
                                    @endif
                                </div>
                                <small class="text-muted">
                                    {{ $incidencia->numero_empleado ?? ($incidencia->empleado?->user_id ?? $incidencia->profesor?->clave_profesor ?? '—') }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $incidencia->area?->identificador ?? '—' }}</div>
                                <small class="text-muted">{{ $incidencia->area?->descripcion ?? 'Sin descripción' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $incidencia->puesto?->identificador ?? '—' }}</div>
                                <small class="text-muted">{{ $incidencia->puesto?->descripcion ?? 'Sin descripción' }}</small>
                            </td>
                            <td>
                                @php
                                    $directorNombre = $incidencia->director?->name ?? $incidencia->responsableArea?->name ?? '—';
                                @endphp
                                <div class="fw-semibold">{{ $directorNombre }}</div>
                                <small class="text-muted">
                                    {{ $incidencia->director?->user_id ?? $incidencia->responsableArea?->user_id ?? 'Sin responsable' }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $incidencia->asunto }}</div>
                                <small class="text-muted">{{ $incidencia->tipo_justificacion }}</small>
                            </td>
                            <td>{{ $incidencia->fecha_justificacion->format('d/m/Y') }}</td>
                            <td>
                                @php
                                $estadoBadge = match($incidencia->estado) {
                                    'aprobada' => 'bg-success',
                                    'rechazada' => 'bg-danger',
                                    default => 'bg-warning text-dark'
                                };
                                @endphp
                                <span class="badge {{ $estadoBadge }}">{{ $incidencia->estado }}</span>
                            </td>
                            <td>
                                @if (auth()->user()->canAccessModule('incidencias', 'approve'))
                                    <div class="btn-group btn-group-sm" role="group">
                                        <form method="POST" action="{{ route('incidencias.estado', $incidencia) }}">
                                            @csrf
                                            <input type="hidden" name="estado" value="aprobada">
                                            <button type="submit" class="btn btn-outline-success">Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('incidencias.estado', $incidencia) }}">
                                            @csrf
                                            <input type="hidden" name="estado" value="rechazada">
                                            <button type="submit" class="btn btn-outline-danger">Rechazar</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No hay incidencias registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $incidencias->links() }}
</div>
@endsection
