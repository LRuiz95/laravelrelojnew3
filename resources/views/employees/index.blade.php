@extends('layouts.admin')

@section('title', 'Empleados')

@section('content')

{{-- ═══ FILTROS ═══ --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="employeeFilters" class="row g-3 align-items-end" method="GET"
              action="{{ route('employees.index') }}">
            <div class="col-md-4">
                <label class="form-label small mb-1" for="employeeSearch">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search text-tertiary-token"></i></span>
                    <input type="search" name="q" id="employeeSearch" value="{{ request('q') }}"
                           class="form-control" placeholder="Buscar empleado por nombre, ID o puesto…"
                           aria-label="Buscar empleados">
                </div>
            </div>

            @if(!empty($cargos) && count($cargos))
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterCargo">Puesto</label>
                    <select name="cargo" id="filterCargo" class="form-select" aria-label="Filtrar por puesto">
                        <option value="">Todos</option>
                        @foreach($cargos as $c)
                            <option @selected(request('cargo') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(!empty($departamentos) && count($departamentos))
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterDepto">Departamento</label>
                    <select name="departamento" id="filterDepto" class="form-select" aria-label="Filtrar por departamento">
                        <option value="">Todos</option>
                        @foreach($departamentos as $d)
                            <option @selected(request('departamento') === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(!empty($sedes) && count($sedes))
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterSede">Sede</label>
                    <select name="id_campus" id="filterSede" class="form-select" aria-label="Filtrar por sede">
                        <option value="">Todas</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s->id_campus }}" @selected(request('id_campus') == $s->id_campus)>{{ $s->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Filtrar</button>
                @if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus']))
                    <a href="{{ route('employees.index') }}" class="btn btn-ghost" aria-label="Limpiar filtros">Limpiar</a>
                @endif
            </div>

            {{-- Filtros activos — chips claros --}}
            @if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus', 'sin_huella', 'sin_device']))
                <div class="col-12 d-flex gap-2 flex-wrap align-items-center pt-1">
                    <span class="small text-tertiary-token me-1">Activos:</span>
                    @if(request('q'))
                        <span class="badge cat-blue d-inline-flex align-items-center gap-1">
                            <i class="bi bi-search"></i> {{ request('q') }}
                            <a href="{{ route('employees.index', request()->except('q')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro búsqueda">&times;</a>
                        </span>
                    @endif
                    @if(request('cargo'))
                        <span class="badge cat-purple d-inline-flex align-items-center gap-1">
                            <i class="bi bi-briefcase"></i> {{ request('cargo') }}
                            <a href="{{ route('employees.index', request()->except('cargo')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro puesto">&times;</a>
                        </span>
                    @endif
                    @if(request('departamento'))
                        <span class="badge cat-blue d-inline-flex align-items-center gap-1">
                            <i class="bi bi-building"></i> {{ request('departamento') }}
                            <a href="{{ route('employees.index', request()->except('departamento')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro departamento">&times;</a>
                        </span>
                    @endif
                    @if(request('id_campus'))
                        <span class="badge cat-green d-inline-flex align-items-center gap-1">
                            <i class="bi bi-geo-alt"></i> Sede {{ request('id_campus') }}
                            <a href="{{ route('employees.index', request()->except('id_campus')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro sede">&times;</a>
                        </span>
                    @endif
                    @if(request('sin_huella'))
                        <span class="badge cat-amber d-inline-flex align-items-center gap-1">
                            <i class="bi bi-fingerprint"></i> Sin huellas
                            <a href="{{ route('employees.index', request()->except('sin_huella')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro sin huellas">&times;</a>
                        </span>
                    @endif
                    @if(request('sin_device'))
                        <span class="badge cat-amber d-inline-flex align-items-center gap-1">
                            <i class="bi bi-hdd-network"></i> Sin enrolar
                            <a href="{{ route('employees.index', request()->except('sin_device')) }}" class="ms-1 text-decoration-none" aria-label="Quitar filtro sin enrolar">&times;</a>
                        </span>
                    @endif
                </div>
            @endif

            {{-- Chips rápidos --}}
            <div class="col-12 d-flex gap-2 flex-wrap pt-1">
                <span class="small text-tertiary-token me-1">Rápidos:</span>
                <a href="{{ route('employees.index', array_merge(request()->query(), ['sin_huella' => 1])) }}" class="ref-chip">
                    <i class="bi bi-fingerprint"></i> Sin huellas
                </a>
                <a href="{{ route('employees.index', array_merge(request()->query(), ['sin_device' => 1])) }}" class="ref-chip">
                    <i class="bi bi-hdd-network"></i> Sin enrolar
                </a>
                <a href="{{ route('employees.sobrantes') }}" class="ref-chip">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Sobrantes
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ═══ TABS POR ESTADO ═══ --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $status === 'todos' ? 'active' : '' }}"
           href="{{ route('employees.index', array_merge(request()->query(), ['status' => 'todos'])) }}">
            Todos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'activos' ? 'active' : '' }}"
           href="{{ route('employees.index', array_merge(request()->query(), ['status' => 'activos'])) }}">
            Activos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'bajas' ? 'active' : '' }}"
           href="{{ route('employees.index', array_merge(request()->query(), ['status' => 'bajas'])) }}">
            Bajas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('employees.sobrantes') }}">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            Sobrantes
            @if(($sobrantesStats['total'] ?? 0) > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $sobrantesStats['total'] }}</span>
            @endif
        </a>
    </li>
</ul>

{{-- ═══ CONTADOR + ACCIÓN CREAR ═══ --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div id="employees-counter" class="small text-tertiary-token">
        <i class="bi bi-people me-1"></i> {{ $employees->total() }} empleados
        @if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus']))
            · filtrado por
            <span class="text-secondary-token">
                {{ request('q') ?: request('cargo') ?: request('departamento') ?: ('sede ' . request('id_campus')) }}
            </span>
        @endif
    </div>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Agregar empleado
        </a>
    @endif
</div>

{{-- ═══ TABLA (SSR — initial render from server) ═══ --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="employees-table" class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th style="width:28%">Empleado</th>
                        <th style="width:22%">Puesto</th>
                        <th style="width:14%">Adscripción</th>
                        <th style="width:20%">Hardware</th>
                        <th style="width:8%">Estado</th>
                        <th class="text-end" style="width:8%">Acciones</th>
                    </tr>
                </thead>
                <tbody data-is-admin="{{ auth()->user()->isAdmin() ? '1' : '0' }}">
                    @forelse ($employees as $employee)
                        @php
                            // ── Dispositivos y enrolamiento ──
                            $enrollment = $employee->devices->first();
                            $devicesCount = $employee->devices->count();

                            // ── Huellas (con fallbacks) ──
                            $fpCount = $employee->fingerprints_count ?? $employee->fingerprints->count() ?? 0;

                            // ── Estado Firebird ──
                            $fbStatus = $employee->status_actual ?? null;
                            $isBaja = $fbStatus === 'B';

                            // ── Estado enrolamiento ──
                            $enrolled = $devicesCount > 0;
                            $anyActive = $enrolled && $employee->devices->contains(fn($d) => $d->pivot->active);

                            // ── Color huellas por count ──
                            $fpCat = $fpCount === 0 ? 'cat-gray' : ($fpCount < 3 ? 'cat-amber' : 'cat-green');

                            // ── Último sync (si viene con latestSync eager o syncs) ──
                            $lastSync = null;
                            if (isset($employee->syncs) && method_exists($employee->syncs, 'first')) {
                                $lastSync = $employee->syncs->first();
                            } elseif (isset($employee->latestSync)) {
                                $lastSync = $employee->latestSync;
                            }

                            // ── Sede label (si existe relación o fallback a id_campus) ──
                            $sedeLabel = '—';
                            if (isset($employee->sede) && $employee->sede) {
                                $sedeLabel = $employee->sede->descripcion ?? $employee->id_campus ?? '—';
                            } elseif (!empty($employee->id_campus)) {
                                $sedeLabel = $employee->id_campus;
                            }
                        @endphp
                        <tr>
                            {{-- A — Empleado --}}
                            <td data-label="Empleado">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="avatar is-sm flex-shrink-0">{{ strtoupper(mb_substr($employee->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" title="{{ $employee->name }}" style="max-width:18ch">
                                            {{ $employee->name }}
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="small">{{ $employee->user_id }}</code>
                                            @if(!empty($employee->fecha_ingreso))
                                                <span class="mono small text-tertiary-token" title="Fecha ingreso {{ \Carbon\Carbon::parse($employee->fecha_ingreso)->format('d/m/Y') }}">
                                                    {{ \Carbon\Carbon::parse($employee->fecha_ingreso)->locale('es')->isoFormat('MMM YYYY') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- B — Puesto --}}
                            <td data-label="Puesto">
                                @if(!empty($employee->cargo))
                                    <div class="fw-semibold small text-truncate" title="{{ $employee->cargo }}" style="max-width:20ch">
                                        <i class="bi bi-briefcase me-1 text-tertiary-token"></i>{{ $employee->cargo }}
                                    </div>
                                    <div class="small text-secondary-token text-truncate" style="max-width:20ch">
                                        @if(!empty($employee->departamento))
                                            <i class="bi bi-building me-1"></i>{{ $employee->departamento }}
                                        @endif
                                    </div>
                                @else
                                    <span class="small text-tertiary-token">—</span>
                                    @if(!empty($employee->departamento))
                                        <div class="small text-secondary-token text-truncate" style="max-width:20ch">
                                            <i class="bi bi-building me-1"></i>{{ $employee->departamento }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- C — Adscripción --}}
                            <td data-label="Sede">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    @if($sedeLabel !== '—')
                                        <span class="badge cat-blue" title="{{ $employee->id_campus ? 'ID_CAMPUS '.$employee->id_campus : '' }}">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $sedeLabel }}
                                        </span>
                                    @else
                                        <span class="small text-tertiary-token">—</span>
                                    @endif
                                    @if(!empty($employee->contrato))
                                        <span class="badge cat-gray">{{ $employee->contrato }}</span>
                                    @endif
                                    @if(!empty($employee->nivel))
                                        <span class="badge cat-purple" title="Nivel {{ $employee->nivel }}">{{ $employee->nivel }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- D — Hardware (devices + huellas + tarjeta + sync) --}}
                            <td data-label="Hardware">
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @forelse($employee->devices->take(2) as $device)
                                        <a href="{{ route('devices.show', $device) }}" class="ref-chip"
                                           title="UID {{ $device->pivot->device_uid }} · {{ $device->pivot->roleLabel() }} · {{ $device->pivot->card_number ? 'Tarjeta '.$device->pivot->card_number : 'Sin tarjeta' }}">
                                            <i class="bi bi-hdd-network"></i>{{ $device->name }}
                                        </a>
                                    @empty
                                        <span class="badge cat-gray">Sin enrolar</span>
                                    @endforelse
                                    @if($devicesCount > 2)
                                        <span class="badge cat-gray" title="{{ $employee->devices->slice(2)->pluck('name')->join(', ') }}">
                                            +{{ $devicesCount - 2 }}
                                        </span>
                                    @endif
                                    {{-- Huellas --}}
                                    <span class="badge {{ $fpCat }}" title="{{ $fpCount }} huellas guardadas">
                                        <i class="bi bi-fingerprint me-1"></i>{{ $fpCount }}
                                    </span>
                                    {{-- Tarjeta RFID icon --}}
                                    @if($employee->devices->contains(fn($d) => filled($d->pivot->card_number)))
                                        <span class="small text-tertiary-token" title="Con tarjeta RFID">
                                            <i class="bi bi-credit-card"></i>
                                        </span>
                                    @endif
                                </div>
                                {{-- Último sync compacto (segunda línea) --}}
                                @if($lastSync)
                                    @php
                                        $scMap = ['completed' => 'cat-green', 'failed' => 'cat-red', 'running' => 'cat-amber', 'queued' => 'cat-gray'];
                                        $sc = $scMap[$lastSync->status] ?? 'cat-gray';
                                    @endphp
                                    <div class="small mono text-tertiary-token mt-1 text-truncate" style="max-width:22ch"
                                         title="{{ $lastSync->stage ?? '' }}{{ $lastSync->error_message ? ' · '.$lastSync->error_message : '' }}">
                                        <span class="badge {{ $sc }}" style="font-size:10px">{{ $lastSync->status }}</span>
                                        {{ $lastSync->finished_at?->format('d/m H:i') ?? $lastSync->created_at?->format('d/m H:i') }}
                                    </div>
                                @endif
                            </td>

                            {{-- F — Estado compuesto --}}
                            <td data-label="Estado">
                                @if($isBaja)
                                    <span class="badge badge-with-dot cat-gray" title="STATUSACTUAL=B Baja nómina">Baja</span>
                                @else
                                    <span class="badge badge-with-dot cat-green" title="STATUSACTUAL={{ $fbStatus ?? 'NULL→Activo' }}">Activo</span>
                                @endif
                                <div class="small mt-1">
                                    @if(!$enrolled)
                                        <span class="badge badge-with-dot cat-gray">Sin enrolar</span>
                                    @elseif($anyActive)
                                        <span class="badge badge-with-dot cat-green">Enrolado</span>
                                    @else
                                        <span class="badge badge-with-dot cat-amber">Inactivo</span>
                                    @endif
                                </div>
                            </td>

                            {{-- G — Acciones --}}
                            <td data-label="">
                                <div class="table-row-actions justify-content-end">
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-ghost"
                                       title="Ver detalle / editar" aria-label="Ver {{ $employee->name }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-ghost"
                                           title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($enrolled)
                                            <form action="{{ route('employees.sync-devices', $employee) }}" method="POST" class="d-inline" data-sync>
                                                @csrf
                                                @foreach($employee->devices as $d)
                                                    <input type="hidden" name="device_ids[]" value="{{ $d->id }}">
                                                @endforeach
                                                <button class="btn btn-sm btn-ghost"
                                                        title="Re-sincronizar en {{ $devicesCount }} checador(es)"
                                                        aria-label="Sincronizar">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-title="¿Quitar a {{ $employee->name }}?"
                                              data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-icon-danger" title="Dar de baja" aria-label="Dar de baja">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        </form>
                                    @else
                                        @foreach($employee->devices as $device)
                                            <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-ghost"
                                               title="Ver {{ $device->name }}" aria-label="Ver dispositivo {{ $device->name }}">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                @if(request()->hasAny(['q','cargo','departamento','id_campus','sin_huella','sin_device']))
                                    <div class="text-center py-5">
                                        <i class="bi bi-search fs-1 text-secondary d-block mb-2"></i>
                                        <p class="fw-semibold mb-1">Sin resultados para tu filtro</p>
                                        <p class="small text-tertiary-token mb-3">Prueba con otro nombre, ID, puesto o departamento, o limpia los filtros.</p>
                                        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-x-lg me-1"></i> Limpiar filtros
                                        </a>
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                                        <p class="fw-semibold mb-1">No hay empleados</p>
                                        <p class="small text-tertiary-token mb-3">Vacía los checadores con «Traer usuarios» o sincroniza Firebird EMPLEADOS para poblar el catálogo.</p>
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Agregar empleado
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Paginación SSR — el JS la reemplaza vía AJAX --}}
<div id="employees-pagination" class="mt-3">{{ $employees->links() }}</div>

@endsection
