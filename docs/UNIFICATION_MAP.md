# Mapa de Fusión — laravelrelojnew ↔ ProyectoBase

> **Generado en Fase 0 — Solo auditoría, sin implementación**

---

## 1. Métricas Comparativas

| Métrica | laravelrelojnew (Principal) | ProyectoBase (Secundario) | Ratio |
|---------|------------------------------|----------------------------|-------|
| **Framework** | Laravel 10.0 | PHP nativo (sin framework) | — |
| **PHP** | ^8.1 | 8.1+ (declare strict_types) | Paridad |
| **Base de datos** | MySQL (Eloquent) | Firebird + MySQL (PDO raw) | Diferente |
| **Archivos PHP** | ~60 (app/, tests/) | 28 (core/ + sections/) | ~2.1x |
| **Líneas código PHP** | ~8,500 | ~12,000 | 0.7x |
| **Modelos/Entidades** | 7 (Eloquent) | 0 (queries directas) | — |
| **Controladores** | 6 (Resource + custom) | 0 (section files) | — |
| **Servicios** | 3 (Zkteco, Sync, Credential) | 0 (helpers globales) | — |
| **Jobs/Colas** | 2 (SyncDevice, SyncEmployee) | 0 | — |
| **Eventos/Listeners** | 1 (SyncProgressUpdated) | 0 | — |
| **Middleware** | 10 (incl. EnsureAdmin) | 0 (Auth::requireAuth inline) | — |
| **Requests/FormRequests** | 0 (validación en controllers) | 0 | — |
| **Policies** | 0 (roles en middleware) | 0 (Auth::hasPermission) | — |
| **Vistas Blade** | 23 | 0 (PHP embebido en sections) | — |
| **Componentes Blade** | 1 (stat-card) | 0 | — |
| **Archivos JS** | 2 (app.js, bootstrap.js) | 2 (dashboard.js, asistencia-clases.js) | Paridad |
| **Archivos CSS** | 1 (app.css ~63KB) | 1 (dashboard.css ~42KB) | 1.5x |
| **Migraciones** | 22 | 0 (schema en SQL dumps) | — |
| **Seeders** | 3 | 0 | — |
| **Tests** | 5 (4 Feature + 1 Unit) | 0 | — |
| **Rutas web** | 36 (web.php) | 1 entry point (rh-dashboard2.php) | — |
| **Dependencias Composer** | 8 prod + 7 dev | 0 | — |
| **Dependencias NPM** | 3 dev (vite, laravel-vite, axios) | 0 | — |

**Conclusión de tamaño**: `ProyectoBase ~= laravelrelojnew` en líneas de código, pero **arquitecturas radicalmente diferentes**. ProyectoBase es más grande en código PHP bruto porque no usa ORM ni framework.

---

## 2. Tabla de Decisiones por Área

