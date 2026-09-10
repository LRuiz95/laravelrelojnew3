<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de control') · {{ config('app.name') }}</title>
    <script>
        // Aplica el tema antes del primer paint para evitar FOUC.
        (() => {
            try {
                const stored = localStorage.getItem('dash-theme') || 'system';
                const theme = stored === 'system'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : stored;
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-theme-preference', stored);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-theme-preference', 'system');
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

{{-- Datos contextuales para JS (notificaciones, búsqueda, alertas) --}}
@php $dash = $dash ?? []; @endphp
<script>
    window.__dash = {!! json_encode($dash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
</script>

<div class="app-shell">
    <nav class="app-sidebar" aria-label="Navegación principal">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark"><i class="bi bi-fingerprint"></i></span>
            <span class="brand-label">{{ config('app.name') }}</span>
        </a>
        <div class="app-sidebar-inner">
            <div class="nav-group-title">Módulos</div>
<ul class="app-nav">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" data-tooltip="Panel de control" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Panel de control">
                    <i class="bi bi-speedometer2"></i><span class="nav-label">Panel de control</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('devices.index') }}" data-tooltip="Dispositivos" class="nav-link {{ request()->routeIs('devices.*') ? 'active' : '' }}" title="Dispositivos">
                    <i class="bi bi-hdd-network"></i><span class="nav-label">Dispositivos</span>
                    @php $offlineDevices = collect($dash['notifications'] ?? [])->contains(fn ($n) => $n['type'] === 'danger'); @endphp
                    @if ($offlineDevices)
                        <span class="nav-notif-dot pulse" title="Hay checadores sin conexión"></span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('employees.index') }}" data-tooltip="Empleados" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" title="Empleados">
                    <i class="bi bi-people"></i><span class="nav-label">Empleados</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('fingerprints.index') }}" data-tooltip="Huellas" class="nav-link {{ request()->routeIs('fingerprints.*') ? 'active' : '' }}" title="Huellas">
                    <i class="bi bi-fingerprint"></i><span class="nav-label">Huellas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendances.index') }}" data-tooltip="Asistencias" class="nav-link {{ request()->routeIs('attendances.*') ? 'active' : '' }}" title="Asistencias">
                    <i class="bi bi-calendar-check"></i><span class="nav-label">Asistencias</span>
                </a>
            </li>
            @if (auth()->user()->isAdmin())
            <li class="nav-item">
                <a href="{{ route('operations.queue') }}" data-tooltip="Cola de sincronización" class="nav-link {{ request()->routeIs('operations.queue') ? 'active' : '' }}" title="Cola de sincronización">
                    <i class="bi bi-list-task"></i><span class="nav-label">Cola de sincronización</span>
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('operations.notifications') }}" data-tooltip="Notificaciones" class="nav-link {{ request()->routeIs('operations.notifications') ? 'active' : '' }}" title="Notificaciones">
                    <i class="bi bi-bell"></i><span class="nav-label">Notificaciones</span>
                </a>
            </li>
        </ul>

        {{-- Academia --}}
        <div class="nav-group-title">Academia</div>
        <ul class="app-nav">
            <li class="nav-item">
                <a href="{{ route('academia.dashboard') }}" data-tooltip="Dashboard Académico" class="nav-link {{ request()->routeIs('academia.dashboard') ? 'active' : '' }}" title="Dashboard Académico">
                    <i class="bi bi-mortarboard"></i><span class="nav-label">Dashboard Académico</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.ciclos.index') }}" data-tooltip="Ciclos Escolares" class="nav-link {{ request()->routeIs('academia.ciclos*') ? 'active' : '' }}" title="Ciclos Escolares">
                    <i class="bi bi-calendar-event"></i><span class="nav-label">Ciclos Escolares</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.grupos.index') }}" data-tooltip="Grupos" class="nav-link {{ request()->routeIs('academia.grupos*') ? 'active' : '' }}" title="Grupos">
                    <i class="bi bi-people"></i><span class="nav-label">Grupos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.alumnos.index') }}" data-tooltip="Alumnos" class="nav-link {{ request()->routeIs('academia.alumnos*') ? 'active' : '' }}" title="Alumnos">
                    <i class="bi bi-mortarboard"></i><span class="nav-label">Alumnos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.profesores.index') }}" data-tooltip="Profesores" class="nav-link {{ request()->routeIs('academia.profesores*') ? 'active' : '' }}" title="Profesores">
                    <i class="bi bi-person-badge"></i><span class="nav-label">Profesores</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.horarios.clase') }}" data-tooltip="Horarios y Asistencia" class="nav-link {{ request()->routeIs('academia.horarios*') ? 'active' : '' }}" title="Horarios y Asistencia">
                    <i class="bi bi-calendar-week"></i><span class="nav-label">Horarios</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.kardex.index') }}" data-tooltip="Kardex" class="nav-link {{ request()->routeIs('academia.kardex*') ? 'active' : '' }}" title="Kardex">
                    <i class="bi bi-file-earmark-text"></i><span class="nav-label">Kardex</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.cursos.index') }}" data-tooltip="Cursos" class="nav-link {{ request()->routeIs('academia.cursos*') ? 'active' : '' }}" title="Cursos">
                    <i class="bi bi-book"></i><span class="nav-label">Cursos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('academia.planes.index') }}" data-tooltip="Planes de Estudio" class="nav-link {{ request()->routeIs('academia.planes*') ? 'active' : '' }}" title="Planes de Estudio">
                    <i class="bi bi-journal-bookmark"></i><span class="nav-label">Planes</span>
                </a>
            </li>
            @if (auth()->check() && auth()->user()->isAdmin())
            <li class="nav-item">
                <a href="{{ route('firebird.index') }}" data-tooltip="Sincronizar Firebird" class="nav-link {{ request()->routeIs('firebird.*') ? 'active' : '' }}" title="Sincronizar datos desde Firebird">
                    <i class="bi bi-cloud-download"></i><span class="nav-label">Sincronizar Firebird</span>
                </a>
            </li>
            @endif
        </ul>

        @if (auth()->check() && auth()->user()->isAdmin())
            <div class="nav-group-title">Administración</div>
            <ul class="app-nav">
                <li class="nav-item">
                    <a href="{{ route('devices.create') }}" data-tooltip="Registrar dispositivo" class="nav-link {{ request()->routeIs('devices.create') ? 'active' : '' }}" title="Registrar dispositivo">
                        <i class="bi bi-plus-circle"></i><span class="nav-label">Registrar dispositivo</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('employees.create') }}" data-tooltip="Nuevo empleado" class="nav-link {{ request()->routeIs('employees.create') ? 'active' : '' }}" title="Nuevo empleado">
                        <i class="bi bi-person-plus"></i><span class="nav-label">Nuevo empleado</span>
                    </a>
                </li>
            </ul>
        @endif

        <div class="nav-group-title">Herramientas</div>
        <ul class="app-nav">
            <li class="nav-item">
                <a href="{{ route('attendances.export', request()->query()) }}" data-tooltip="Exportar CSV" class="nav-link" title="Exportar asistencias a CSV">
                    <i class="bi bi-file-earmark-spreadsheet"></i><span class="nav-label">Exportar asistencias</span>
                </a>
            </li>
        </ul>
        </div>

        @auth
            <div class="app-profile">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="profile-copy">
                    <div class="profile-name">{{ auth()->user()->name }}</div>
                    <div class="profile-role">{{ auth()->user()->isAdmin() ? 'Administrador' : 'Operador' }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="logout-button" title="Cerrar sesión" aria-label="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        @endauth
    </nav>

    <main class="app-main">
        @if (!empty($dash['alerts']))
            <div class="global-alerts" role="status" aria-live="polite">
                @foreach ($dash['alerts'] as $alert)
                    <div class="global-alert ga-{{ $alert['type'] }}" data-global-alert="{{ $alert['key'] ?? '' }}">
                        <i class="bi {{ $alert['icon'] ?? 'bi-info-circle' }} ga-icon"></i>
                        <span class="ga-text">{{ $alert['text'] }}</span>
                        @if (!empty($alert['action']))
                            <a class="ga-action" href="{{ $alert['action']['url'] }}">{{ $alert['action']['label'] }}</a>
                        @endif
                        @if (!empty($alert['key']))
                            <button class="ga-close" data-ga-close aria-label="Descartar"><i class="bi bi-x"></i></button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <header class="topbar">
            <div class="page-heading">
                <h1>@yield('title', 'Panel de control')</h1>
                <div class="breadcrumb-line">Panel <span class="mx-1">›</span> <strong>@yield('title', 'Resumen')</strong></div>
            </div>
            <button class="global-search" type="button" data-open-cmd aria-label="Búsqueda global (Ctrl+K)">
                <i class="bi bi-search"></i>
                <span class="flex-grow-1 text-start" style="font-size:13px;color:var(--text-tertiary)">Buscar en el panel...</span>
                <span class="search-kbd">Ctrl K</span>
            </button>
            <div class="topbar-actions">
                <button class="icon-button" type="button" data-notifications-toggle title="Notificaciones" aria-label="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="nav-badge" data-notif-badge style="display:none">0</span>
                </button>
                <button class="icon-button theme-toggle" type="button" data-theme-toggle title="Tema del sistema" aria-label="Cambiar tema" aria-pressed="false">
                    <span class="icon-crossfade">
                        <i class="bi bi-sun icon-sun"></i>
                        <i class="bi bi-moon-stars icon-moon"></i>
                    </span>
                </button>
                @php
    $cicloActual = app(\App\Services\CicloActualService::class)->current(request());
    $ciclosDisponibles = \App\Models\Academia\Ciclo::activo()
        ->orderByDesc('inicial')
        ->orderByDesc('final')
        ->orderByDesc('periodo')
        ->get();
@endphp

<span class="date-chip me-2"><i class="bi bi-calendar3 me-1"></i>{{ now()->locale('es')->isoFormat('D MMM YYYY') }}</span>

@if($ciclosDisponibles->isNotEmpty())
    <div class="dropdown ciclo-selector">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" id="cicloDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="max-width: 220px;">
            <i class="bi bi-calendar-event"></i>
            @if($cicloActual)
                <span class="text-truncate">{{ $cicloActual->label }}</span>
            @else
                <span class="text-muted">Sin ciclo</span>
            @endif
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="cicloDropdown" style="min-width: 240px;">
            <li class="dropdown-header text-muted small">Seleccionar ciclo escolar</li>
            @forelse($ciclosDisponibles as $ciclo)
                <li>
                    <a class="dropdown-item {{ $cicloActual && $cicloActual->label === $ciclo->label ? 'active' : '' }}" href="#"
                       onclick="setCiclo('{{ $ciclo->label }}'); return false;">
                        <i class="bi bi-calendar3 me-2"></i>{{ $ciclo->label }}
                        @if($ciclo->descripcion)
                            <small class="text-muted ms-1">- {{ Str::limit($ciclo->descripcion, 30) }}</small>
                        @endif
                    </a>
                </li>
            @empty
                <li><span class="dropdown-item text-muted">No hay ciclos disponibles</span></li>
            @endforelse
            @if($cicloActual)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="#" onclick="setCiclo(''); return false;">
                        <i class="bi bi-x-circle me-2"></i>Quitar ciclo
                    </a>
                </li>
            @endif
        </ul>
    </div>
@endif
                <button class="icon-button mobile-nav-toggle" type="button" data-sidebar-control aria-label="Contraer navegación" title="Contraer navegación">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
        </header>

        @if (isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <strong>Corrige los siguientes errores:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Feedback de sesión → toasts --}}
