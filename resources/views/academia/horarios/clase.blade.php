@extends('layouts.admin')

@section('title', 'Asistencia de Clases')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Asistencia de Clases</h1>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Nivel <span class="text-danger">*</span></label>
                <select name="nivel" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach (\App\Models\Academia\Nivel::activo()->get() as $n)
                        <option value="{{ $n->nivel }}" {{ $filtros['nivel'] == $n->nivel ? 'selected' : '' }}>{{ $n->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Turno <span class="text-danger">*</span></label>
                <select name="turno" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach (\App\Models\Academia\Turno::activo()->get() as $t)
                        <option value="{{ $t->turno }}" {{ $filtros['turno'] == $t->turno ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($sedes as $sede)
                        <option value="{{ $sede->id_campus }}" {{ $filtros['sede'] == $sede->id_campus ? 'selected' : '' }}>{{ $sede->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Edificio</label>
                <select name="edificio" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($edificios as $edificioOption)
                        <option value="{{ $edificioOption }}" {{ $filtros['edificio'] == $edificioOption ? 'selected' : '' }}>{{ $edificioOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Día</label>
                <select name="dia" class="form-select">
                    @foreach ([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $d => $label)
                        <option value="{{ $d }}" {{ $filtros['dia'] == $d ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $filtros['fecha'] }}">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">Filtrar ubicación y horario</button>
            </div>
        </form>
    </div>
</div>

@if ($filtros['nivel'] && $filtros['turno'])
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
        @if ($stats['total_clases'] > 0)
            <x-stat-card :icon="'bi-graph-up'" :label="'Avance'" :value="round(($stats['capturadas'] / max($stats['total_clases'], 1)) * 100) . '%'" :color="'info'">
                <div class="kpi-trend flat">–</div>
            </x-stat-card>
        @endif
    </div>

    {{-- Grid de asistencia --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sesión / Hora</th>
                            <th>Docente / Materia</th>
                            <th>Sección</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $current_sesion = null; $current_ubicacion = null; @endphp
                        @foreach ($horarios as $cl)
                            @php
                                $is_receso = ($cl['RECESO'] ?? '') === 'S';
                                $sesion_label = $cl['SESION'] ?? '?';
                                $hora_inicio = $cl['SESION_INI'] ?? '??:??';
                                $hora_fin = $cl['SESION_FIN'] ?? '??:??';
                                $estado = $cl['ASISTENCIA_ESTADO'] ?? null;
                                $estado_cls = $estado ? strtolower(str_replace(' ', '-', $estado)) : 'sin-captura';
                                $estado_label = $estado ?: 'Sin capturar';
                                $is_new_sesion = $sesion_label !== $current_sesion;
                                $ubicacion_key = ($cl['ID_CAMPUS'] ?? '') . '|' . ($cl['EDIFICIO'] ?? 'SIN EDIFICIO');
                                $is_new_ubicacion = $ubicacion_key !== $current_ubicacion;
                                $current_ubicacion = $ubicacion_key;
                                $current_sesion = $sesion_label;
                            @endphp
                            @if ($is_new_ubicacion)
                                <tr class="table-primary">
                                    <td colspan="6" class="fw-semibold py-2">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $cl['SEDE_NOMBRE'] ?? $cl['ID_CAMPUS'] ?? 'Sede sin definir' }}
                                        <span class="text-muted">· Edificio {{ $cl['EDIFICIO'] ?? 'sin definir' }}</span>
                                    </td>
                                </tr>
                            @endif
                            @if ($is_receso)
                                <tr class="table-warning">
                                    <td colspan="6" class="text-center py-3">
                                        <span class="badge bg-warning text-dark">RECESO</span> {{ $hora_inicio }} - {{ $hora_fin }}
                                    </td>
                                </tr>
                            @else
                                <tr class="asist-row">
                                    <td class="asist-session">
                                        @if ($is_new_sesion)
                                            <strong>Ses. {{ $sesion_label }}</strong>
                                            <br><small class="text-muted">{{ $hora_inicio }} - {{ $hora_fin }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $cl['NOMBREPROFESOR'] }}</div>
                                        <div class="text-muted small">{{ $cl['MATERIA_NOMBRE'] }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $cl['CODIGO_GRUPO'] }}</div>
                                        <div class="small text-muted">
                                            {{ $cl['GRADO'] ?? '' }}° · {{ $cl['TURNO'] ?? '' }}
                                            <span class="ms-1">· {{ $cl['ALUMNOS_TOTAL'] }} alumnos</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $cl['SEDE_NOMBRE'] ?? $cl['ID_CAMPUS'] ?? 'Sede sin definir' }}</div>
                                        <div class="small text-muted">
                                            Edificio {{ $cl['EDIFICIO'] ?? 'sin definir' }} · Aula {{ $cl['AULA'] ?? 'sin definir' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="asist-badge asist-badge--{{ $estado_cls }}">{{ $estado_label }}</span>
                                        @if ($cl['ASISTENCIA_OBS'])
                                            <div class="small text-muted" title="{{ $cl['ASISTENCIA_OBS'] }}">{{ Str::limit($cl['ASISTENCIA_OBS'], 40) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick='openAsistDrawer({{ json_encode([
                                                    "I"=>$cl["INICIAL"],"F"=>$cl["FINAL"],"P"=>$cl["PERIODO"],
                                                    "grupo"=>$cl["CODIGO_GRUPO"],"profesor"=>$cl["CLAVEPROFESOR"],
                                                    "asig"=>$cl["CLAVEASIGNATURA"],"dia"=>$cl["DIA"],"sesion"=>$cl["SESION"],
                                                    "fecha"=>$filtros["fecha"],"nombre"=>$cl["NOMBREPROFESOR"],
                                                    "materia"=>$cl["MATERIA_NOMBRE"],"grupoLabel"=>$cl["CODIGO_GRUPO"],
                                                    "sede"=>$cl["SEDE_NOMBRE"] ?? $cl["ID_CAMPUS"],"edificio"=>$cl["EDIFICIO"],"aula"=>$cl["AULA"],
                                                    "hora"=>$hora_inicio." - ".$hora_fin,
                                                    "estado"=>$estado,"obs"=>$cl["ASISTENCIA_OBS"] ?? ""
                                                ], JSON_HEX_APOS | JSON_HEX_TAG) }})'>
                                            {{ $estado ? 'Editar' : 'Capturar' }}
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Drawer captura --}}
    <x-drawer id="asistDrawer" title="Capturar Asistencia" size="lg">
        <form method="POST" action="{{ route('academia.horarios.clase.asistencia.guardar') }}" id="asistForm">
            @csrf
            <input type="hidden" name="inicial" id="acInicial"><input type="hidden" name="final" id="acFinal"><input type="hidden" name="periodo" id="acPeriodo">
            <input type="hidden" name="codigo_grupo" id="acGrupo"><input type="hidden" name="clave_profesor" id="acProfesor"><input type="hidden" name="clave_asignatura" id="acAsignatura">
            <input type="hidden" name="dia" id="acDia"><input type="hidden" name="sesion" id="acSesion"><input type="hidden" name="fecha" id="acFecha">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label">Profesor</label><input type="text" id="acNombre" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Materia</label><input type="text" id="acMateria" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Grupo</label><input type="text" id="acGrupoLabel" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Sede</label><input type="text" id="acSede" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Edificio</label><input type="text" id="acEdificio" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Aula</label><input type="text" id="acAula" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Sesión</label><input type="text" id="acHora" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Fecha</label><input type="text" id="acFechaLabel" class="form-control" readonly></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Estado</label>
                <div class="asist-estado-btns">
                    <label class="asist-estado-btn asist-estado-btn--presente"><input type="radio" name="estado" value="PRESENTE" required><span>Presente</span></label>
                    <label class="asist-estado-btn asist-estado-btn--ausente"><input type="radio" name="estado" value="AUSENTE"><span>Ausente</span></label>
                    <label class="asist-estado-btn asist-estado-btn--retardo"><input type="radio" name="estado" value="RETARDO"><span>Retardo</span></label>
                    <label class="asist-estado-btn asist-estado-btn--justificado"><input type="radio" name="estado" value="JUSTIFICADO"><span>Justificado</span></label>
                </div>
            </div>
            <div class="mb-3"><label class="form-label" for="acObs">Observaciones</label><textarea name="observaciones" id="acObs" rows="3" maxlength="500" class="form-control" placeholder="Nota..."></textarea></div>
            <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-secondary" onclick="closeAsistDrawer()">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
    </x-drawer>

    <script>
    function openAsistDrawer(clase) {
        document.getElementById('acInicial').value = clase.I;
        document.getElementById('acFinal').value = clase.F;
        document.getElementById('acPeriodo').value = clase.P;
        document.getElementById('acGrupo').value = clase.grupo;
        document.getElementById('acProfesor').value = clase.profesor;
        document.getElementById('acAsignatura').value = clase.asig;
        document.getElementById('acDia').value = clase.dia;
        document.getElementById('acSesion').value = clase.sesion;
        document.getElementById('acFecha').value = clase.fecha;
        document.getElementById('acNombre').value = clase.nombre;
        document.getElementById('acMateria').value = clase.materia;
        document.getElementById('acGrupoLabel').value = clase.grupoLabel;
        document.getElementById('acSede').value = clase.sede || 'Sede no definida';
        document.getElementById('acEdificio').value = clase.edificio || 'Edificio no definido';
        document.getElementById('acAula').value = clase.aula || 'Aula no definida';
        document.getElementById('acHora').value = clase.hora;
        document.getElementById('acFechaLabel').value = clase.fecha;
        document.querySelectorAll('input[name="estado"]').forEach(r => r.checked = r.value === (clase.estado || ''));
        document.getElementById('acObs').value = clase.obs || '';
        new bootstrap.Offcanvas(document.getElementById('asistDrawer')).show();
    }
    function closeAsistDrawer() { bootstrap.Offcanvas.getInstance(document.getElementById('asistDrawer'))?.hide(); }
    </script>

@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-funnel display-4 text-muted"></i>
            <h5 class="mt-3 text-muted">Selecciona nivel y turno</h5>
            <p class="text-muted">Usa los filtros superiores para ver la asistencia de clases.</p>
        </div>
    </div>
@endif
@endsection