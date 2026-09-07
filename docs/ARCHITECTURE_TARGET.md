# Arquitectura Objetivo — laravelrelojnew Unificado

> **Generado en Fase 0** — Describe el estado final tras incorporar funcionalidades de ProyectoBase.
> **NO define nombres de módulos no respaldados por auditoría** (ej. no asume "Rhges").

---

## 1. Visión General

```
laravelrelojnew (Única aplicación final)
│
├── 🎯 Núcleo Principal (Existente — MANTENER)
│   ├── Autenticación Laravel (User, Guards, Policies, Password Reset)
│   ├── Control Asistencia Biométrico (ZKTeco)
│   │   ├── Device ↔ Employee (multi-dispositivo, pivot DeviceEmployee)
│   │   ├── Attendance (biométrico: type/state ZKTeco)
│   │   ├── Fingerprint (plantillas por dispositivo)
│   │   ├── ZktecoService + SyncDeviceJob + Queue
│   │   └── Dashboard KPIs (chequeos, empleados, dispositivos, tendencias)
│   ├── UI System (Layout admin, Sidebar, Topbar, Dark Mode 3-estados, Toast, Cmd Palette)
│   └── Infraestructura (Queue DB, Scheduler, Logging, Testing MySQL, Vite/Alpine.js)
│
├── 🔄 Módulo ETL Firebird → MySQL (NUEVO — desde ProyectoBase)
│   ├── FirebirdReader (Service, PDO wrapper, read-only)
│   ├── FirebirdSyncJob (ShouldQueue, WithoutOverlapping, Progress Events)
│   ├── SyncStrategies (CycleDirect, AlumnosByCycle, CatalogSmart)
│   ├── Models: FirebirdSync, FirebirdSyncItem (tracking + log)
│   ├── Command: php artisan firebird:sync {ciclo?} {--catalogos} {--delete-orphans}
│   └── Schedule: sync automático configurable (diario/nocturno)
│
├── 🎓 Módulo Académico + RRHH (NUEVO — desde ProyectoBase)
│   ├── Dominio: Ciclos, Grupos, Alumnos, Kardex, Profesores, Horarios, Planes, Catálogos
│   ├── Models (15+): Ciclo, Nivel, Turno, Sede, Plan, Materia, MetodoEval, Contrato,
│   │   Grupo, Alumno, AlumnoGrupo, AlumnoKardex, Profesor, HorarioDet, Curso, SesionBase
│   ├── Services: CicloActual, HorarioResolver, KardexCalculator, PersonaContratosResolver
│   ├── Controllers: Ciclo, Grupo, Alumno, Kardex, HorarioClase/Curso/Aula, Profesor, Plan, Materia
│   ├── Views: academia/* (Blade, reutiliza layout + componentes)
│   ├── API: academia.grupos-por-ciclo, alumnos-por-grupo, ciclos-disponibles, planes-por-nivel
│   └── Asistencia Clases: Grid captura por sesión/día/grupo + Drawer edición
│
├── 👥 Unificación Persona/Asistencia (MERGE — Principal + Base)
│   ├── Employee (catálogo unificado)
│   │   ├── type: enum (biometric, admin, teacher)
│   │   ├── numero_empleado, clave_profesor (unique, nullable)
│   │   ├── departamento, cargo, contrato, status_actual, fecha_ingreso, id_campus, nivel, tarjeta_id
│   │   └── Relaciones: devices(), attendances(), fingerprints(), profesor? (hasOne)
│   ├── Attendance (esquema unificado)
│   │   ├── attendance_type: enum (biometric, manual_admin, manual_teacher, class)
│   │   ├── source: enum (zkteco, manual, class)
│   │   ├── Campos ZKTeco: type, state, device_id
│   │   ├── Campos manuales: hora_entrada, hora_salida, hora_salida_comer, hora_regreso_comer
│   │   └── Índice único: (employee_id, recorded_at, device_id, attendance_type)
│   └── Profesor (opcional: modelo separado o STI en Employee)
│
└── 🎨 Sistema de Diseño Unificado (MERGE — Principal + Base)
    ├── Tokens: tailwind.config.js (colors, spacing, radius, shadows, breakpoints, darkMode: 'class')
    ├── Componentes Blade: <x-stat-card>, <x-data-table>, <x-badge>, <x-drawer>, <x-modal>, <x-toast>, <x-pagination>
    ├── Alpine.js: Sidebar, Drawers, Modals, Tabs, Ciclo Selector, Theme Toggle, Search
    ├── Dark Mode: 3 estados (light/dark/system) + FOUC-free + cobertura total academia
    └── Icons: Bootstrap Icons (CDN) + emojis legacy migrados a iconos
```

---

## 2. Arquitectura por Capas (Laravel 10 — Estructura Clásica)

### 2.1 Capa de Presentación (HTTP + Views)

```
routes/
├── web.php                    # Rutas principales (auth, dashboard, devices, employees, attendances, operations)
├── academia.php               # Rutas módulo académico (Resource + Custom) → prefix 'academia'
└── api.php                    # API endpoints (kpisJson, queueData, academia AJAX)

app/Http/
├── Controllers/
│   ├── AuthController.php
│   ├── DashboardController.php          # Extendido: KPIs academia + asistencia
│   ├── DeviceController.php
│   ├── EmployeeController.php
│   ├── AttendanceController.php
│   ├── OperationsController.php
│   └── Academia/                        # NUEVO namespace
│       ├── CicloController.php
│       ├── GrupoController.php
│       ├── AlumnoController.php
│       ├── KardexController.php
│       ├── HorarioClaseController.php
│       ├── HorarioCursoController.php
│       ├── HorarioAulaController.php
│       ├── ProfesorController.php
│       ├── PlanController.php
│       ├── MateriaController.php
│       └── AsistenciaClaseController.php
├── Middleware/
│   ├── EnsureAdmin.php
│   ├── EnsureCicloActivo.php            # NUEVO: valida ciclo_actual en sesión/URL
│   └── ... (Laravel defaults)
└── View/Composers/
    ├── AdminLayoutComposer.php          # EXISTENTE: inyecta $dash (notif, alerts, navigation, ciclo_actual)
    └── AcademiaComposer.php             # NUEVO: datos específicos academia (kpis nav, horarios base)
```

### 2.2 Capa de Aplicación (Services + Jobs + Events)

```
app/Services/
├── ZktecoService.php                    # EXISTENTE: Device-bound, SDK ZKTeco
├── EmployeeDeviceSyncService.php        # EXISTENTE: Credential package pattern
├── EmployeeCatalogMover.php             # EXISTENTE: Legacy migration
├── FirebirdReader.php                   # NUEVO: PDO Firebird wrapper (read-only)
├── SyncStrategies/                      # NUEVO: Strategy Pattern para ETL
│   ├── SyncStrategyInterface.php
│   ├── CycleDirectSync.php
│   ├── AlumnosByCycleSync.php
│   └── CatalogSmartSync.php
├── CicloActualService.php               # NUEVO: resolve_ciclo_principal → global
├── HorarioResolver.php                  # NUEVO: build_horario_grid, get_horario_base
├── KardexCalculator.php                 # NUEVO: calcularKardexAsignatura (unit tested)
├── PersonaContratosResolver.php         # NUEVO: get_persona_contratos unificado
└── Exportable.php                       # NUEVO: Trait CSV export unificado

app/Jobs/
├── SyncDeviceJob.php                    # EXISTENTE: ZKTeco sync
├── SyncEmployeeToDeviceJob.php          # EXISTENTE
├── FirebirdSyncJob.php                  # NUEVO: ETL Firebird (ShouldQueue, WithoutOverlapping)
└── Academia/                            # NUEVO (opcional)
    ├── KardexExportJob.php
    └── HorarioConflictCheckJob.php

app/Events/
├── SyncProgressUpdated.php              # EXISTENTE: ZKTeco sync progress
└── FirebirdSyncProgressUpdated.php      # NUEVO: ETL progress

app/Exceptions/
├── ZktecoConnectionException.php        # EXISTENTE
├── SyncCancelledException.php           # EXISTENTE
└── FirebirdSyncException.php            # NUEVO
```

### 2.3 Capa de Dominio (Models + Eloquent)

```
app/Models/
├── User.php                             # EXISTENTE: Laravel Auth
├── Employee.php                         # EXISTENTE + CAMPOS NUEVOS (type, numero_empleado, ...)
├── Device.php                           # EXISTENTE
├── Attendance.php                       # EXISTENTE + CAMPOS NUEVOS (attendance_type, source, hora_*)
├── Fingerprint.php                      # EXISTENTE
├── DeviceSync.php                       # EXISTENTE
├── DeviceSyncItem.php                   # EXISTENTE
├── Pivots/DeviceEmployee.php            # EXISTENTE
├── FirebirdSync.php                     # NUEVO: tracking ETL
├── FirebirdSyncItem.php                 # NUEVO: detalle por tabla
└── Academia/                            # NUEVO namespace
    ├── Ciclo.php                        # PK compuesta (inicial, final, periodo) + id surrogate
    ├── Nivel.php
    ├── Turno.php
    ├── Sede.php
    ├── Plan.php
    ├── Materia.php
    ├── MetodoEval.php
    ├── Contrato.php
    ├── Grupo.php                        # PK compuesta + id surrogate
    ├── Alumno.php
    ├── AlumnoGrupo.php                  # Pivot (alumno, grupo, ciclo)
    ├── AlumnoKardex.php
    ├── Profesor.php                     # O STI en Employee
    ├── HorarioDet.php                   # PK compuesta (8 cols) + id surrogate
    ├── Curso.php
    ├── CursoDet.php
    └── SesionBase.php                   # CFGSESIONES (nivel, turno, sesion, hora_inicio, fin, receso)
```

### 2.4 Capa de Datos (Migrations + Database)

```
database/migrations/
├── 2014_10_12_000000_create_users_table.php
├── ... (existentes 22 migraciones)
├── 2026_XX_XX_XXXXXX_add_academia_fields_to_employees.php    # NUEVO
├── 2026_XX_XX_XXXXXX_add_academia_fields_to_attendances.php  # NUEVO
├── 2026_XX_XX_XXXXXX_create_ciclos_table.php                 # NUEVO
├── 2026_XX_XX_XXXXXX_create_niveles_table.php                # NUEVO
├── 2026_XX_XX_XXXXXX_create_turnos_table.php                 # NUEVO
├── 2026_XX_XX_XXXXXX_create_sedes_table.php                  # NUEVO
├── 2026_XX_XX_XXXXXX_create_planes_table.php                 # NUEVO
├── 2026_XX_XX_XXXXXX_create_materias_table.php               # NUEVO
├── 2026_XX_XX_XXXXXX_create_metodos_eval_table.php           # NUEVO
├── 2026_XX_XX_XXXXXX_create_contratos_table.php              # NUEVO
├── 2026_XX_XX_XXXXXX_create_grupos_table.php                 # NUEVO
├── 2026_XX_XX_XXXXXX_create_alumnos_table.php                # NUEVO
├── 2026_XX_XX_XXXXXX_create_alumnos_grupos_table.php         # NUEVO (pivot)
├── 2026_XX_XX_XXXXXX_create_alumnos_kardex_table.php         # NUEVO
├── 2026_XX_XX_XXXXXX_create_profesores_table.php             # NUEVO (o STI)
├── 2026_XX_XX_XXXXXX_create_horarios_det_table.php           # NUEVO
├── 2026_XX_XX_XXXXXX_create_cursos_table.php                 # NUEVO
├── 2026_XX_XX_XXXXXX_create_cursos_det_table.php             # NUEVO
├── 2026_XX_XX_XXXXXX_create_sesiones_base_table.php          # NUEVO (CFGSESIONES)
├── 2026_XX_XX_XXXXXX_create_firebird_syncs_table.php         # NUEVO
└── 2026_XX_XX_XXXXXX_create_firebird_sync_items_table.php    # NUEVO

config/database.php
├── connections: mysql (default), sqlite, pgsql, sqlsrv, firebird (NUEVO)
└── firebird: dsn, user, pass, options (desde .env)
```

### 2.5 Capa de Frontend (Assets + Components)

