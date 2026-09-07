# 01_ARCHITECTURE.md — Arquitectura del Sistema

## Flujo de Request Completo

HTTP Request
    Kernel.php Middleware Global (TrustProxies, HandleCors, PreventRequestsDuringMaintenance, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull)
    Route Middleware Groups (web: EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, VerifyCsrfToken, SubstituteBindings)
    Route Middleware Aliases (auth, admin, guest, throttle, etc.)
    Controller Method
    Form Request Validation (inline en controladores - NO hay Form Requests dedicados)
    Service Classes (ZktecoService, EmployeeDeviceSyncService, FirebirdReader, SyncStrategies)
    Jobs (SyncDeviceJob, SyncEmployeeToDeviceJob, FirebirdSyncJob) Queue
    Models / Eloquent ORM / Query Builder
    Database (MySQL)
    Response: View (Blade) o JSON
    Frontend: Bootstrap 5 + CSS Variables + Vanilla JS Modules

## Capas y Responsabilidades

### 1. Routes (routes/web.php)
- 164 lineas, todas las rutas web
- Prefijos: /devices, /employees, /attendances, /academia, /firebird, /operations
- Middleware: auth global, admin en rutas de escritura, guest en login
- API AJAX bajo api/academia/* retornan JSON

### 2. Middleware (app/Http/Kernel.py)
| Alias | Clase | Uso |
|-------|-------|-----|
| auth | Authenticate | Rutas autenticadas |
| admin | EnsureAdmin | Solo role=admin (403 si no) |
| guest | RedirectIfAuthenticated | Login/register |
| throttle | ThrottleRequests | Rate limiting (no usado en rutas criticas) |

### 3. Controllers (16 total)
**Core Asistencia:**
- AuthController - Login/logout
- DashboardController - KPIs, trend, pipeline, donut, recent
- DeviceController - CRUD dispositivos + acciones sync (13 metodos)
- EmployeeController - CRUD empleados + huellas + enrolamiento (20 metodos)
- AttendanceController - Listado, export, print, filtros
- OperationsController - Cola sync, notificaciones

**Academia (8 controladores):**
- DashboardController - Dashboard academico
- CicloController, GrupoController, AlumnoController, ProfesorController, HorarioController, KardexController, CursoController, PlanController
- ApiController - 10 endpoints AJAX

### 4. Services (7 servicios + 3 estrategias)
| Servicio | Responsabilidad |
|----------|-----------------|
| ZktecoService | Core - Toda comunicacion con checadores (1019 lineas) |
| EmployeeDeviceSyncService | Empaquetado y sincronizacion credenciales empleado-dispositivo |
| FirebirdReader | Lectura BD Firebird externa (PDO) |
| HorarioResolver | Resolucion horarios academicos |
| CicloActualService | Ciclo escolar por defecto |
| KardexCalculator | Calculo kardex alumnos |
| PersonaContratosResolver | Resolucion contratos persona |

**SyncStrategies:**
- SyncStrategyInterface - Contrato
- FullSyncStrategy - Sincronizacion completa
- CycleDirectSync - Por ciclo directo
- CatalogSmartSync - Inteligente por catalogo

### 5. Jobs (3 jobs)
| Job | Queue | Retries | Backoff | Middleware |
|-----|-------|---------|---------|------------|
| SyncDeviceJob | default | 3 | 30s, 120s, 300s | WithoutOverlapping(device-sync:{id}) |
| SyncEmployeeToDeviceJob | default | 3 | 30s, 120s, 300s | WithoutOverlapping(employee-device-sync:{emp}:{dev}) |
| FirebirdSyncJob | default | 3 | 30s, 120s, 300s | WithoutOverlapping |

### 6. Models (15 + 1 pivot)
**Core:**
- User - Auth, role (admin/operator)
- Device - Checador ZKTeco (IP, puerto, password encrypted, status)
- Employee - Catalogo central (user_id unico global)
- Attendance - Registro de checada (device_id, employee_id, user_id, state, type, recorded_at)
- Fingerprint - Template biometrico (employee_id, device_id, finger, template, template_hash)
- DeviceSync - Tracking sincronizacion (status, operation, stage, processed, total, counts)
- DeviceSyncItem - Detalle por credencial (credential_type, finger, status, attempts)

**Academia (12):**
- Ciclo, Grupo, Alumno, Profesor, HorarioDet, Curso, CursoDet, Plan, Nivel, Materia, MetodoEval, Sede, Turno, Contrato, SesionBase, AlumnoGrupo, AlumnoKardex

**Pivot:**
- DeviceEmployee - Enrolamiento empleado-dispositivo (device_uid, role, card_number, password encrypted, active, fingerprint_count)

### 7. Database (44 migraciones)
- Evolucion historica desde 2014 (users, password_resets) hasta 2026 (academia, firebird, device_employee)
- Migraciones clave 2026: centralizacion empleados, device_employee pivot, device_sync_items, academia

### 8. Views (Blade)
- Layout principal: layouts/admin.blade.php - Sidebar, topbar, theme toggle, toasts, notifications, command palette
- Dashboard: KPIs (6 tarjetas), trend chart (SVG), pipeline (6 steps), donut chart (SVG), recent table
- Components: stat-card, drawer, data-table, badge, sparkline, empty-state, donut
- Academia: 20+ vistas organizadas por modulo

### 9. Frontend JS (resources/js/app.js - 681 lineas)
Modulos auto-ejecutables:
1. Theme - light/dark/system, localStorage, FOUC prevention
2. Heartbeat - Polling 20s: KPIs + notification badge
3. Sidebar - Collapse/expand, mobile off-canvas, localStorage, Ctrl+B
4. Toast - Stack, 4 tipos, progress bar, auto-dismiss, actions
5. Confirm - Dialog modal, require-type para destructivas, ESC para cerrar
6. Notifications - Flyout, tabs (all/unread), persist read state
7. CommandPalette - Ctrl+K, fuzzy search paginas/dispositivos/empleados
8. Global Alerts - SessionStorage dismiss
9. Forms data-sync - Fetch POST + toast response

### 10. CSS (resources/css/app.css - 1117+ lineas)
- Design tokens en CSS variables (:root, [data-theme=dark], [data-theme=light])
- Colores semanticos: --primary, --cat-*, --success, --warning, --error, --info
- Sidebar: --sidebar-expanded (258px), --sidebar-collapsed (72px)
- Responsive: breakpoints 1024px, 640px
- Componentes: cards, tables, badges, buttons, inputs, modals, toasts, pipeline, donut, trend, sparkline

## Dependencias Entre Componentes

ZktecoService - Device, Employee, Attendance, Fingerprint, DeviceSync, DeviceEmployee
    SyncDeviceJob - ZktecoService, DeviceSync, DeviceSyncItem
    SyncEmployeeToDeviceJob - EmployeeDeviceSyncService, ZktecoService
    EmployeeDeviceSyncService - ZktecoService, Employee, Device, Fingerprint

FirebirdSyncJob - FirebirdReader, SyncStrategies (Full/CycleDirect/CatalogSmart)
    FirebirdReader - PDO Firebird
    Models Academia (12)

DashboardController - Attendance, Device, Employee, Fingerprint
DeviceController - Device, Employee, Attendance, Fingerprint, DeviceSync, ZktecoService
EmployeeController - Employee, Device, Fingerprint, DeviceSync, ZktecoService
AttendanceController - Attendance, Device
OperationsController - DeviceSync

ApiController (Academia) - CicloActualService, HorarioResolver, Models Academia

## Patrones Identificados

| Patron | Ubicacion | Ejemplo |
|--------|-----------|---------|
| Service Layer | app/Services/* | ZktecoService, EmployeeDeviceSyncService |
| Job/Queue | app/Jobs/* | SyncDeviceJob, SyncEmployeeToDeviceJob |
| Strategy | app/Services/SyncStrategies/* | FullSyncStrategy, CycleDirectSync |
| DTO/Data | app/Data/* | EmployeeCredentialPackage |
| Pivot Model | app/Models/Pivots/DeviceEmployee.py | BelongsToMany con pivot personalizado |
| View Composer | app/View/Composers/AdminLayoutComposer.py | Datos globales layout |
| Event | app/Events/SyncProgressUpdated.py | Broadcast progreso sync |
| Exception Custom | app/Exceptions/ZktecoConnectionException.py | Errores especificos ZKTeco |

## Comunicacion ZKTeco (docs/zkteco.md)

- Protocolo: TCP/IP puerto 4370 (configurable)
- SDK: coding-libs/zkteco-php ^0.0.35
- Timeout socket: 15s base, adaptativo hasta 60s
- Reintentos: 3 (corto), 5 (largo) con backoff 800ms * attempt
- Estado dispositivo: boot() actualiza devices.status (online/offline) en cada intento
- Sincronizacion: Incremental (insertOrIgnore), deduplicacion por unique index

## Zona Horaria (Pendiente de Auditoria)
- Dispositivos reportan hora local SIN timezone
- MySQL servidor en UTC
- No hay conversion explicita documentada al insertar/mostrar
- Riesgo: desfasaje horario en asistencias
