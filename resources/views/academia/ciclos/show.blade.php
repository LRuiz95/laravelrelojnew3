@extends('layouts.admin')

@section('title', 'Ciclo: ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $ciclo->label }}</h1>
        <p class="text-muted mb-0">{{ $ciclo->descripcion }}</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.ciclos.edit', $ciclo) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <button class="btn btn-outline-{{ $ciclo->activo ? 'warning' : 'success' }}" 
                onclick="toggleCicloActivo('{{ $ciclo->id }}', {{ $ciclo->activo ? 'false' : 'true' }})">
            <i class="bi bi-{{ $ciclo->activo ? 'pause' : 'play' }} me-1"></i>
            {{ $ciclo->activo ? 'Desactivar' : 'Activar' }}
        </button>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-people'" :label="'Grupos'" :value="$stats['grupos']" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-people-fill'" :label="'Alumnos'" :value="$stats['alumnos']" :color="'blue'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-person-badge'" :label="'Profesores'" :value="$stats['profesores']" :color="'green'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-calendar-week'" :label="'Horarios'" :value="$stats['horarios']" :color="'orange'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-book'" :label="'Cursos'" :value="$stats['cursos']" :color="'teal'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Grupos por nivel/turno --}}
<div class="row g-3 mb-4">
    @foreach ($gruposPorNivelTurno as $g)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ $g->nivel }} - {{ $g->turno }}</span>
                    <span class="badge bg-primary">{{ $g->total }} grupos</span>
                </div>
                <div class="card-body">
                    <a href="{{ route('academia.grupos.index', ['nivel' => $g->nivel, 'turno' => $g->turno]) }}" class="btn btn-sm btn-outline-primary w-100">
                        Ver grupos
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Estadísticas detalladas --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold">Grupos por nivel</span>
            </div>
            <div class="card-body">
                @php
                    $gruposNivel = \App\Models\Academia\Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
                        ->activo()
                        ->selectRaw('nivel, COUNT(*) as total')
                        ->groupBy('nivel')
                        ->orderBy('nivel')
                        ->get();
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Nivel</th><th class="text-end">Grupos</th></tr></thead>
                        <tbody>
                            @foreach ($gruposNivel as $g)
                                <tr>
                                    <td>{{ $g->nivel }}</td>
                                    <td class="text-end fw-semibold">{{ $g->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold">Grupos por turno</span>
            </div>
            <div class="card-body">
                @php
                    $gruposTurno = \App\Models\Academia\Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
                        ->activo()
                        ->selectRaw('turno, COUNT(*) as total')
                        ->groupBy('turno')
                        ->orderBy('turno')
                        ->get();
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Turno</th><th class="text-end">Grupos</th></tr></thead>
                        <tbody>
                            @foreach ($gruposTurno as $g)
                                <tr>
                                    <td>{{ $g->turno }}</td>
                                    <td class="text-end fw-semibold">{{ $g->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection