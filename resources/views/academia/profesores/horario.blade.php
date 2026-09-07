@extends('layouts.admin')

@section('title', 'Horario: {{ $profesor->nombre_completo }} - {{ $ciclo->label }}')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Horario: {{ $profesor->nombre_completo }}</h1>
        <p class="text-muted mb-0">
            {{ $profesor->clave_profesor }} | {{ $profesor->origen_horario_label }} | {{ $profesor->departamento }}
        </p>
    </div>
    <a href="{{ route('academia.profesores.show', $profesor) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-calendar-week'" :label="'Total clases'" :value="{{ $stats['total_clases'] }}" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-person-badge'" :label="'PTC (Hora Docente)'" :value="{{ $stats['ptc'] }}" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-book'" :label="'PA (Carga Asignada)'" :value="{{ $stats['pa'] }}" :color="'blue'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Grid horario --}}
@if (empty($grid))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-calendar-x fs-1 mb-2"></i>
            <p>No hay horarios programados para este ciclo</p>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Sesión / Hora</th>
                            @foreach ($dias as $dia)
                                <th class="text-center">
                                    <span class="badge {{ in_array($dia, [6,7]) ? 'bg-purple' : 'bg-primary' }} me-1">
                                        {{ ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'][$dia-1] }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sesiones as $sesion)
                            <tr>
                                <td class="text-nowrap small text-muted">
                                    <div class="fw-semibold">Ses. {{ $sesion }}</div>
                                    @if (isset($grid["{$sesion}-1"]))
                                        <div>{{ $grid["{$sesion}-1"][0]['inicio'] ?? '' }} - {{ $grid["{$sesion}-1"][0]['fin'] ?? '' }}</div>
                                    @endif
                                </td>
                                @foreach ($dias as $dia)
                                    <td class="align-middle">
                                        @if (isset($grid["{$dia}-{$sesion}"]))
                                            @foreach ($grid["{$dia}-{$sesion}"] as $clase)
                                                <div class="mb-2 p-2 rounded bg-light border">
                                                    <div class="fw-semibold small">{{ $clase['materia'] }}</div>
                                                    <div class="small text-muted">{{ $clase['grupo'] }} · {{ $clase['aula'] }}</div>
                                                    <span class="badge {{ $clase['tipo'] === 'PTC' ? 'bg-purple' : 'bg-info' }}">{{ $clase['tipo'] }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection