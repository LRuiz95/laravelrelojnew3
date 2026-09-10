@extends('layouts.admin')

@section('title', 'Sincronización Firebird')

@section('content')
<style>
    .fb-section-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface-elevated);margin-bottom:10px}
    .fb-section-title{display:flex;align-items:center;gap:10px;margin:0;cursor:pointer;font-weight:600;font-size:13px;color:var(--text)}
    .fb-section-title small{font-weight:400;color:var(--text-tertiary)}
    .fb-section-title .form-check-input{width:18px;height:18px;margin:0;flex:0 0 18px}
    .fb-counter{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums;font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;border:1px solid var(--border)}
    .fb-counter.is-empty{background:rgba(148,163,184,.14);color:var(--text-tertiary)}
    .fb-counter.is-partial{background:var(--primary-soft);color:var(--primary-hover)}
    .fb-counter.is-full{background:rgba(16,185,129,.14);color:var(--success)}
    .fb-section-hint{color:var(--text-tertiary);font-style:italic;font-size:12px;margin:6px 2px 0;min-height:18px}
    .fb-needs-ciclo{opacity:.55}
    .fb-needs-ciclo .form-check-input{border-color:var(--warning)}
    .fb-quick-actions{display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:12px 16px;border:1px solid var(--border);border-radius:10px;background:color-mix(in srgb, var(--primary) 4%, var(--surface));margin-bottom:20px}
    @supports not (background:color-mix(in srgb,red 4%,transparent)){.fb-quick-actions{background:var(--surface-elevated)}}
    .fb-quick-actions .vr{width:1px;align-self:stretch;background:var(--border);opacity:1}
    .fb-remembered{color:var(--text-secondary);font-size:12px}
    .fb-remembered code{font-size:11px;background:var(--primary-soft);padding:2px 6px;border-radius:6px;color:var(--primary-hover)}
    [data-ciclo-banner]{display:flex;gap:8px;align-items:center;padding:10px 12px;border-radius:8px;background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.20);color:var(--warning);font-size:12px;margin-top:10px}
    .fb-dep-warning{display:flex;align-items:flex-start;gap:6px;padding:6px 10px;margin:4px 0;border-radius:6px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.20);color:#dc2626;font-size:11px;line-height:1.4}
    .fb-dep-warning strong{font-weight:700}
    .fb-dep-missing{opacity:.6}
    .fb-dep-missing .form-check-input{border-color:var(--danger)!important}
    .fb-dep-badge{font-size:9px;padding:1px 5px;border-radius:3px;font-weight:600;background:rgba(239,68,68,.12);color:#dc2626;margin-left:4px}
    .fb-phase{border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px;background:var(--surface)}
    .fb-table-group{border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .fb-tg-header{padding:8px 12px;background:color-mix(in srgb, var(--text) 3%, var(--surface));border-bottom:1px solid var(--border);font-size:12px;display:flex;align-items:center;justify-content:space-between}
    .fb-sync-option{display:flex;align-items:center;gap:10px;padding:10px 12px;min-height:44px;cursor:pointer;border-bottom:1px solid color-mix(in srgb, var(--border) 50%, transparent)}
    .fb-sync-option:last-child{border-bottom:none}
    .fb-sync-option:hover{background:var(--primary-soft)}
    .fb-sync-option .form-check-input{width:18px;height:18px;flex:0 0 18px}
    .fb-option-info{flex:1;min-width:0}
    .fb-option-name{font-size:13px;font-weight:600;color:var(--text)}
    .fb-option-desc{font-size:11px;color:var(--text-tertiary)}
    .fb-option-badge{font-size:10px;padding:2px 6px;border-radius:4px;font-weight:600}
    .fb-option-badge.is-rec{background:rgba(16,185,129,.12);color:var(--success)}
    .fb-stepper{display:flex;gap:6px;overflow-x:auto;scrollbar-width:none}
    .fb-stepper::-webkit-scrollbar{display:none}
    .fb-step{flex:1;display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);min-width:0}
    .fb-step.is-active{border-color:var(--primary);box-shadow:0 0 0 2px color-mix(in srgb, var(--primary) 15%, transparent)}
    .fb-step.is-done{border-color:var(--success);background:rgba(16,185,129,.05)}
    .fb-step-num{width:24px;height:24px;display:grid;place-items:center;border-radius:50%;background:var(--primary-soft);color:var(--primary);font-weight:700;font-size:11px;flex-shrink:0}
    .fb-step.is-done .fb-step-num{background:rgba(16,185,129,.15);color:var(--success)}
    .fb-step-label{font-size:11px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .fb-summary-card{border-left:3px solid var(--primary)}
    .fb-summary-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600}
    @media(max-width:767px){
        .fb-section-header{flex-wrap:wrap}
        .fb-quick-actions .vr{display:none}
        #selected-breakdown{display:none!important}
        .fb-stepper{gap:4px}
        .fb-step{padding:6px 8px}
        .fb-step-label{font-size:10px}
    }
</style>

<div class="container-fluid px-4">
    {{-- Flash messages --}}
    @if(session('dep_warnings') && count(session('dep_warnings')) > 0)
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Advertencias de dependencias FK:</strong>
            <ul class="mb-0 mt-1">
                @foreach(session('dep_warnings') as $warning)
                    <li class="small">{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li><li class="breadcrumb-item active">Firebird</li></ol></nav>
            <h1 class="h3 mb-1">Sincronización Firebird</h1>
            <p class="text-muted small mb-0">Legacy → MySQL · Selecciona solo lo que necesitas</p>
        </div>
        <a href="{{ route('firebird.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Actualizar</a>
    </div>

    @if($runningSync)
        <div class="alert alert-primary d-flex flex-wrap align-items-center gap-3 mb-4" role="alert" id="running-sync-banner">
            <div class="flex-grow-1">
                <div class="fw-semibold"><i class="bi bi-hourglass-split me-2"></i>Sincronización en ejecución</div>
                <div class="small text-muted">
                    <strong>{{ $runningSync->operationLabel }}</strong>
                    @if($runningSync->ciclo) · Ciclo: {{ $runningSync->ciclo }} @endif
                    · Iniciada: {{ $runningSync->started_at?->format('H:i:s') }}
                </div>
            </div>
            <div class="ms-3" style="min-width:200px">
                <div class="progress" style="height:8px" role="progressbar" aria-valuenow="{{ min(100, (int) round(($runningSync->processed / max(1, $runningSync->total)) * 100)) }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width:{{ min(100, (int) round(($runningSync->processed / max(1, $runningSync->total)) * 100)) }}%"></div>
                </div>
                <div class="small text-muted mt-1">{{ $runningSync->processed }}/{{ $runningSync->total }} — Etapa: <strong>{{ $runningSync->stage }}</strong></div>
            </div>
            <a href="{{ route('firebird.sync', $runningSync) }}" class="btn btn-sm btn-outline-primary ms-2">Ver detalle</a>
        </div>
    @endif

    @if($pendingCount > 0 && !$hasWorker)
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-3 mb-3" id="queue-stalled-alert">
  <div class="flex-grow-1">
    <div class="fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Cola detenida — {{ $pendingCount }} sincronización(es) en espera</div>
    <div class="small text-muted">No se detectó worker activo. La cola no avanzará hasta procesarla.</div>
  </div>
  <form method="POST" action="{{ route('firebird.execute-pending') }}" class="ms-auto">
    @csrf
    <button class="btn btn-warning btn-sm"><i class="bi bi-play-circle me-1"></i>Procesar ahora</button>
  </form>
  <a href="{{ route('operations.queue') }}" class="btn btn-outline-secondary btn-sm">Ver cola unificada</a>
</div>
@endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body py-3">
                <div class="d-flex align-items-center"><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-database"></i></div><div class="ms-3"><div class="stat-value">{{ $stats['total'] ?? 0 }}</div><div class="stat-label text-muted small">Totales</div></div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body py-3">
                <div class="d-flex align-items-center"><div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle"></i></div><div class="ms-3"><div class="stat-value">{{ $stats['completed'] ?? 0 }}</div><div class="stat-label text-muted small">Completadas</div></div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body py-3">
                <div class="d-flex align-items-center"><div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-x-circle"></i></div><div class="ms-3"><div class="stat-value">{{ $stats['failed'] ?? 0 }}</div><div class="stat-label text-muted small">Fallidas</div></div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body py-3">
                <div class="d-flex align-items-center"><div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass"></i></div><div class="ms-3"><div class="stat-value">{{ $stats['running'] ?? 0 }}</div><div class="stat-label text-muted small">En progreso</div></div></div>
            </div></div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Izquierda: Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="card-title mb-0"><i class="bi bi-cloud-download me-2"></i>Nueva sincronización</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('firebird.start') }}" method="POST" id="firebird-sync-form">
                        @csrf
                        <input type="hidden" name="operation" value="sync_custom">

                        {{-- Stepper --}}
                        <div class="fb-stepper mb-4" role="navigation" aria-label="Fases de sincronización">
                            <div class="fb-step is-active" data-step="base">
                                <span class="fb-step-num">1</span>
                                <span class="fb-step-label">Catálogos Base</span>
                                <span class="badge bg-primary-subtle text-primary ms-auto" data-mini="base">0/{{ collect($catalogGroups['base'])->flatten(1)->count() }}</span>
                            </div>
                            <div class="fb-step" data-step="ciclo">
                                <span class="fb-step-num">2</span>
                                <span class="fb-step-label">Por Ciclo</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" data-mini="ciclo">0/{{ collect($catalogGroups['ciclo'])->flatten(1)->count() }}</span>
                            </div>
                            <div class="fb-step" data-step="alumnos">
                                <span class="fb-step-num">3</span>
                                <span class="fb-step-label">Alumnos</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" data-mini="alumnos">0/{{ collect($catalogGroups['alumnos'])->flatten(1)->count() }}</span>
                            </div>
                        </div>

                        {{-- FASE 1: Catálogos Base --}}
                        <fieldset class="fb-phase" data-phase="base">
                            <legend class="visually-hidden">Catálogos Base</legend>
                            <div class="fb-section-header" data-section="base">
                                <label class="fb-section-title">
                                    <input type="checkbox" class="form-check-input fb-parent" id="parent-base" aria-label="Seleccionar todos los catálogos base">
                                    <i class="bi bi-tags text-primary" aria-hidden="true"></i>
                                    <span>Catálogos Base</span>
                                    <small>(independientes de ciclo)</small>
                                </label>
                                <span class="fb-counter is-empty" data-counter="base" aria-live="polite">0 / {{ collect($catalogGroups['base'])->flatten(1)->count() }}</span>
                            </div>
                            <div id="group-base" class="row g-2">
                                @foreach($catalogGroups['base'] as $groupName => $tables)
                                    <div class="col-12 col-md-6">
                                        <div class="fb-table-group">
                                            <div class="fb-tg-header">
                                                <span class="fw-semibold">{{ $groupName }}</span>
                                                <span class="text-muted small">{{ count($tables) }} tablas</span>
                                            </div>
                                            @foreach($tables as $table)
                                                <label class="fb-sync-option">
                                                    <input class="form-check-input sync-table-checkbox" type="checkbox" name="tables[]" value="{{ $table['fb'] }}" data-mysql="{{ $table['mysql'] }}" {{ $table['recommended'] ? 'checked' : '' }}>
                                                    <div class="fb-option-info">
                                                        <div class="fb-option-name">{{ $table['fb'] }}</div>
                                                        <div class="fb-option-desc">→ {{ $table['mysql'] }}</div>
                                                    </div>
                                                    @if($table['recommended'])
                                                        <span class="fb-option-badge is-rec">Recomendado</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="fb-section-hint" data-hint="base">Ninguna seleccionada</div>
                        </fieldset>

                        {{-- FASE 2: Catálogos por Ciclo --}}
                        <fieldset class="fb-phase" data-phase="ciclo">
                            <legend class="visually-hidden">Catálogos por Ciclo</legend>
                            <div class="fb-section-header" data-section="ciclo">
                                <label class="fb-section-title">
                                    <input type="checkbox" class="form-check-input fb-parent" id="parent-ciclo" aria-label="Seleccionar todas las tablas de ciclo">
                                    <i class="bi bi-calendar text-info" aria-hidden="true"></i>
                                    <span>Por Ciclo</span>
                                    <small>(requieren filtro)</small>
                                </label>
                                <span class="fb-counter is-empty" data-counter="ciclo" aria-live="polite">0 / {{ collect($catalogGroups['ciclo'])->flatten(1)->count() }}</span>
                            </div>

                            {{-- Selector ciclo integrado en la fase --}}
                            <div class="fb-ciclo-wrap mb-3" style="border-left:3px solid var(--info);padding-left:12px">
                                <label for="sync-ciclo" class="form-label small fw-bold">Ciclo Escolar <span class="text-danger">*</span></label>
                                <select name="ciclo" id="sync-ciclo" class="form-select form-select-sm" aria-describedby="ciclo-help ciclo-remembered">
                                    <option value="">-- Seleccionar ciclo --</option>
                                    @foreach($ciclos as $ciclo)
                                        <option value="{{ $ciclo->inicial }}-{{ $ciclo->final }}-{{ $ciclo->periodo }}">{{ $ciclo->inicial }}-{{ $ciclo->final }} (Periodo {{ $ciclo->periodo }})</option>
                                    @endforeach
                                </select>
                                <div id="ciclo-remembered" class="fb-remembered mt-1" aria-live="polite" hidden>
                                    <i class="bi bi-bookmark-check"></i> Recordado: <code data-remembered-value></code>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-forget-ciclo>Olvidar</button>
                                </div>
                                <div id="ciclo-help" class="form-text">Obligatorio para catálogos por ciclo y datos de alumnos.</div>
                            </div>

                            <div data-ciclo-banner hidden>
                                <i class="bi bi-info-circle"></i> Selecciona un ciclo para habilitar estas tablas. Tu selección se guardará.
                            </div>

                            <div id="group-ciclo" class="row g-2">
                                @foreach($catalogGroups['ciclo'] as $groupName => $tables)
                                    <div class="col-12 col-md-6">
                                        <div class="fb-table-group">
                                            <div class="fb-tg-header">
                                                <span class="fw-semibold">{{ $groupName }}</span>
                                                <span class="text-muted small">{{ count($tables) }} tablas</span>
                                            </div>
                                            @foreach($tables as $table)
                                                <label class="fb-sync-option">
                                                    <input class="form-check-input sync-table-checkbox" type="checkbox" name="tables[]" value="{{ $table['fb'] }}" data-mysql="{{ $table['mysql'] }}" data-requires-ciclo="true">
                                                    <div class="fb-option-info">
                                                        <div class="fb-option-name">{{ $table['fb'] }}</div>
                                                        <div class="fb-option-desc">→ {{ $table['mysql'] }}</div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="fb-section-hint" data-hint="ciclo">Ninguna seleccionada</div>
                        </fieldset>

                        {{-- FASE 3: Datos de Alumnos --}}
                        <fieldset class="fb-phase" data-phase="alumnos">
                            <legend class="visually-hidden">Datos de Alumnos</legend>
                            <div class="fb-section-header" data-section="alumnos">
                                <label class="fb-section-title">
                                    <input type="checkbox" class="form-check-input fb-parent" id="parent-alumnos" aria-label="Seleccionar todos los datos de alumnos">
                                    <i class="bi bi-people text-warning" aria-hidden="true"></i>
                                    <span>Alumnos</span>
                                    <small>(requieren ciclo + IDs)</small>
                                </label>
                                <span class="fb-counter is-empty" data-counter="alumnos" aria-live="polite">0 / {{ collect($catalogGroups['alumnos'])->flatten(1)->count() }}</span>
                            </div>
                            <div data-ciclo-banner hidden>
                                <i class="bi bi-info-circle"></i> Requiere ciclo + alumnos inscritos. Si sincronizas sin ciclo traerá 0 filas.
                            </div>
                            <div id="group-alumnos" class="row g-2">
                                @foreach($catalogGroups['alumnos'] as $groupName => $tables)
                                    <div class="col-12 col-md-6">
                                        <div class="fb-table-group">
                                            <div class="fb-tg-header">
                                                <span class="fw-semibold">{{ $groupName }}</span>
                                                <span class="text-muted small">{{ count($tables) }} tablas</span>
                                            </div>
                                            @foreach($tables as $table)
                                                <label class="fb-sync-option">
                                                    <input class="form-check-input sync-table-checkbox" type="checkbox" name="tables[]" value="{{ $table['fb'] }}" data-mysql="{{ $table['mysql'] }}" data-requires-ciclo="true" data-requires-alumnos="true">
                                                    <div class="fb-option-info">
                                                        <div class="fb-option-name">{{ $table['fb'] }}</div>
                                                        <div class="fb-option-desc">→ {{ $table['mysql'] }}</div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="fb-section-hint" data-hint="alumnos">Ninguna seleccionada</div>
                        </fieldset>

                        {{-- Quick Actions --}}
                        <div class="fb-quick-actions" role="toolbar" aria-label="Acciones rápidas de selección">
                            <button type="button" class="btn btn-primary btn-sm" id="select-all-global" aria-label="Seleccionar todas las tablas">
                                <i class="bi bi-check-all"></i> Seleccionar todo
                            </button>
                            <div class="vr"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-all" aria-label="Limpiar toda la selección">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                            <span class="ms-auto small" style="font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums" aria-live="polite">
                                <span id="selected-count">0</span> de <span id="selected-total">17</span>
                                <span class="text-muted d-none d-md-inline" id="selected-breakdown"> · Base 0 · Ciclo 0 · Alumnos 0</span>
                            </span>
                            <div class="w-100 d-md-none"></div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle-section="base">Base <span class="badge bg-primary-subtle text-primary ms-1" data-mini="base">0</span></button>
                                <button type="button" class="btn btn-sm btn-outline-info" data-toggle-section="ciclo">Ciclo <span class="badge bg-info-subtle text-info ms-1" data-mini="ciclo">0</span></button>
                                <button type="button" class="btn btn-sm btn-outline-warning" data-toggle-section="alumnos">Alumnos <span class="badge bg-warning-subtle text-warning ms-1" data-mini="alumnos">0</span></button>
                            </div>
                        </div>

                        {{-- Advertencias de dependencias FK --}}
                        <div id="dep-warnings" class="mb-3" role="alert" aria-live="polite"></div>

                        {{-- Opciones --}}
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="delete_orphans" id="delete_orphans" value="1">
                                    <label class="form-check-label small" for="delete_orphans">
                                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                                        Eliminar huérfanos en tablas seleccionadas
                                        <br><small class="text-muted">Acción destructiva — eliminar registros que ya no existen en Firebird</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="skip_existing" id="skip_existing" value="1" checked>
                                    <label class="form-check-label small" for="skip_existing">
                                        <i class="bi bi-shield-check text-success me-1"></i>
                                        Omitir registros existentes
                                        <br><small class="text-muted">Recomendado: más rápido y seguro</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary" id="btn-start-sync" disabled aria-disabled="true">
                                <i class="bi bi-cloud-download"></i> <span id="btn-label">Sincronizar</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                                <i class="bi bi-x"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Derecha: Resumen + Historial reciente --}}
        <div class="col-12 col-lg-4">
            <div class="position-sticky" style="top:24px">
                {{-- Resumen vivo --}}
                <div class="card mb-3 fb-summary-card">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2"><i class="bi bi-clipboard-data me-2"></i>Resumen</h6>
                        <div id="summary-empty" class="text-muted small fst-italic">Selecciona tablas para ver el resumen</div>
                        <div id="summary-content" hidden>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="fb-summary-pill bg-primary-subtle text-primary" data-pill="base">Base: 0</span>
                                <span class="fb-summary-pill bg-info-subtle text-info" data-pill="ciclo">Ciclo: 0</span>
                                <span class="fb-summary-pill bg-warning-subtle text-warning" data-pill="alumnos">Alumnos: 0</span>
                            </div>
                            <div class="small text-muted mb-2" id="summary-ciclo-display" hidden>
                                <i class="bi bi-calendar3"></i> Ciclo: <strong id="summary-ciclo-value"></strong>
                            </div>
                            <div class="small text-muted" id="summary-options-display">
                                <i class="bi bi-shield-check"></i> Omitir existentes: <strong>Sí</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Historial reciente --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 small fw-bold"><i class="bi bi-clock-history me-1"></i>Historial reciente</h6>
                        @if($syncs->isNotEmpty())
                            <a href="#full-history" class="small">Ver todo</a>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if($syncs->isEmpty())
                            <div class="text-center py-3 text-muted small">
                                <i class="bi bi-inbox"></i> Sin sincronizaciones aún
                            </div>
                        @else
                            @foreach($syncs->take(5) as $sync)
                                @php
                                    $sc = match($sync->status) { 'completed'=>'success', 'failed'=>'danger', 'running'=>'primary', default=>'secondary' };
                                @endphp
                                <a href="{{ route('firebird.sync', $sync) }}" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none border-bottom" style="border-color:var(--border)!important">
                                    <span class="badge bg-{{ $sc }} badge-pill" style="font-size:8px">&nbsp;</span>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="small fw-semibold text-truncate" style="color:var(--text)">{{ $sync->operationLabel }}</div>
                                        <div class="text-muted" style="font-size:11px">{{ $sync->started_at?->format('d/m H:i') ?? '-' }}</div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted" style="font-size:10px"></i>
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Último error --}}
                @if($syncs->where('status', 'failed')->isNotEmpty())
                    @php $lastFail = $syncs->where('status', 'failed')->first(); @endphp
                    <div class="card border-danger">
                        <div class="card-body py-3">
                            <h6 class="card-title text-danger small mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Último error</h6>
                            <p class="small text-muted mb-1">{{ $lastFail->operationLabel }} — {{ $lastFail->started_at?->format('d/m H:i') }}</p>
                            <p class="small text-danger mb-0" style="font-size:11px">{{ Str::limit($lastFail->error_message ?? 'Error desconocido', 120) }}</p>
                            <a href="{{ route('firebird.sync', $lastFail) }}" class="small">Ver detalle →</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Historial completo --}}
    <div class="card mt-4" id="full-history">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Historial de Sincronizaciones</h5>
        </div>
        <div class="card-body p-0">
            @if($syncs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-database fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No hay sincronizaciones registradas</p>
                    <p class="text-muted small">Usa el formulario arriba para iniciar una nueva sincronización</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Operación</th>
                                <th>Ciclo</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                                <th>Iniciada</th>
                                <th>Finalizada</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($syncs as $sync)
                                @php $sc = match($sync->status) { 'completed'=>'success', 'failed'=>'danger', 'running'=>'primary', 'cancelled'=>'warning', default=>'secondary' }; @endphp
                                <tr>
                                    <td style="font-family:'JetBrains Mono',monospace">#{{ $sync->id }}</td>
                                    <td><span class="badge bg-secondary">{{ $sync->operationLabel }}</span></td>
                                    <td>{{ $sync->ciclo ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $sc }}">{{ $sync->statusLabel }}</span></td>
                                    <td style="min-width:120px">
                                        @if($sync->total > 0)
                                            <div class="progress" style="height:6px">
                                                <div class="progress-bar bg-{{ $sync->status === 'completed' ? 'success' : ($sync->status === 'failed' ? 'danger' : 'primary') }}" role="progressbar" style="width:{{ min(100, ($sync->processed / max(1, $sync->total)) * 100) }}%"></div>
                                            </div>
                                            <small class="text-muted" style="font-size:11px">{{ $sync->processed }}/{{ $sync->total }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $sync->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="text-muted small">{{ $sync->finished_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td><a href="{{ route('firebird.sync', $sync) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $syncs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const LS_KEY = 'firebird_last_ciclo';
    const cicloSelect = document.getElementById('sync-ciclo');
    const btnStart = document.getElementById('btn-start-sync');
    const btnLabel = document.getElementById('btn-label');
    const form = document.getElementById('firebird-sync-form');

    // --- Section config ---
    const sections = {
        base:    { parent: '#parent-base',    selector: '.sync-table-checkbox:not([data-requires-ciclo])' },
        ciclo:   { parent: '#parent-ciclo',   selector: '.sync-table-checkbox[data-requires-ciclo]:not([data-requires-alumnos])' },
        alumnos: { parent: '#parent-alumnos', selector: '.sync-table-checkbox[data-requires-alumnos]' },
    };

    // --- FK Dependency map (Firebird table name → [required tables]) ---
    const TABLE_DEPENDENCIES = {
        'GRUPOS':          ['CICLOS', 'CFGNIVELES', 'CFGTURNOS', 'CFGSEDES'],
        'CURSOS':          ['CICLOS'],
        'CURSOS_DET':      ['CURSOS', 'CFGPLANES_DET'],
        'ALUMNOS_GRUPOS':  ['ALUMNOS', 'GRUPOS'],
        'HORARIOS_DET':    ['CICLOS', 'GRUPOS', 'PROFESORES', 'CFGPLANES_DET', 'CFGSEDES'],
        'ALUMNOS_NIVELES': ['ALUMNOS'],
        'CFGSESIONES':     ['CFGNIVELES', 'CFGTURNOS'],
    };

    // Friendly names for display
    const TABLE_NAMES = {
        'CFGSEDES': 'Sedes', 'CFGNIVELES': 'Niveles', 'CFGTURNOS': 'Turnos',
        'CICLOS': 'Ciclos', 'CFGPLANES_MST': 'Planes', 'CFGPLANES_DET': 'Materias',
        'CFGSESIONES': 'Sesiones', 'CFGTIPOSEVALUACION': 'Metodos Eval',
        'EMPLEADOS_CONTRATOS_CAT': 'Contratos', 'ALUMNOS': 'Alumnos', 'PROFESORES': 'Profesores',
        'GRUPOS': 'Grupos', 'HORARIOS_DET': 'Horarios', 'CURSOS': 'Cursos',
        'CURSOS_DET': 'Cursos Det', 'ALUMNOS_NIVELES': 'Alumnos Niveles',
        'ALUMNOS_GRUPOS': 'Alumnos Grupos',
    };

    // --- Ciclo persistence ---
    function restoreCiclo() {
        try {
            const saved = localStorage.getItem(LS_KEY);
            if (!saved) return;
            const exists = [...cicloSelect.options].some(o => o.value === saved);
            if (exists) {
                cicloSelect.value = saved;
                showRemembered(saved);
            } else {
                localStorage.removeItem(LS_KEY);
            }
        } catch(e) {}
    }

    function persistCiclo() {
        try {
            if (cicloSelect.value) localStorage.setItem(LS_KEY, cicloSelect.value);
            else localStorage.removeItem(LS_KEY);
        } catch(e) {}
        showRemembered(cicloSelect.value);
    }

    function showRemembered(val) {
        const wrap = document.getElementById('ciclo-remembered');
        if (!wrap) return;
        if (val) { wrap.hidden = false; wrap.querySelector('[data-remembered-value]').textContent = val; }
        else wrap.hidden = true;
    }

    document.querySelector('[data-forget-ciclo]')?.addEventListener('click', function() {
        localStorage.removeItem(LS_KEY);
        cicloSelect.value = '';
        showRemembered('');
        updateUI();
        cicloSelect.focus();
    });

    // --- Section stats ---
    function getSectionStats(key) {
        const nodes = document.querySelectorAll(sections[key].selector);
        const checked = [...nodes].filter(n => n.checked).length;
        return { total: nodes.length, checked };
    }

    function syncParent(key) {
        const { total, checked } = getSectionStats(key);
        const parent = document.querySelector(sections[key].parent);
        if (!parent) return;
        parent.checked = checked === total && total > 0;
        parent.indeterminate = checked > 0 && checked < total;
        parent.setAttribute('aria-checked', parent.indeterminate ? 'mixed' : String(parent.checked));

        // Badge counter
        const counter = document.querySelector('[data-counter="' + key + '"]');
        if (counter) {
            counter.textContent = checked + ' / ' + total;
            counter.className = 'fb-counter ' + (checked === 0 ? 'is-empty' : checked === total ? 'is-full' : 'is-partial');
        }
        // Mini badge in stepper & quick actions
        document.querySelectorAll('[data-mini="' + key + '"]').forEach(b => b.textContent = checked);
        // Hint
        const hint = document.querySelector('[data-hint="' + key + '"]');
        if (hint) hint.textContent = checked === 0 ? 'Ninguna seleccionada' : checked === total ? 'Todas seleccionadas' : checked + ' seleccionadas';
        // Step badge
        const stepBadge = document.querySelector('.fb-step[data-step="' + key + '"] .badge');
        if (stepBadge) {
            stepBadge.textContent = checked + '/' + total;
            stepBadge.className = 'badge ms-auto ' + (checked === 0 ? 'bg-secondary-subtle text-secondary' : checked === total ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary');
        }
    }

    // --- FK Dependency checking ---
    function getSelectedTables() {
        return [...document.querySelectorAll('.sync-table-checkbox:checked')].map(cb => cb.value);
    }

    function checkDependencies() {
        const selected = getSelectedTables();
        const selectedSet = new Set(selected);
        const warnings = [];
        const missingDeps = new Map(); // table → [missing deps]

        for (const table of selected) {
            const deps = TABLE_DEPENDENCIES[table];
            if (!deps) continue;
            const missing = deps.filter(d => !selectedSet.has(d));
            if (missing.length > 0) {
                missingDeps.set(table, missing);
                const friendlyMissing = missing.map(d => TABLE_NAMES[d] || d).join(', ');
                const friendlyTable = TABLE_NAMES[table] || table;
                warnings.push(
                    '<div class="fb-dep-warning">' +
                    '<i class="bi bi-exclamation-triangle flex-shrink-0 mt-1"></i>' +
                    '<span><strong>' + friendlyTable + '</strong> requiere que existan en MySQL: ' +
                    friendlyMissing + '. Si no existen, la sincronización fallará por FK constraint.</span>' +
                    '</div>'
                );
            }
        }

        // Update warnings container
        const container = document.getElementById('dep-warnings');
        if (container) container.innerHTML = warnings.join('');

        // Update visual state on checkboxes
        document.querySelectorAll('.sync-table-checkbox').forEach(cb => {
            const option = cb.closest('.fb-sync-option');
            if (!option) return;
            const table = cb.value;
            const deps = TABLE_DEPENDENCIES[table];
            if (!deps || !cb.checked) {
                option.classList.remove('fb-dep-missing');
                // Remove old badge
                const oldBadge = option.querySelector('.fb-dep-badge');
                if (oldBadge) oldBadge.remove();
                return;
            }
            const missing = deps.filter(d => !selectedSet.has(d));
            const isMissing = missing.length > 0;
            option.classList.toggle('fb-dep-missing', isMissing);
            // Add/remove badge
            let badge = option.querySelector('.fb-dep-badge');
            if (isMissing) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'fb-dep-badge';
                    badge.textContent = '⚠ FK';
                    option.appendChild(badge);
                }
            } else if (badge) {
                badge.remove();
            }
        });

        return { warnings, missingDeps, hasBlocking: warnings.length > 0 };
    }

    // --- Main updateUI ---
    function updateUI() {
        // Sync all parent checkboxes + badges
        Object.keys(sections).forEach(syncParent);

        // Global count
        const all = document.querySelectorAll('.sync-table-checkbox');
        const checkedAll = [...all].filter(c => c.checked);
        document.getElementById('selected-count').textContent = checkedAll.length;
        document.getElementById('selected-total').textContent = all.length;

        const breakdown = document.getElementById('selected-breakdown');
        if (breakdown) {
            const b = getSectionStats('base'), c = getSectionStats('ciclo'), a = getSectionStats('alumnos');
            breakdown.textContent = ' · Base ' + b.checked + ' · Ciclo ' + c.checked + ' · Alumnos ' + a.checked;
        }

        // Ciclo dependency
        const cicloOk = cicloSelect.value !== '';
        const requiresCiclo = checkedAll.some(cb => cb.dataset.requiresCiclo === 'true');
        const shouldDisable = checkedAll.length === 0 || (requiresCiclo && !cicloOk);
        btnStart.disabled = shouldDisable;
        btnStart.setAttribute('aria-disabled', String(shouldDisable));
        btnLabel.textContent = checkedAll.length > 0 ? 'Sincronizar ' + checkedAll.length + ' tabla' + (checkedAll.length > 1 ? 's' : '') : 'Sincronizar';

        // Visual hint for ciclo-dependent sections
        document.querySelectorAll('.sync-table-checkbox[data-requires-ciclo]').forEach(cb => {
            cb.closest('.fb-sync-option')?.classList.toggle('fb-needs-ciclo', !cicloOk);
        });
        // Banners
        document.querySelectorAll('[data-ciclo-banner]').forEach(b => b.hidden = cicloOk);

        // FK dependency check
        const depResult = checkDependencies();

        // Stepper states
        document.querySelectorAll('.fb-step').forEach(step => {
            const key = step.dataset.step;
            if (!key) return;
            const s = getSectionStats(key);
            step.classList.toggle('is-done', s.checked === s.total && s.total > 0);
            step.classList.toggle('is-active', s.checked > 0 && s.checked < s.total);
        });

        // Summary card
        const summaryEmpty = document.getElementById('summary-empty');
        const summaryContent = document.getElementById('summary-content');
        const hasAny = checkedAll.length > 0;
        if (summaryEmpty) summaryEmpty.hidden = hasAny;
        if (summaryContent) summaryContent.hidden = !hasAny;
        if (hasAny) {
            const b = getSectionStats('base'), c = getSectionStats('ciclo'), a = getSectionStats('alumnos');
            const pill = (k, label, cls) => { const el = document.querySelector('[data-pill="' + k + '"]'); if(el){ el.textContent = label + ': ' + (sections[k] ? getSectionStats(k).checked : 0); } };
            pill('base', 'Base', 'primary');
            pill('ciclo', 'Ciclo', 'info');
            pill('alumnos', 'Alumnos', 'warning');
            const cicloDisplay = document.getElementById('summary-ciclo-display');
            const cicloValue = document.getElementById('summary-ciclo-value');
            if (cicloDisplay) cicloDisplay.hidden = !cicloOk;
            if (cicloValue && cicloOk) cicloValue.textContent = cicloSelect.value;
        }
    }

    // --- Event listeners ---
    // Parent → children
    Object.entries(sections).forEach(([key, cfg]) => {
        document.querySelector(cfg.parent)?.addEventListener('change', function(e) {
            document.querySelectorAll(cfg.selector).forEach(cb => cb.checked = e.target.checked);
            updateUI();
        });
    });

    // Children → parent
    document.querySelectorAll('.sync-table-checkbox').forEach(cb => cb.addEventListener('change', updateUI));

    // Ciclo change
    cicloSelect.addEventListener('change', function() { persistCiclo(); updateUI(); });

    // Quick actions: toggle per section
    document.querySelectorAll('[data-toggle-section]').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = btn.dataset.toggleSection;
            const { total, checked } = getSectionStats(key);
            const shouldCheck = checked < total;
            document.querySelectorAll(sections[key].selector).forEach(cb => cb.checked = shouldCheck);
            updateUI();
        });
    });

    // Global select all
    document.getElementById('select-all-global')?.addEventListener('click', function() {
        document.querySelectorAll('.sync-table-checkbox').forEach(cb => cb.checked = true);
        updateUI();
    });

    // Clear all
    document.getElementById('clear-all')?.addEventListener('click', function() {
        document.querySelectorAll('.sync-table-checkbox').forEach(cb => cb.checked = false);
        updateUI();
    });

    // --- Submit validation ---
    form.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.sync-table-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            return;
        }
        const requiresCiclo = Array.from(checked).some(cb => cb.dataset.requiresCiclo === 'true');
        if (requiresCiclo && !cicloSelect.value) {
            e.preventDefault();
            cicloSelect.focus();
            cicloSelect.classList.add('is-invalid');
            setTimeout(() => cicloSelect.classList.remove('is-invalid'), 3000);
            return;
        }
        // FK dependency validation
        const depResult = checkDependencies();
        if (depResult.hasBlocking) {
            e.preventDefault();
            const tables = Array.from(checked).map(cb => cb.value).join(', ');
            const warnings = depResult.warnings.map(w => w.replace(/<[^>]+>/g, '')).join('\n');
            if (!confirm('⚠️ Advertencias de dependencias FK:\n\n' + warnings + '\n\n¿Continuar de todas formas?\n\nTablas: ' + tables)) {
                return;
            }
            // If user confirms, submit anyway (backend will handle)
            form.submit();
        } else {
            const tableList = Array.from(checked).map(cb => cb.value).join(', ');
            if (!confirm('Iniciar sincronización de ' + checked.length + ' tabla(s)?\n\n' + tableList)) {
                e.preventDefault();
            }
        }
    });

    // --- Init ---
    restoreCiclo();
    updateUI();
});
</script>
@endpush

@if($runningSync)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fallback reload si el polling falla
    let reloadTimer = setTimeout(function() { window.location.reload(); }, 15000);
    const syncId = {{ $runningSync->id }};
    const statusUrl = "{{ route('firebird.status', $runningSync) }}";
    const banner = document.getElementById('running-sync-banner');
    async function pollStatus() {
        try {
            const res = await fetch(statusUrl, {headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            if (!res.ok) return;
            const data = await res.json();
            if (banner && data.total !== undefined) {
                const bar = banner.querySelector('.progress-bar');
                const meta = banner.querySelector('.small.text-muted.mt-1');
                if (bar && data.total !== null) {
                    const pct = Math.min(100, Math.round((data.processed / Math.max(1, data.total)) * 100));
                    bar.style.width = pct + '%';
                    bar.setAttribute('aria-valuenow', pct);
                }
                if (meta) meta.textContent = `${data.processed ?? 0}/${data.total ?? 0} — Etapa: ${data.stage ?? '-'}`;
            }
            if (['completed','failed','cancelled'].includes(data.status)) {
                clearTimeout(reloadTimer);
                window.location.reload();
            }
        } catch(e) {}
    }
    setInterval(pollStatus, 3000);
});
</script>
@endpush
@endif