```
resources/
├── views/
│   ├── layouts/
│   │   └── admin.blade.php              # EXISTENTE: Shell principal (sidebar, topbar, theme, toasts, cmd)
│   ├── dashboard.blade.php              # EXISTENTE: KPIs, trend, donut, pipeline, recent
│   ├── academia/                        # NUEVO
│   │   ├── dashboard.blade.php          # Dashboard academia (KPIs ciclos, grupos, inscripciones)
│   │   ├── ciclos/ (index, show)
│   │   ├── grupos/ (index, show, conflicts)
│   │   ├── alumnos/ (index, show, kardex)
│   │   ├── kardex/ (show, print)
│   │   ├── horarios/ (clase, curso, aula, base, persona)
│   │   ├── profesores/ (index, show, contratos)
│   │   ├── planes/ (index, show, materias, metodos)
│   │   ├── asistencia-clases/ (grid + drawer)
│   │   └── reportes/ (index, exports)
│   ├── components/                      # COMPONENTES REUTILIZABLES
│   │   ├── stat-card.blade.php          # EXISTENTE → extendido
│   │   ├── data-table.blade.php         # NUEVO: slots headers, rows, filters, pagination, export
│   │   ├── badge.blade.php              # NUEVO: variantes status, role, attendance, eval
│   │   ├── drawer.blade.php             # NUEVO: Alpine.js drawer (empleado, asistencia, persona)
│   │   ├── modal.blade.php              # NUEVO: Alpine.js modal (confirm, form)
│   │   ├── toast.blade.php              # NUEVO: stack toasts (ya existe en layout)
│   │   ├── pagination.blade.php         # NUEVO: wrapper Laravel paginate
│   │   ├── kpi-card.blade.php           # NUEVO: variante stat-card para academia
│   │   ├── horario-grid.blade.php       # NUEVO: grid 7 días × sesiones
│   │   └── asistencia-clase-grid.blade.php # NUEVO: grid captura estados
│   └── partials/                        # EXISTENTES (donut, sparkline, empty-state)
├── css/
│   └── app.css                          # EXISTENTE → migrar a Tailwind @layer components
├── js/
│   ├── app.js                           # EXISTENTE → migrar a Alpine.js components
│   ├── bootstrap.js
│   └── academia.js                      # NUEVO: componentes Alpine.js específicos academia
```

---

## 3. Patrones Arquitectónicos Aplicados

| Patrón | Dónde se usa | Justificación |
|--------|--------------|---------------|
| **Repository/Service Layer** | `ZktecoService`, `FirebirdReader`, `SyncStrategies`, `CicloActualService` | Separar lógica de negocio de controllers; testeable; inyectable |
| **Strategy Pattern** | `SyncStrategyInterface` + 3 implementaciones | ETL tiene 3 modos distintos (ciclo directo, alumnos por ciclo, catálogos smart) |
| **DTO / Data Package** | `EmployeeCredentialPackage`, `FirebirdSyncPayload` | Transferir datos estructurados entre capas sin acoplar a modelos |
| **Observer/Events** | `SyncProgressUpdated`, `FirebirdSyncProgressUpdated` | Progreso async en UI sin polling agresivo |
| **Queue + WithoutOverlapping** | `SyncDeviceJob`, `FirebirdSyncJob` | Evitar sincronizaciones concurrentes del mismo dispositivo/ciclo |
| **View Composer** | `AdminLayoutComposer`, `AcademiaComposer` | Inyectar datos globales (nav, ciclo, kpis) sin repetir en controllers |
| **Form Request** | (Pendiente — migrar validación inline) | Validación centralizada, autorización via Policies |
| **Policies/Gates** | `DevicePolicy`, `EmployeePolicy`, `AcademiaPolicy` | Autorización declarativa, no `if` sueltos |
| **Componentes Blade + Alpine.js** | `<x-drawer>`, `<x-modal>`, `<x-data-table>` | UI reactiva sin SPA; reutilizable; integra con Blade |
| **Cast / Mutator / Scope** | `Employee::scopeBiometric()`, `Attendance::stateLabel()` | Lógica de dominio en modelo, no en controller |

---

## 4. Flujos Críticos Unificados

### 4.1 Sincronización ZKTeco (Existente — Sin Cambios)
```
User → DeviceController::syncNow()
    → Dispatch SyncDeviceJob (device, operation=all)
    → Job: ZktecoService->syncUsers/Attendances/Fingerprints
    → Events: SyncProgressUpdated (progress bar UI)
    → DeviceSync model: status, stage, processed, totals, log
    → UI: Poll /progress endpoint → progress bar real-time
```

### 4.2 Sincronización Firebird → MySQL (Nuevo)
```
User → Command firebird:sync 2025-2025-3 --catalogos
    OR Scheduler → Dispatch FirebirdSyncJob (ciclo, operations)
    → Job: FirebirdReader->fetch(table, where)
    → Strategy: CycleDirectSync / AlumnosByCycleSync / CatalogSmartSync
    → FirebirdSync model: status, stage, processed, totals, log (JSON)
    → Events: FirebirdSyncProgressUpdated
    → UI: /operations/firebird-sync → progress + log viewer
```

### 4.3 Captura Asistencia Clases (Nuevo — desde Base)
```
User → Academia/AsistenciaClaseController::index(nivel, turno, dia, fecha)
    → HorarioResolver->get_clase_asistencia_grid() → Grid data
    → View: asistencia-clase-grid (Alpine.js: click → open drawer)
    → Drawer: select estado (Presente/Ausente/Retardo/Justificado) + obs
    → POST → AsistenciaClaseController::store()
    → AlumnoKardex/ClaseAsistencia model save
    → Toast success + grid update (Alpine.js fetch partial)
```

