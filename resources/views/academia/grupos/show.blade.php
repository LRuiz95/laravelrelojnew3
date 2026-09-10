@extends('layouts.admin')

@section('title', $grupo->codigo_grupo . ' - ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $grupo->codigo_grupo }}</h1>
        <p class="text-muted mb-0">
            {{ $grupo->grado }}° · {{ $grupo->turno_nombre }} · {{ $grupo->nivelRel?->descripcion ?? $grupo->nivel }} · {{ $grupo->modalidad_nombre }}
            <span class="ms-2 badge bg-secondary">{{ $grupo->inscritos }} inscritos</span>
            <span class="ms-1 badge bg-secondary">{{ $grupo->sede?->descripcion }}</span>
        </p>
        @php
            $partesCodigo = $grupo->codigo_grupo_partes;
        @endphp
        <small class="text-muted">
            Plan {{ $partesCodigo['anio_plan'] ?? '—' }} · Nivel {{ $partesCodigo['nivel'] ?? $grupo->nivel }} ·
            Sede código {{ $partesCodigo['sede'] ?? '—' }} · Modelo {{ $partesCodigo['modelo'] ?? '—' }} ·
            Grado/grupo {{ $partesCodigo['grado_grupo'] ?? '—' }}
            @if ($partesCodigo['nivel_superior']) · Ingeniería/Licenciatura ({{ $partesCodigo['nivel_superior'] }}) @endif
        </small>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.grupos.asistencia', $grupo) }}" class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i> Asistencia
        </a>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-alumnos">Alumnos ({{ $alumnos->total() }})</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-horarios">Horarios</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-conflictos">Conflictos Aula</button></li>
</ul>

<div class="tab-content">
    {{-- Alumnos --}}
    <div class="tab-pane fade show active" id="tab-alumnos">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre</th>
                                <th>Nivel / Carrera</th>
                                <th>Contacto</th>
                                <th>Estatus académico</th>
                                <th>Estatus en grupo</th>
                                <th>Inscripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($alumnos as $alumnoGrupo)
                                <tr>
                                    <td class="fw-semibold">{{ $alumnoGrupo->numero_alumno }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $alumnoGrupo->nombre_completo }}</div>
                                        <small class="text-muted">CURP: {{ $alumnoGrupo->curp ?: 'No registrada' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $alumnoGrupo->nivelRel?->descripcion ?? $alumnoGrupo->nivel ?? '—' }}</div>
                                        <small class="text-muted">{{ $alumnoGrupo->carrera ?: 'Carrera no registrada' }}</small>
                                    </td>
                                    <td class="small">
                                        <div>{{ $alumnoGrupo->telefono ?: 'Sin teléfono' }}</div>
                                        <div class="text-muted">{{ $alumnoGrupo->email ?: 'Sin correo' }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $estatusAcademico = strtoupper((string) $alumnoGrupo->estatus);
                                            $estatusAcademicoClase = $estatusAcademico === 'ACTIVO' ? 'badge--active' : 'badge--inactive';
                                        @endphp
                                        <span class="badge badge--status {{ $estatusAcademicoClase }}">
                                            {{ $alumnoGrupo->estatus ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge--status {{ ($alumnoGrupo->pivot_estatus ?? null) === 'INSCRITO' ? 'badge--active' : 'badge--inactive' }}">
                                            {{ $alumnoGrupo->pivot_estatus ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $alumnoGrupo->pivot_fecha_inscripcion ? \Carbon\Carbon::parse($alumnoGrupo->pivot_fecha_inscripcion)->format('d/m/Y') : '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('academia.alumnos.show', $alumnoGrupo) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $alumnos->links() }}
        </div>
    </div>

    {{-- Horarios --}}
    <div class="tab-pane fade" id="tab-horarios">
        @if ($horarios->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-calendar-x fs-1 mb-2"></i>
                    <p>No hay horarios programados para este grupo</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body p-0">
                    @foreach ($horarios as $dia => $clases)
                        <div class="{{ $loop->first ? '' : 'border-top' }}">
                            <div class="p-3 bg-light border-bottom fw-semibold">
                                <span class="badge {{ in_array($dia, [6,7]) ? 'bg-purple' : 'bg-primary' }} me-2">
                                    {{ ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$dia-1] }}
                                </span>
                                {{ $clases->first()->sesionBase?->descripcion }}
                            </div>
                            @foreach ($clases as $clase)
                                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                                    <div class="text-nowrap small text-muted" style="width: 120px;">
                                        {{ $clase->sesionBase?->hora_inicio?->format('H:i') }} - {{ $clase->sesionBase?->hora_fin?->format('H:i') }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $clase->materia?->label }}</div>
                                        <div class="small text-muted">
                                            {{ $clase->profesor?->nombre_completo }} · {{ $clase->ubicacion }} · <span class="badge {{ $clase->tipoClase === 'PTC' ? 'bg-purple' : 'bg-info' }}">{{ $clase->tipoClase }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Conflictos de aula --}}
    <div class="tab-pane fade" id="tab-conflictos">
        @if (empty($conflictos))
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <p>No se detectaron conflictos de aula</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header bg-danger-subtle">
                    <span class="fw-bold text-danger">Se detectaron {{ count($conflictos) }} conflicto(s) de aula</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Sesión</th>
                                    <th>Sede</th>
                                    <th>Edificio</th>
                                    <th>Aula</th>
                                    <th>Clases en conflicto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conflictos as $c)
                                    <tr>
                                        <td>{{ ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$c->dia-1] }}</td>
                                        <td>{{ $c->sesion }}</td>
                                        <td>{{ $c->id_campus }}</td>
                                        <td>{{ $c->edificio }}</td>
                                        <td>{{ $c->aula }}</td>
                                        <td><span class="badge bg-danger">{{ $c->total }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection