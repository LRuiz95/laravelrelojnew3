@extends('layouts.admin')

@section('title', 'Asistencia por grupo: ' . $grupo->codigo_grupo)

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Asistencia por grupo</h1>
        <p class="text-muted mb-2">Captura individual de alumnos por clase y fecha.</p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-dark">Grupo {{ $grupo->codigo_grupo }}</span>
            <span class="badge bg-secondary">{{ $grupo->grado }}° · {{ $grupo->nivel }}</span>
            <span class="badge bg-secondary">Turno {{ $grupo->turnoRel?->descripcion ?? $grupo->turno }}</span>
            <span class="badge bg-secondary">{{ $grupo->sede?->descripcion ?? 'Sede sin definir' }}</span>
            <span class="badge bg-light text-dark border">Ciclo {{ $ciclo->label }}</span>
        </div>
    </div>
    <a href="{{ route('academia.grupos.show', [$grupo, 'ciclo_principal' => $ciclo->label]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al grupo
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="ciclo_principal" value="{{ $ciclo->label }}">
            <div class="col-md-4">
                <label class="form-label">Fecha de asistencia</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Clase seleccionada</label>
                <select name="horario_id" class="form-select">
                    @foreach ($horariosPorDia as $dia => $clasesDia)
                        @foreach ($clasesDia as $clase)
                            <option value="{{ $clase->id }}" {{ $claseSeleccionada?->id === $clase->id ? 'selected' : '' }}>
                                {{ $diasSemana[$dia] }} · Sesión {{ $clase->sesion }} · {{ $clase->materia?->nombre_asignatura }} · {{ $clase->aula ?? 'Sin aula' }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-calendar-check me-1"></i> Ver clase y alumnos
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-calendar-week me-2"></i>Horario semanal</span>
        <small class="text-muted">Selecciona una clase para capturar asistencia</small>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach ($diasSemana as $dia => $nombreDia)
                            <th class="text-center" style="min-width: 170px;">{{ $nombreDia }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach ($horariosPorDia as $dia => $clasesDia)
                            <td class="p-2" style="vertical-align: top;">
                                @forelse ($clasesDia as $clase)
                                    <a href="{{ request()->fullUrlWithQuery(['horario_id' => $clase->id, 'fecha' => $fecha]) }}"
                                       class="d-block text-decoration-none border rounded p-2 mb-2 {{ $claseSeleccionada?->id === $clase->id ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                                        <div class="fw-semibold text-dark">Ses. {{ $clase->sesion }} · {{ $clase->sesionBase?->hora_inicio?->format('H:i') }}-{{ $clase->sesionBase?->hora_fin?->format('H:i') }}</div>
                                        <div class="small text-dark">{{ $clase->materia?->nombre_asignatura ?? $clase->clave_asignatura }}</div>
                                        <div class="small text-muted">{{ $clase->profesor?->nombre_completo ?? $clase->clave_profesor }}</div>
                                        <div class="small text-muted">{{ $grupo->sede?->descripcion ?? 'Sede sin definir' }} · Ed. {{ $clase->edificio ?? '—' }} · Aula {{ $clase->aula ?? '—' }}</div>
                                    </a>
                                @empty
                                    <span class="small text-muted">Sin clase</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($claseSeleccionada)
    <div class="kpi-grid mb-4">
        <x-stat-card :icon="'bi-people'" :label="'Alumnos inscritos'" :value="$stats['total_alumnos']" :color="'purple'"><div class="kpi-trend flat">-</div></x-stat-card>
        <x-stat-card :icon="'bi-check-circle'" :label="'Capturados'" :value="$stats['capturadas'] . '/' . $stats['total_alumnos']" :color="'green'"><div class="kpi-trend flat">-</div></x-stat-card>
        <x-stat-card :icon="'bi-check'" :label="'Presentes'" :value="$stats['presentes']" :color="'success'"><div class="kpi-trend flat">-</div></x-stat-card>
        <x-stat-card :icon="'bi-x-circle'" :label="'Ausentes'" :value="$stats['ausentes']" :color="'danger'"><div class="kpi-trend flat">-</div></x-stat-card>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="fw-semibold">{{ $claseSeleccionada->materia?->nombre_asignatura ?? $claseSeleccionada->clave_asignatura }}</div>
            <div class="small text-muted">
                Grupo {{ $grupo->codigo_grupo }} · {{ $claseSeleccionada->profesor?->nombre_completo ?? $claseSeleccionada->clave_profesor }} ·
                {{ $grupo->sede?->descripcion ?? 'Sede sin definir' }} · Edificio {{ $claseSeleccionada->edificio ?? '-' }} · Aula {{ $claseSeleccionada->aula ?? '-' }} · {{ $fecha }}
            </div>
        </div>
        <form method="POST" action="{{ route('academia.grupos.asistencia.guardar') }}">
            @csrf
            <input type="hidden" name="horario_id" value="{{ $claseSeleccionada->id }}">
            <input type="hidden" name="inicial" value="{{ $ciclo->inicial }}">
            <input type="hidden" name="final" value="{{ $ciclo->final }}">
            <input type="hidden" name="periodo" value="{{ $ciclo->periodo }}">
            <input type="hidden" name="codigo_grupo" value="{{ $grupo->codigo_grupo }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">

            <div class="p-3 border-bottom">
                <label class="form-label fw-semibold" for="observacionGrupo">Observación general del grupo</label>
                <textarea id="observacionGrupo" name="observacion_grupo" class="form-control" rows="2" maxlength="1000" placeholder="Incidencia general de la clase o situación del grupo...">{{ $grupoAsistencia?->observaciones }}</textarea>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Control</th><th>Alumno</th><th style="min-width: 180px;">Estado</th><th>Observación individual</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($alumnos as $alumno)
                            @php $asistencia = $asistencias->get($alumno->numero_alumno); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $alumno->numero_alumno }}</td>
                                <td>{{ $alumno->nombre_completo }}</td>
                                <td>
                                    <select name="alumnos[{{ $alumno->numero_alumno }}][estado]" class="form-select form-select-sm" required>
                                        <option value="">Sin capturar</option>
                                        @foreach (['PRESENTE' => 'Presente', 'AUSENTE' => 'Ausente', 'RETARDO' => 'Retardo', 'JUSTIFICADO' => 'Justificado'] as $valor => $etiqueta)
                                            <option value="{{ $valor }}" {{ $asistencia?->estado === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="alumnos[{{ $alumno->numero_alumno }}][observaciones]" class="form-control form-control-sm" maxlength="500" value="{{ $asistencia?->observaciones }}" placeholder="Opcional"></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay alumnos inscritos en este grupo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary" {{ $alumnos->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-save me-1"></i> Guardar asistencia del grupo
                </button>
            </div>
        </form>
    </div>
@else
    <div class="alert alert-info">Este grupo no tiene clases activas registradas en su horario semanal.</div>
@endif
@endsection