| Área | laravelrelojnew | ProyectoBase | Decisión | Riesgo | Justificación |
|------|-----------------|--------------|----------|--------|---------------|
| **Framework** | Laravel 10 | PHP nativo | **KEEP_MAIN** | Bajo | Principal ya tiene estructura madura, testing, queue, schedulers |
| **Autenticación** | Laravel Auth (web guard, session, User model) | Auth class estática + config PHP array + sesión nativa | **KEEP_MAIN** | Bajo | Laravel Auth integra policies, gates, password reset, remember me |
| **Usuarios/Sistema** | User model + roles (admin/operator via middleware) | config/auth.php array users + roles/permissions | **KEEP_MAIN** | Medio | Migrar users de ProyectoBase a tabla users de Laravel (seed) |
| **Sesiones** | Laravel session (database/cookie) | PHP $_SESSION nativa | **KEEP_MAIN** | Bajo | Laravel session más robusto, configurable |
| **Permisos/Autorización** | Middleware EnsureAdmin + isAdmin() en User | Auth::hasPermission/hasRole sobre config array | **ADAPT** | Medio | Mantener middleware Laravel; migrar matriz permisos a DB/Policies |
| **Layout principal** | admin.blade.php (sidebar + topbar + theme toggle) | rh-dashboard2.php (sidebar + topbar + ciclo selector) | **MERGE** | Alto | Ambos tienen sidebar retráctil, topbar, dark mode. Unificar en layout Laravel |
| **Navegación/Menú** | Nav fijo en layout (ul.app-nav) | Array $nav_menu + render dinámico en sidebar | **ADAPT** | Medio | Migrar estructura $nav_menu a config/navigation.php + view composer |
| **Componentes UI** | stat-card.blade.php + Bootstrap 5 + CSS variables | CSS custom (dashboard.css) + badges, KPIs, tablas, drawers | **MERGE** | Alto | Sistema de diseño similar (dark-first, CSS variables). Unificar tokens en tailwind.config.js |
| **Dark Mode** | data-theme en <html> + localStorage (dash-theme) | Solo dark (CSS variables --bg, --card, etc.) | **KEEP_MAIN** | Bajo | Principal ya tiene 3 estados (light/dark/system) + persistencia FOUC-free |
| **Base de datos** | MySQL (Eloquent, migraciones, FK, índices) | Firebird (origen) + MySQL (destino, PDO raw) | **KEEP_MAIN** | Medio | Mantener MySQL + Eloquent. Firebird solo como fuente ETL externa |
| **Conexión DB** | config/database.php (Laravel standard) | Database.php singleton (Firebird + MySQL) | **ADAPT** | Medio | Mantener Laravel config. Crear servicio FirebirdReader para ETL |
| **Modelos: Empleado** | Employee (catálogo central) + DeviceEmployee pivot | EMPLEADOS (Firebird/MySQL) + PROFESORES | **MERGE** | **Crítico** | Conceptos distintos: Principal = checadores; Base = personal admin + docentes. Requiere análisis semántico |
| **Modelos: Dispositivo** | Device (ZKTeco IP, port, password, serial) | No existe | **KEEP_MAIN** | Bajo | Exclusivo del principal |
| **Modelos: Asistencia** | Attendance (device_id, employee_id, user_id, state, type, recorded_at) | EMPLEADOS_ASISTENCIA (checadas admin/docente, entrada/salida/comida) | **MERGE** | **Alto** | Esquemas compatibles pero semántica distinta. Principal: biométrico; Base: checadas manuales/legacy |
| **Modelos: Huella** | Fingerprint (device_id, employee_id, finger, template, hash) | No existe | **KEEP_MAIN** | Bajo | Exclusivo del principal |
| **Sincronización ZKTeco** | ZktecoService + SyncDeviceJob + DeviceSync model | No existe | **KEEP_MAIN** | Bajo | Core business del principal |
| **Sincronización Firebird→MySQL** | No existe (archivos en docs/ARCHIVE/) | sincronizar.php + helpers (smart sync, ciclo/alumno/catálogo) | **NEW** | **Alto** | Funcionalidad crítica de ProyectoBase. Debe adaptarse como comando/job Laravel |
| **Asistencia clases** | No existe | asistencia-clases.php (grid captura por sesión/día/grupo) | **NEW** | Medio | Módulo académico nuevo. Evaluar si pertenece al principal o app separada |
| **Dashboard KPIs** | 5 KPIs (chequeos, empleados, entradas, salidas, devices online) | 3 KPIs (activos, bajas, total) + checadas periodo | **MERGE** | Medio | Unificar en DashboardController extendido |
| **Gráficas** | SVG inline (tendencia horaria/diaria/mensual + donut) | No gráfico (solo barras CSS .bar-chart) | **KEEP_MAIN** | Bajo | Principal más avanzado |
| **Tablas/Listados** | Blade tables + pagination + export CSV | HTML tables en sections + pagination custom + export CSV | **ADAPT** | Medio | Migrar a Blade components + Laravel pagination |
| **Export CSV** | AttendanceController::export() | csv_download() helper en sections | **MERGE** | Bajo | Unificar en trait/servicio Exportable |
| **Búsqueda/Filtros** | Query params en controllers | Query params en sections (GET) | **KEEP_MAIN** | Bajo | Patrón idéntico |
| **Paginación** | Laravel paginate() + links() | render_pagination() helper custom | **KEEP_MAIN** | Bajo | Laravel pagination más robusto |
| **Modales/Drawers** | Bootstrap 5 modals + toast stack + confirm dialogs | CSS drawer overlay (empDrawer, asistDrawer) | **MERGE** | Medio | Unificar en componentes Blade + Alpine.js o Vue |
| **Notificaciones/Toasts** | Toast stack (data-toast-stack) + session flash | alert-error/alert-success/alert-info inline | **KEEP_MAIN** | Bajo | Principal más completo |
| **Ciclo escolar** | No existe | $ciclo_principal global (URL + session + default) | **NEW** | Medio | Si se integra módulo académico, portar concepto de ciclo |
| **Configuración** | .env + config/*.php | .env + config/*.php (custom loader) | **KEEP_MAIN** | Bajo | Laravel config superior |
| **Logging** | Laravel Log (stack, daily, slack) | Solo error_log / Log::warning en ZktecoService | **KEEP_MAIN** | Bajo | |
| **Testing** | PHPUnit (MySQL testing DB) | No tests | **KEEP_MAIN** | Bajo | |
| **Queue/Jobs** | database driver + SyncDeviceJob (WithoutOverlapping) | No queue | **KEEP_MAIN** | Bajo | |
| **Scheduler** | Kernel.php (no jobs programados aún) | No scheduler | **KEEP_MAIN** | Bajo | |
| **Validación** | Inline en controllers (TODO: FormRequests) | Inline en sections (manual) | **KEEP_MAIN** | Bajo | Migrar a FormRequests en principal |
| **Manejo errores** | Handler.php + custom exceptions (ZktecoConnection, SyncCancelled) | try/catch + session flash errors | **KEEP_MAIN** | Bajo | |
| **CSRF** | Laravel VerifyCsrfToken middleware | Auth::generateCSRFToken/validateCSRFToken | **KEEP_MAIN** | Bajo | Laravel CSRF automático |
| **API Endpoints** | kpisJson, queueData, syncStatus, progress | api param en rh-dashboard2.php (grupos, alumnos, ciclos, planes) | **ADAPT** | Medio | Migrar endpoints AJAX a rutas api.php + controllers |

---

## 3. Clasificación Final por Componente (ProyectoBase → Principal)

### KEEP_MAIN (Principal gana, no tocar)
- Framework Laravel 10 + estructura clásica
- Autenticación Laravel (User, guards, password reset, session)
- Base de datos MySQL + Eloquent + Migraciones + FK + Índices
- Modelos: Device, Fingerprint, DeviceSync, DeviceSyncItem, DeviceEmployee (pivot)
- ZktecoService + SyncDeviceJob + SyncEmployeeToDeviceJob
- Eventos: SyncProgressUpdated
- Middleware: EnsureAdmin, EncryptCookies, VerifyCsrfToken, etc.
- Layout admin.blade.php (sidebar, topbar, theme-toggle, notifications, command palette)
- Dark mode 3-estados + FOUC-free script
- DashboardController (KPIs, pipeline, donut, trend, recent)
- Vistas Blade + componentes (stat-card, partials: donut, sparkline, empty-state)
- Bootstrap 5 + Bootstrap Icons + Vite
- Queue database + WithoutOverlapping
- Logging Laravel
- Testing PHPUnit (MySQL testing DB)
- CSRF Laravel
- Rutas web.php + api.php (futuro)

### ADAPT (Existe en Base, no en Principal → portar adaptando a Laravel)
| Componente | Origen | Acción requerida |
|------------|--------|------------------|
| Matriz permisos/roles | config/auth.php | Migrar a tabla roles/permissions o Policies Laravel |
| Navegación dinámica | $nav_menu array | Config navigation.php + ViewComposer (AdminLayoutComposer ya existe) |
| Ciclo escolar global | resolve_ciclo_principal() | Service CicloActual + middleware/global helper |
| Helpers resolución nombres | resolve_sede, resolve_turno, resolve_materia, etc. | Eloquent Accessors/Mutators o Services |
| Export CSV genérico | csv_download() | Trait Exportable en controllers |
| Paginación custom | render_pagination() | Usar Laravel paginate() + view personalizada |
| API AJAX filtros cascada | api param (grupos, alumnos, planes) | Rutas api.php + controllers dedicados |
| Drawers (empleado, asistencia) | CSS drawer overlay | Componentes Blade + Alpine.js (modal/drawer) |
| Alertas inline | alert-error/success/info | Toast stack + session flash (ya existe) |
| Badges semánticos | CSS .badge--* | Mantener clases CSS, migrar a tokens Tailwind |
| Tablas con scroll hint | .table-container + JS scroll-hint | Componente Blade TableResponsive |

### MERGE (Existe en ambos, combinar selectivamente)
| Componente | Principal | Base | Estrategia |
|------------|-----------|------|------------|
| **Empleado/Persona** | Employee (catálogo central, user_id=PIN) | EMPLEADOS + PROFESORES (Firebird) | **Análisis semántico requerido**. Employee principal = persona que checa. Base = personal admin + docentes. ¿Unificar en Employee con type? ¿O Employee + Teacher separados? |
| **Asistencia** | Attendance (biométrico, device_id, type/state ZKTeco) | EMPLEADOS_ASISTENCIA (checadas admin/docente, entrada/salida/comida) | **Unificar esquema**: attendance_type enum (biometric, manual), source_device_id nullable. Mantener state/type ZKTeco + campos comida. |
| **Dashboard KPIs** | 5 KPIs asistencia + devices | 3 KPIs empleados + checadas | Extender DashboardController con KPIs unificados (configurable por módulo) |
| **Sistema diseño (CSS)** | app.css (Bootstrap override + CSS vars) | dashboard.css (CSS vars dark-first, KPIs, tablas, badges) | **Unificar en Tailwind config**: tokens de color, spacing, radius. Migrar dashboard.css → Tailwind + components Blade |
| **Sidebar retráctil** | Bootstrap sidebar (data-sidebar-control) | CSS sidebar (toggleSidebar JS) | Mantener comportamiento: tooltip en colapsado, persistencia localStorage, responsive breakpoints |
| **Topbar** | Breadcrumb + search + notif + theme + date + mobile toggle | Título + user + ciclo selector + logout | Fusionar: mantener topbar principal + agregar selector ciclo (si módulo académico) |
| **Modales/Feedback** | Bootstrap Modal + Toast + Confirm | CSS Drawer + inline alerts | Unificar en componentes Blade reutilizables |

### NEW (Funcionalidad solo en Base → incorporar como módulo nuevo)
| Funcionalidad | Archivos Base | Complejidad | Notas |
|---------------|---------------|-------------|-------|
| **Sincronización Firebird→MySQL** | sincronizar.php, helpers sync_* | **Muy Alta** | ETL complejo: 3 fases (ciclo directo, alumnos por ciclo, catálogos smart sync). Requiere: comando artisan, job queue, modelos Firebird (read-only), config conexión Firebird |
| **Módulo Académico** | alumnos, docentes, grupos, inscripciones, kardex, horarios-clase/curso/aula, planes, materias, niveles, turnos, sedes, ciclos, métodos eval, contratos | **Muy Alta** | Dominio completo distinto (académico vs asistencia). Evaluar: ¿mismo Laravel? ¿Microservicio? ¿Package separado? |
| **Asistencia de clases** | asistencia-clases.php + asistencia-clases.js | Media | Grid captura por sesión/día/grupo + drawer edición. Si se integra académico, viene incluido |
| **Horarios laborales** | horarios-laborales.php, horario-persona.php, horario-base.php | Media | Plantillas horario admin + asignación personal |
| **Reportes** | reportes.php | Baja | Exportaciones variadas |
| **Config catálogos** | niveles, turnos, sedes, contratos, horario-base | Baja | Tablas de referencia |

### DISCARD (Obsoleto, duplicado, incompatible)
| Componente | Motivo |
|------------|--------|
| Database.php singleton | Reemplazado por Laravel DB facade + Eloquent |
| Auth.php class estática | Reemplazado por Laravel Auth |
| Helpers.php (q, qm, fb, db, csv_download, resolve_*) | Reemplazado por Eloquent, Services, Accessors |
| login.php / logout.php | Reemplazado por AuthController + rutas web |
| rh-dashboard2.php (entry point único) | Reemplazado por routing Laravel + controllers |
| Sections/*.php (PHP embebido HTML) | Reemplazado por Blade templates + Controllers |
| CSS dashboard.css completo | Tokens migrados a Tailwind; componentes a Blade |
| JS dashboard.js (sidebar, drawer, ciclo, scroll-hint) | Comportamiento nativo en layout principal + Alpine.js |
| Firebird DSN hardcoded en config/database.php | Mover a .env + config/database.php connection 'firebird' |

### REPLACE (Solo si razón técnica clara — **ninguno identificado por ahora**)

---

## 4. Duplicaciones Semánticas Detectadas

| Concepto | Principal | Base | ¿Mismo significado? | Análisis |
|----------|-----------|------|---------------------|----------|
| **Employee/Empleado** | Persona que checa en ZKTeco (user_id = PIN/badge global) | Personal administrativo (NUMEMPLEADO) | **NO** | Principal: identidad biométrica. Base: nómina/RRHH. Clave distinta (user_id vs NUMEMPLEADO) |
| **Attendance/Asistencia** | Registro biométrico (device_id, type/state ZKTeco) | Checada manual admin/docente (entrada/salida/comida) | **Parcial** | Ambos son "marcados de tiempo" pero origen y campos difieren |
| **Device/Dispositivo** | Checador ZKTeco (IP, port, password, serial) | No existe | — | Exclusivo principal |
| **Sync/Sincronizar** | ZKTeco ↔ MySQL (bidireccional usuarios/huellas/asistencias) | Firebird → MySQL (unidireccional, ETL masivo) | **NO** | Protocolos, direccionalidad, frecuencia totalmente distintos |
| **Dashboard** | Panel control asistencia (KPIs, tendencia, donut, pipeline) | Panel RH académico (KPIs personal, checadas, navegación módulos) | **Parcial** | Mismo patrón visual, datos distintos |
| **Ciclo** | No existe | Ciclo escolar (INICIAL-FINAL-PERIODO) | — | Nuevo dominio |
| **Sede/Campus** | device_employee no tiene sede; Device no tiene sede | ID_CAMPUS en EMPLEADOS, PROFESORES, GRUPOS, HORARIOS | **Parcial** | Principal podría necesitar sede para multi-sucursal |
| **Turno** | No existe en modelo (solo en device_employee role) | TURNO en GRUPOS, PROFESORES, EMPLEADOS_CFGHORARIOS | **Nuevo** | Dominio académico/laboral |

---

## 5. Resumen Ejecutivo

- **ProyectoBase NO es un proyecto Laravel**. Es una aplicación PHP nativa con arquitectura de "page controller" (un entry point + sections include).
- **Dominio distinto**: Principal = **Control de asistencia biométrico (ZKTeco)**. Base = **Gestión académica + RRHH (Firebird legacy → MySQL)**.
- **Overlap real**: Solo en "personal" (empleados) y "asistencia" (checadas), pero con semántica y esquemas diferentes.
- **Riesgo de integración: ALTO** — No es "fusionar dos Laraveles"; es "incorporar módulo académico+ETL Firebird en Laravel existente".
- **Complejidad: MUY ALTA** — Requiere: (1) modelos Firebird read-only, (2) comandos ETL programables, (3) dominio académico completo, (4) unificación semántica Employee/Attendance.
- **Tiempo relativo estimado: 4x+** — Por la magnitud del módulo académico + ETL Firebird, no por el principal.