@extends('layouts.admin')

@section('title', $profesor->nombre_completo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $profesor->nombre_completo }}</h1>
        <p class="text-muted mb-0">
            {{ $profesor->clave_profesor }} | {{ $profesor->departamento ?? '—' }} · {{ $profesor->origen_horario_label }}
        </p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.profesores.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="{{ route('academia.profesores.horario', $profesor) }}" class="btn btn-outline-primary">
            <i class="bi bi-calendar-week me-1"></i> Horario
        </a>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-calendar-week'" :label="'Total clases'" :value="$stats['total_clases']" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-person-badge'" :label="'PTC'" :value="$stats['ptc']" :color="'blue'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-book'" :label="'PA'" :value="$stats['pa']" :color="'teal'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Datos personales --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><span class="fw-bold">Datos Personales</span></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">RFC</dt><dd class="col-sm-8">{{ $profesor->rfc ?? '—' }}</dd>
                    <dt class="col-sm-4">CURP</dt><dd class="col-sm-8">{{ $profesor->curp ?? '—' }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $profesor->email ?? '—' }}</dd>
                    <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8">{{ $profesor->telefono ?? '—' }}</dd>
                    <dt class="col-sm-4">Ingreso</dt><dd class="col-sm-8">{{ $profesor->fecha_ingreso?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Sede</dt><dd class="col-sm-8">{{ $profesor->sede?->descripcion ?? $profesor->id_campus }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><span class="fw-bold">Contratos</span></div>
            <div class="card-body">
                @if ($contratos->isEmpty())
                    <p class="text-muted mb-0">No se encontraron contratos</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Contrato</th><th>Tipo</th><th>Periodo</th></tr></thead>
                            <tbody>
                                @foreach ($contratos as $c)
                                    <tr>
                                        <td>{{ $c['contrato'] ?? '—' }}</td>
                                        <td><span class="badge bg-secondary">{{ $c['tipo'] ?? '—' }}</span></td>
                                        <td>{{ $c['periodo'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Horarios del ciclo --}}
<div class="card mb-4">
    <div class="card-header"><span class="fw-bold">Horarios del Ciclo</span></div>
    <div class="card-body p-0">
        @if ($horarios->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 mb-2"></i>
                <p>No hay horarios asignados en este ciclo</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Sesión</th>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Aula</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($horarios as $dia => $clases)
                            @foreach ($clases as $i => $cl)
                                <tr>
                                    @if ($i === 0)
                                        <td rowspan="{{ $clases->count() }}" class="fw-semibold align-middle">
                                            {{ ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$dia - 1] ?? $dia }}
                                        </td>
                                    @endif
                                    <td>Ses. {{ $cl->sesion }}</td>
                                    <td><span class="badge bg-secondary">{{ $cl->grupo?->codigo_grupo ?? '—' }}</span></td>
                                    <td>{{ $cl->materia?->label ?? $cl->clave_asignatura }}</td>
                                    <td><small class="text-muted">{{ $cl->ubicacion ?? '—' }}</small></td>
                                    <td><span class="badge {{ $cl->tipoClase === 'PTC' ? 'bg-purple' : 'bg-info' }}">{{ $cl->tipoClase }}</span></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
