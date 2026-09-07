@extends('layouts.admin')

@section('title', $plan->nombre_plan)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $plan->nombre_plan }}</h1>
        <p class="text-muted mb-0">ID: {{ $plan->id_plan }} | Nivel: {{ $plan->nivel }} | {{ $plan->duracion_semestres }} semestres</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.planes.edit', $plan) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-journal-bookmark'" :label="'Materias'" :value="$plan->materias_count" :color="'blue'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-clock'" :label="'Horas Teoría'" :value="$plan->materias->sum('horas_teoria')" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-gear'" :label="'Horas Práctica'" :value="$plan->materias->sum('horas_practica')" :color="'orange'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-mortarboard'" :label="'Créditos Totales'" :value="$plan->materias->sum('creditos')" :color="'teal'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Materias por semestre --}}
<div class="card">
    <div class="card-header">
        <span class="fw-bold">Materias del Plan ({{ $plan->materias_count }})</span>
    </div>
    <div class="card-body">
        @if ($materiasPorSemestre->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay materias asignadas a este plan</p>
            </div>
        @else
            @foreach ($materiasPorSemestre as $semestre => $materias)
                <div class="mb-4">
                    <h6 class="fw-semibold text-primary mb-2">Semestre {{ $semestre }}</h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Materia</th>
                                    <th class="text-center">Teoría</th>
                                    <th class="text-center">Práctica</th>
                                    <th class="text-center">Créditos</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($materias as $m)
                                    <tr>
                                        <td class="fw-semibold">{{ $m->clave_asignatura }}</td>
                                        <td>{{ $m->nombre_asignatura }}</td>
                                        <td class="text-center">{{ $m->horas_teoria }}</td>
                                        <td class="text-center">{{ $m->horas_practica }}</td>
                                        <td class="text-center">{{ $m->creditos }}</td>
                                        <td>
                                            <span class="badge {{ $m->tipo === 'obligatoria' ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ $m->tipo }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge--status {{ $m->activa ? 'badge--active' : 'badge--inactive' }}">
                                                {{ $m->activa ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection