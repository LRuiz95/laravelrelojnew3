@extends('layouts.admin')

@section('title', 'Asistencia: ' . $grupo->codigo_grupo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Asistencia: {{ $grupo->codigo_grupo }}</h1>
        <p class="text-muted mb-0">{{ $grupo->grado }}° · {{ $grupo->turnoRel?->descripcion }} · {{ $grupo->nivel }}</p>
    </div>
    <a href="{{ route('academia.grupos.show', $grupo) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al grupo
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Día</label>
                <select name="dia" class="form-select">
                    @foreach ([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $d => $label)
                        <option value="{{ $d }}" {{ $dia == $d ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-calendar-week'" :label="'Total clases'" :value="$stats['total_clases']" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-check-circle'" :label="'Capturadas'" :value="$stats['capturadas'] . '/' . $stats['total_clases']" :color="'green'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-check'" :label="'Presentes'" :value="$stats['presentes']" :color="'success'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-x-circle'" :label="'Ausentes'" :value="$stats['ausentes']" :color="'danger'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-clock'" :label="'Retardos'" :value="$stats['retardos']" :color="'warning'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-shield-check'" :label="'Justificados'" :value="$stats['justificados']" :color="'info'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Grid de asistencia --}}
<div class="card">
    <div class="card-body p-0">
        @if (empty($horarios))
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 mb-2"></i>
                <p>No hay clases programadas para este día</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sesión / Hora</th>
                            <th>Profesor / Materia</th>
                            <th>Grupo</th>
                            <th>Aula</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($horarios as $clase)
                            <tr class="asist-row">
                                <td class="asist-session">
                                    <strong>Ses. {{ $clase['SESION'] }}</strong>
                                    @if ($clase['SESION_INI'])
                                        <br><small class="text-muted">{{ $clase['SESION_INI'] }} - {{ $clase['SESION_FIN'] }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $clase['NOMBREPROFESOR'] }}</div>
                                    <div class="text-muted small">{{ $clase['MATERIA_NOMBRE'] }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ str_starts_with($clase['GRUPO_TURNO'] ?? '', 'V') ? 'bg-purple' : 'bg-warning' }}">
                                        {{ $clase['GRADO'] }}-{{ $clase['TURNO'] ?? '' }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ $clase['EDIFICIO'] }} {{ $clase['AULA'] }}</small></td>
                                <td>
                                    <span class="asist-badge asist-badge--{{ strtolower(str_replace(' ', '-', $clase['ASISTENCIA_ESTADO'] ?? 'SIN_CAPTURA')) }}">
                                        {{ $clase['ASISTENCIA_ESTADO'] ?? 'Sin capturar' }}
                                    </span>
                                    @if ($clase['ASISTENCIA_OBS'])
                                        <div class="small text-muted" title="{{ $clase['ASISTENCIA_OBS'] }}">{{ Str::limit($clase['ASISTENCIA_OBS'], 40) }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="openAsistDrawer({{ json_encode($clase, JSON_HEX_APOS | JSON_HEX_TAG) }})">
                                        {{ $clase['ASISTENCIA_ESTADO'] ? 'Editar' : 'Capturar' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Drawer captura --}}
<x-drawer id="asistDrawer" title="Capturar Asistencia" size="lg">
    <form method="POST" action="{{ route('academia.grupos.asistencia.guardar', $grupo) }}" id="asistForm">
        @csrf
        <input type="hidden" name="dia" id="acDia" value="{{ $dia }}">
        <input type="hidden" name="fecha" id="acFecha" value="{{ $fecha }}">
        <input type="hidden" name="sesion" id="acSesion">
        <input type="hidden" name="clave_profesor" id="acProfesor">
        <input type="hidden" name="clave_asignatura" id="acAsignatura">
        <input type="hidden" name="codigo_grupo" id="acGrupo">
        <input type="hidden" name="inicial" id="acInicial" value="{{ $ciclo->inicial }}">
        <input type="hidden" name="final" id="acFinal" value="{{ $ciclo->final }}">
        <input type="hidden" name="periodo" id="acPeriodo" value="{{ $ciclo->periodo }}">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Profesor</label>
                <input type="text" id="acNombre" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Materia</label>
                <input type="text" id="acMateria" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Grupo</label>
                <input type="text" id="acGrupoLabel" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Aula</label>
                <input type="text" id="acAula" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Sesión / Hora</label>
                <input type="text" id="acHora" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha</label>
                <input type="text" id="acFechaLabel" class="form-control" readonly>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Estado de asistencia</label>
            <div class="asist-estado-btns">
                <label class="asist-estado-btn asist-estado-btn--presente">
                    <input type="radio" name="estado" value="PRESENTE" required>
                    <span><i class="bi bi-check-circle me-1"></i>Presente</span>
                </label>
                <label class="asist-estado-btn asist-estado-btn--ausente">
                    <input type="radio" name="estado" value="AUSENTE">
                    <span><i class="bi bi-x-circle me-1"></i>Ausente</span>
                </label>
                <label class="asist-estado-btn asist-estado-btn--retardo">
                    <input type="radio" name="estado" value="RETARDO">
                    <span><i class="bi bi-clock me-1"></i>Retardo</span>
                </label>
                <label class="asist-estado-btn asist-estado-btn--justificado">
                    <input type="radio" name="estado" value="JUSTIFICADO">
                    <span><i class="bi bi-shield-check me-1"></i>Justificado</span>
                </label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="acObs">Observaciones (opcional)</label>
            <textarea name="observaciones" id="acObs" rows="3" maxlength="500" class="form-control" placeholder="Escribe una nota..."></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="closeAsistDrawer()">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar asistencia</button>
        </div>
    </form>
</x-drawer>

<script>
function openAsistDrawer(clase) {
    document.getElementById('acDia').value = clase.DIA;
    document.getElementById('acFecha').value = '{{ $fecha }}';
    document.getElementById('acSesion').value = clase.SESION;
    document.getElementById('acProfesor').value = clase.CLAVEPROFESOR;
    document.getElementById('acAsignatura').value = clase.CLAVEASIGNATURA;
    document.getElementById('acGrupo').value = clase.CODIGO_GRUPO;
    document.getElementById('acInicial').value = clase.INICIAL;
    document.getElementById('acFinal').value = clase.FINAL;
    document.getElementById('acPeriodo').value = clase.PERIODO;
    document.getElementById('acNombre').value = clase.NOMBREPROFESOR;
    document.getElementById('acMateria').value = clase.MATERIA_NOMBRE;
    document.getElementById('acGrupoLabel').value = clase.GRUPO_LABEL;
    document.getElementById('acAula').value = clase.AULA;
    document.getElementById('acHora').value = clase.HORA;
    document.getElementById('acFechaLabel').value = '{{ $fecha }}';

    // Seleccionar radio si ya tiene estado
    const estado = clase.ASISTENCIA_ESTADO || '';
    document.querySelectorAll('input[name="estado"]').forEach(r => {
        r.checked = r.value === estado;
    });
    document.getElementById('acObs').value = clase.ASISTENCIA_OBS || '';

    const drawer = new bootstrap.Offcanvas(document.getElementById('asistDrawer'));
    drawer.show();
}

function closeAsistDrawer() {
    const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('asistDrawer'));
    if (drawer) drawer.hide();
}
</script>
@endsection