<div data-session-feedback
     data-success="{{ session('success') ? e(session('success')) : '' }}"
     data-error="{{ session('error') ? e(session('error')) : '' }}"
     hidden></div>

{{-- Stack de toasts --}}
<div class="toast-stack" data-toast-stack aria-live="polite" aria-atomic="false"></div>

{{-- Centro de notificaciones --}}
<div class="notify-flyout d-none" data-notify-flyout role="dialog" aria-label="Notificaciones">
    <div class="notify-header">
        <h3>Notificaciones</h3>
        <button type="button" class="notify-mark-all" data-mark-all>Marcar todas como leídas</button>
    </div>
    <div class="notify-tabs">
        <button type="button" class="notify-tab active" data-notify-tab="all">Todas</button>
        <button type="button" class="notify-tab" data-notify-tab="unread">No leídas</button>
    </div>
    <div class="notify-list" data-notify-list></div>
    <div class="notify-empty" data-notify-empty style="display:none">
        <i class="bi bi-bell"></i>
        <strong>No hay notificaciones nuevas</strong>
        <span class="notify-empty-sub">Estás al día.</span>
    </div>
    <div class="notify-footer">
        <a href="{{ route('attendances.index') }}">Ver todas las notificaciones →</a>
    </div>
</div>

{{-- Command palette --}}
<div class="cmd-overlay d-none" data-cmd-palette data-cmd-scrim>
    <div class="cmd-palette">
        <div class="cmd-input-row">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Buscar solicitudes, usuarios, acciones..." autocomplete="off" aria-label="Búsqueda global">
            <span class="search-kbd">Esc</span>
        </div>
        <div class="cmd-body" data-cmd-body></div>
        <div class="cmd-footer">
            <span><kbd>↑↓</kbd> navegar</span>
            <span><kbd>↵</kbd> seleccionar</span>
            <span><kbd>esc</kbd> cerrar</span>
        </div>
    </div>
</div>

{{-- Raíz de diálogos de confirmación --}}
<div data-confirm-root></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Ciclo selector functionality
    function setCiclo(label) {
        fetch('{{ route("academia.set-ciclo") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ciclo_label: label })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error setting ciclo:', error);
            alert('Error al cambiar el ciclo. Intenta de nuevo.');
        });
    }
</script>
<script>
    document.querySelectorAll('form[action*="/sync-"]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"], button:not([type])');
            if (!button) return;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';
        });
    });
</script>
@stack('scripts')
</body>
</html>