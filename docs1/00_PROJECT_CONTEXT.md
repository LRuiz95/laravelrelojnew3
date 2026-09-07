# 00_PROJECT_CONTEXT.md — Contexto del Proyecto

## Versión y Stack Tecnológico

| Componente | Versión | Notas |
|------------|---------|-------|
| Laravel | 10.x | Estructura clásica (Kernel, Console\Kernel, Exceptions\Handler, bootstrap/app.php) |
| PHP | ^8.1 | Tipado estricto, enums, readonly, constructor property promotion |
| Base de Datos | MySQL/MariaDB | Produccion y testing usan MySQL (no SQLite) |
| Frontend | Bootstrap 5 + CSS custom properties | No usa Tailwind; tokens en CSS variables (:root, data-theme) |
| JS Build | Vite 4 | npm run dev / npm run build |
| Queue | Database (sync en tests) | Jobs con reintentos y backoff |
| Cache/Session | Database/Array | |

## Paquetes Principales (composer.json)

| Paquete | Versión | Propósito |
|---------|---------|-----------|
| coding-libs/zkteco-php | ^0.0.35 | SDK para checadores ZKTeco (TCP/IP, puerto 4370) |
| guzzlehttp/guzzle | ^7.2 | Cliente HTTP (usado internamente por SDK o Firebird) |
| laravel/framework | ^10.0 | Framework core |
| laravel/sanctum | ^3.2 | API tokens (solo /api/user) |
| laravel/tinker | ^2.8 | REPL |

## Estructura del Proyecto

app/
Console/Commands/          # migrate:employees-to-central, firebird:sync
Data/                      # EmployeeCredentialPackage (DTO)
Events/                    # SyncProgressUpdated
Exceptions/                # ZktecoConnectionException, SyncCancelledException
Http/
  Controllers/           # 16 controladores (Auth, Dashboard, Device, Employee, Attendance, Operations, Academia/*)
  Middleware/            # Authenticate, EnsureAdmin, RedirectIfAuthenticated, PreventRequestsDuringMaintenance, TrustProxies, TrimStrings, VerifyCsrfToken, EncryptCookies
  Kernel.php             # Middleware global, groups, aliases
Jobs/                      # SyncDeviceJob, SyncEmployeeToDeviceJob, FirebirdSyncJob
Models/                    # 15 modelos + pivotes
  Academia/              # 12 modelos académicos
  Pivots/                # DeviceEmployee
Providers/                 # AppServiceProvider, AuthServiceProvider, EventServiceProvider, BroadcastServiceProvider, RouteServiceProvider
Services/                  # 7 servicios
  SyncStrategies/        # 3 estrategias + interface
  ZktecoService.php      # Core integración ZKTeco (1019 lineas)
  EmployeeDeviceSyncService.php
  FirebirdReader.php
  HorarioResolver.py
  CicloActualService.py
  KardexCalculator.php
  PersonaContratosResolver.php
View/Composers/            # AdminLayoutComposer
database/
migrations/                # 44 migraciones (evolución histórica)
seeders/                   # DeviceSeeder, AdminUserSeeder, DatabaseSeeder
factories/                 # UserFactory
resources/
css/app.css                # Design system completo (1117+ lineas)
js/app.js                  # Módulos UI (Theme, Heartbeat, Sidebar, Toast, Confirm, Notifications, CommandPalette)
views/
  layouts/admin.blade.php    # Layout principal con sidebar, topbar, theme toggle
  dashboard.blade.php        # KPIs, trend, pipeline, donut, recent
  devices/                   # index, show, create, edit
  employees/                 # index, create, edit
  fingerprints/              # index
  attendances/               # index, print
  operations/                # queue, notifications
  academia/                  # 20+ vistas académicas
  components/                # stat-card, drawer, data-table, badge
  partials/                  # sparkline, empty-state, donut
routes/
web.php                    # 164 lineas - todas las rutas web
api.py                     # Solo /api/user (sanctum)
console.py                 # Solo inspire
tests/
Feature/                   # 4 test files (DashboardRender, ZktecoSync, AttendanceFilter, Example)
Unit/                      # 1 test file
docs/
zkteco.md                  # Guía integración ZKTeco
checklist-backend.md       # Checklist auditoría backend
diseno.md                  # Guía diseño UI/UX
flujo-de-trabajo.md        # Flujo obligatorio
ANALISIS_MEJORAS.md        # Análisis sync Firebird

## Módulos Funcionales

### 1. Control de Asistencia (Biométrico ZKTeco)
- Dispositivos: Registro, estado (online/offline), sincronización usuarios/asistencias/huellas
- Empleados: Catálogo central (user_id = PIN global único), enrolamiento por dispositivo (pivote device_employee)
- Asistencias: Sincronización incremental, deduplicación, filtros por tipo/modo de checado
- Huellas: Templates binarios por dispositivo, re-atribución de huellas legadas
- Cola de sincronización: Jobs con progreso, reintentos, cancelación

### 2. Gestión Académica (Academia)
- Ciclos/Grupos/Alumnos/Profesores: CRUD completo
- Horarios: 5 vistas (clase, profesor, aula, base, persona)
- Kardex: Consulta, historial, impresión
- Cursos/Planes/Niveles/Materias/Turnos/Sedes/MetodosEval: Catálogos
- API AJAX: 10 endpoints para selects dependientes y carga dinámica

### 3. Sincronización Firebird (Legado)
- Lectura de BD Firebird externa MySQL
- Estrategias: Full, CycleDirect, CatalogSmart
- Tracking en firebird_syncs / firebird_sync_items

## Autenticación y Autorización

| Aspecto | Implementación |
|---------|----------------|
| Auth | Laravel default (email/password), AuthController |
| Roles | User.role = admin / operator (string) |
| Middleware | auth (autenticado), admin (solo role=admin) |
| Gates/Policies | No se usan - autorización solo via middleware admin |
| API | Sanctum solo para /api/user |

## APIs y AJAX

- Rutas API: Solo /api/user (Sanctum)
- AJAX interno: Endpoints en web.php con prefijo api/academia/* retornan JSON
- Heartbeat JS: Polling cada 20s a /kpis/json + badge notificaciones
- Command Palette: Ctrl+K para búsqueda global
- Toast notifications: Sistema propio (no librería externa)

## Servicios Externos

| Servicio | Protocolo | Configuración |
|----------|-----------|---------------|
| Checadores ZKTeco | TCP/IP (puerto 4370) | IP, puerto, password por dispositivo en BD |
| Firebird (legado) | PDO Firebird | DSN, user, pass en config/database.py |

## Arquitectura General

Request (Web)
    Middleware (auth, admin, etc.)
    Controller (thin - delega a Services/Jobs)
    Services (ZktecoService, EmployeeDeviceSyncService, FirebirdReader, etc.)
    Models / Eloquent / DB
    Response (View / JSON)
    Blade + JS (Heartbeat, Sidebar, Toasts, Confirm, Notifications, CommandPalette)

## Variables de Entorno Críticas (.env)

APP_ENV=local
APP_KEY=base64:...
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rh_reloj
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database

Nota: No se almacenan secretos reales en este documento.

## Información Crítica del Entorno

- Tests: Corren sobre MySQL (rh_reloj_testing), migrate:fresh en cada proceso (phpunit.xml)
- ZKTeco SDK: coding-libs/zkteco-php ^0.0.35 - comunicación UDP/TCP, timeout configurable
- Dark Mode: 3 estados (light/dark/system), persistencia en localStorage (dash-theme), script inline en head para evitar FOUC
- Sidebar: 2 estados (expandido/colapsado), persistencia en localStorage (dash-sidebar-collapsed), tooltips en modo colapsado
- Zona horaria: Dispositivos reportan hora local sin TZ; servidor MySQL en UTC - conversión pendiente de auditoría