### 4.4 Ciclo Escolar Global (Nuevo — desde Base)
```
Middleware EnsureCicloActivo (global o group academia)
    → CicloActualService->resolve(request, session)
    → Guarda en session['ciclo_actual'] + URL param ciclo_principal
    → AdminLayoutComposer inyecta $ciclo_actual + $kpis_nav en todas las vistas
    → Topbar: selector ciclo (select) → cambio → reload con nuevo ciclo
    → KPIs nav badges: grupos, inscripciones, horarios, checadas (filtrados por ciclo)
```

---

## 5. Decisiones de Diseño Clave (Pendientes de Confirmación)

| Decisión | Opciones | Recomendación | Impacto |
|----------|----------|---------------|---------|
| **Employee vs Profesor** | A) Un solo modelo `Employee` con `type` enum + campos nullable<br>B) `Employee` + `Teacher` (STI o tabla separada)<br>C) `Persona` abstracta + `Employee`/`Teacher` concretos | **A** (más simple, pivot DeviceEmployee ya soporta multi-type, Attendance FK único) | Medio — requiere migración campos + scopes |
| **PK Compuestas Firebird** | A) Mantener PK compuesta en modelos + `id` surrogate autoincrement<br>B) Solo PK compuesta (Laravel 10 soporta composite PK)<br>C) Mapear a `id` surrogate único, PK compuesta como `unique` index | **C** (Eloquent relations funcionan mejor con `id`; PK compuesta como constraint) | Alto — afecta 8+ modelos academia |
| **Módulo Académico: ¿Mismo Laravel?** | A) Sí, mismo repo, namespace `App\Academia\`<br>B) Package separado (`academia/`) con ServiceProvider<br>C) Microservicio independiente (API) | **A** (menor overhead, comparte Auth, UI, Queue, Testing; dominio relacionado) | Bajo — decisión organizacional |
| **Firebird: ¿Read-only Models o PDO directo?** | A) Modelos Eloquent read-only (`$guarded=['*']`, connection=firebird)<br>B) PDO directo en `FirebirdReader` service (actual) | **B** (Firebird schema complejo, PK compuestas, ETL bulk; Eloquent añade overhead) | Medio |
| **Dark Mode Academia** | A) Extender tokens principales → cobertura total<br>B) CSS custom academia (dashboard.css) + migrate gradual | **A** (consistencia visual, un solo sistema tokens) | Medio |

---

## 6. Configuración Requerida (.env + config)

```env
# .env — NUEVAS VARIABLES
# Firebird
FIREBIRD_DSN=firebird:dbname=host:/path/to/DATOS.FDB;charset=UTF-8
FIREBIRD_USER=SYSDBA
FIREBIRD_PASS=masterkey

# Academia
CICLO_DEFAULT=2025-2025-3
ACADEMIA_SYNC_SCHEDULE="0 2 * * *"  # 2 AM daily

# Queue
QUEUE_CONNECTION=database
```

```php
// config/database.php — NUEVA CONEXIÓN
'firebird' => [
    'driver' => 'firebird',
    'dsn' => env('FIREBIRD_DSN'),
    'username' => env('FIREBIRD_USER'),
    'password' => env('FIREBIRD_PASS'),
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
],
```

```php
// config/navigation.php — NUEVO (desde $nav_menu Base)
return [
    'inicio' => ['label' => 'Inicio', 'icon' => 'speedometer2', 'route' => 'dashboard'],
    'personal' => [
        'label' => 'Personal', 'icon' => 'people',
        'groups' => [
            ['label' => 'Directorio', 'items' => [
                ['route' => 'employees.index', 'label' => 'Empleados'],
                ['route' => 'academia.profesores.index', 'label' => 'Docentes'],
            ]],
            ['label' => 'Asistencia', 'items' => [
                ['route' => 'attendances.index', 'label' => 'Checadas Biométricas'],
                ['route' => 'academia.asistencia-clases.index', 'label' => 'Asistencia Clases'],
            ]],
        ]
    ],
    'academia' => [
        'label' => 'Academia', 'icon' => 'mortarboard', 'badge' => 'ciclo_actual',
        'groups' => [
            ['label' => 'Ciclo Actual', 'items' => [
                ['route' => 'academia.ciclos.show', 'label' => 'Resumen Ciclo'],
                ['route' => 'academia.grupos.index', 'label' => 'Grupos'],
                ['route' => 'academia.inscripciones.index', 'label' => 'Inscripciones'],
            ]],
            ['label' => 'Alumnos', 'items' => [
                ['route' => 'academia.alumnos.index', 'label' => 'Buscar Alumno'],
                ['route' => 'academia.kardex.index', 'label' => 'Kardex'],
            ]],
            ['label' => 'Horarios', 'items' => [
                ['route' => 'academia.horarios.clase', 'label' => 'Clases'],
                ['route' => 'academia.horarios.curso', 'label' => 'Cursos'],
                ['route' => 'academia.horarios.aula', 'label' => 'Conflictos Aula'],
            ]],
        ]
    ],
    'config' => [...],
    'sistema' => [...], // solo admin
];
```

---

## 7. Testing Strategy (Target)

| Capa | Herramienta | Cobertura Objetivo |
|------|-------------|-------------------|
| **Unit** | Pest/PHPUnit | Services: `KardexCalculator`, `HorarioResolver`, `PersonaContratosResolver`, `SyncStrategies`, `CicloActualService` |
| **Feature** | Pest/PHPUnit + MySQL Testing DB | Controllers: CRUD academia, Dashboard KPIs, Sync flows, Auth, Ciclo selector |
| **Browser** | (Opcional) Dusk / Playwright | Flujos críticos: login → dashboard → sync ZKTeco → sync Firebird → asistencia clases |
| **Static Analysis** | Larastan/PHPStan Level 5 | Todo código nuevo (Services, Models, Controllers) |
| **Code Style** | Laravel Pint | PSR-12 + Laravel conventions |

**Regla de Paridad Drivers**: Tests **siempre** corren en MySQL (`phpunit.xml` → `DB_DATABASE=rh_reloj_testing`). **Nunca SQLite** — oculta bugs de `wherePivot` y FK.

---

## 8. Deployment Target

```yaml
# .github/workflows/deploy.yml (o deploy.sh extendido)
steps:
  - composer install --no-dev --optimize-autoloader
  - npm ci && npm run build          # Vite: app.css + app.js + academia.js
  - php artisan migrate --force      # Incluye nuevas migraciones academia + firebird_sync
  - php artisan config:cache
  - php artisan route:cache
  - php artisan view:cache
  - php artisan queue:restart        # Reinicia workers para nuevos jobs
  - systemctl reload php-fpm nginx   # O según infraestructura
```

**Requisitos Infraestructura (INFRASTRUCTURE_DEPENDENCY):**
- Firebird DB accesible desde servidor Laravel (red/puerto 3050)
- `pdo_firebird` extension instalada y habilitada
- Queue worker corriendo: `php artisan queue:work --timeout=600 --tries=3`
- Scheduler cron: `* * * * * php artisan schedule:run`
- MySQL 8.0+ (FK, JSON, CTE support)

---

## 9. Lo que NO está en esta Arquitectura (Fuera de Alcance Fase 0)

- ❌ Módulo de **Nómina / Liquidación de sueldos** (no visto en Base)
- ❌ **App móvil** (no hay evidencia en ninguno)
- ❌ **API pública REST** para terceros (solo API interna AJAX)
- ❌ **WebSockets / Broadcasting** en tiempo real (solo polling progress)
- ❌ **Multi-tenancy** (una sola institución UTE)
- ❌ **Microservicios** (monolito Laravel único)
- ❌ **Event Sourcing / CQRS** (patrón no necesario)

---

## 10. Métricas Objetivo Post-Integración

| Métrica | Actual (Principal) | Objetivo (Unificado) |
|---------|-------------------|---------------------|
| Modelos Eloquent | 7 + 1 pivot | ~25 + 2 pivots |
| Migraciones | 22 | ~45 |
| Controllers | 6 | ~20 |
| Rutas web | 36 | ~80 |
| Rutas API | 4 | ~15 |
| Jobs | 2 | 4 |
| Events | 1 | 2 |
| Services | 3 | ~10 |
| Vistas Blade | 23 | ~50 |
| Componentes Blade | 1 | ~10 |
| Tests | 5 | ~40 |
| Líneas CSS (custom) | ~63KB | ~0 (Tailwind utilities) |
| Líneas JS (custom) | ~26KB | ~5KB (Alpine.js components) |
| Dependencias Composer | 8 prod | 9 prod (+ pdo_firebird) |