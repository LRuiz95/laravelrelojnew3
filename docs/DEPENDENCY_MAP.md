# Mapa de Dependencias — ProyectoBase → laravelrelojnew

> **Generado en Fase 0** — Análisis de dependencias para planificar migración segura

---

## 1. Grafo de Dependencias — ProyectoBase (Actual)

```
rh-dashboard2.php (ENTRY POINT)
│
├── core/Database.php (Singleton PDO: Firebird + MySQL)
│   └── config/database.php (DSN, credenciales .env)
│
├── core/Auth.php (Class estática: login, session, permissions, CSRF)
│   └── config/auth.php (users array, roles/permissions, session config)
│
├── core/Helpers.php (Funciones globales: q(), qm(), fb(), csv_download(), resolve_*)
│   ├── db() → Database::mysql()
│   ├── fb() → Database::firebird()
│   └── resolve_* → qm() queries (cache estático)
│
├── sections/*.php (28 archivos — "Page Controllers" con HTML embebido)
│   ├── empleados.php → qm(empleados), csv_download
│   ├── docentes.php → qm(profesores), csv_download
│   ├── asistencia.php → qm(empleados_asistencia + JOIN empleados/profesores), csv_download
│   ├── asistencia-clases.php → get_clase_asistencia_grid/stats (Helpers.php), POST save
│   ├── sincronizar.php → sync_* functions (ETL Firebird→MySQL), POST actions
│   ├── horarios-*.php → qm(horarios_det, grupos, cfgsesiones, etc.)
│   ├── alumnos*.php → qm(alumnos, alumnos_grupos, alumnos_kardex, etc.)
│   ├── kardex.php → calcularKardexAsignatura() (Helpers.php)
│   ├── reportes.php → qm(varios), csv_download
│   └── ... (config: niveles, turnos, sedes, contratos, horarios-base, planes, materias, metodos-eval, ciclos, grupos, inscripciones, horario-persona, horarios-aula, horarios-curso)
│
├── assets/css/dashboard.css (956 líneas — Sistema de diseño completo dark-first)
│
├── assets/js/dashboard.js (98 líneas — Sidebar, drawers, ciclo selector, scroll hints, topbar shadow)
│
├── assets/js/asistencia-clases.js (Drawer captura asistencia)
│
└── login.php / logout.php (Auth entry points)
```

### Dependencias Críticas en ProyectoBase

| Componente | Depende de | Tipo | Riesgo si se mueve |
|------------|------------|------|-------------------|
| `sections/*` | `core/Database`, `core/Auth`, `core/Helpers` | **Hard** (require_once) | Alto — todo usa helpers globales |
| `helpers resolve_*` | `qm()` → `db()` → `Database::mysql()` | **Hard** | Alto — 15+ funciones con cache estático |
| `sincronizar.php` | `fb()`, `db()`, `sync_* helpers`, `Auth::csrf` | **Hard** | **Crítico** — ETL completo acoplado a PDO raw |
| `asistencia-clases.php` | `get_clase_asistencia_grid/stats` (Helpers) | **Hard** | Alto — SQL complejos con JOINs múltiples |
| `dashboard.css` | CSS variables (:root) + clases semánticas | **Hard** | Medio — referenciado en todos los sections |
| `$nav_menu` + `$breadcrumb_map` | `$ciclo_principal`, `$kpi_*`, `Auth::hasPermission` | **Hard** | Medio — renderizado en rh-dashboard2.php |

### Dependencias Circulares Detectadas

```
rh-dashboard2.php
    → require core/Helpers.php
        → function qm() uses db() → Database::mysql()
    → require sections/sincronizar.php (si sec=sincronizar)
        → uses fb() → Database::firebird()
        → uses sync_smart_sync() → qm() → db()
    → sections/asistencia.php uses qm() → JOIN empleados/profesores
    → sections/empleados.php uses qm(empleados)
    → helpers resolve_sede() uses qm(cfgsedes)
    → helpers resolve_profesor_nombre() uses qm(profesores)
```
**No hay ciclos reales** — es un grafo acíclico dirigido desde entry point hacia helpers/DB. Pero **todo está acoplado a funciones globales**, no a interfaces.

---

## 2. Grafo de Dependencias — laravelrelojnew (Principal)

```
routes/web.php
│
├── Controllers (6)
│   ├── DashboardController
│   │   ├── Attendance, Device, Employee, Fingerprint models
│   │   └── kpisJson() → JSON API
│   ├── DeviceController
│   │   ├── Device, Employee, Attendance, Fingerprint, DeviceSync
│   │   ├── ZktecoService (sync users/attendances/fingerprints)
│   │   └── SyncDeviceJob (queue)
│   ├── EmployeeController
│   │   ├── Employee, Device, Fingerprint, DeviceEmployee pivot
│   │   ├── EmployeeDeviceSyncService
│   │   └── ZktecoService (upload fingerprints)
│   ├── AttendanceController
│   │   └── Attendance, Employee, Device
│   ├── OperationsController
│   │   └── DeviceSync (queue management)
│   └── AuthController
│       └── User model
│
├── Models (7 + 1 pivot)
│   ├── Employee ←→ Device (BelongsToMany, pivot DeviceEmployee)
│   ├── Attendance → Device, Employee
│   ├── Fingerprint → Employee, Device
│   ├── Device → Employee, Attendance, Fingerprint, DeviceSync
│   ├── DeviceSync → Device, Employee, DeviceSyncItem
│   ├── DeviceSyncItem → DeviceSync, Fingerprint
│   └── User (Auth)
│
├── Services (3)
│   ├── ZktecoService (SDK coding-libs/zkteco-php)
│   │   ├── Device model (config)
│   │   ├── Employee, Fingerprint, DeviceEmployee pivot
│   │   └── Exceptions: ZktecoConnectionException, SyncCancelledException
│   ├── EmployeeDeviceSyncService
│   │   ├── EmployeeCredentialPackage (Data)
│   │   ├── ZktecoService
│   │   └── Device, Employee, Fingerprint
│   └── EmployeeCatalogMover (legacy migration)
│
├── Jobs (2)
│   ├── SyncDeviceJob (ShouldQueue, WithoutOverlapping)
│   │   ├── ZktecoService
│   │   ├── DeviceSync model (progress tracking)
│   │   └── Event: SyncProgressUpdated
│   └── SyncEmployeeToDeviceJob
│
├── Events/Listeners (1)
│   └── SyncProgressUpdated → Broadcast/Queue (configurable)
│
├── Middleware (10)
│   └── EnsureAdmin (checks User::isAdmin())
│
├── View Composers (1)
│   └── AdminLayoutComposer → $dash (notifications, alerts) → all views
│
├── Views (23 Blade + 1 component + 5 partials)
│   ├── layouts/admin.blade.php (shell: sidebar, topbar, theme, toasts, cmd palette)
│   ├── dashboard.blade.php (KPIs, trend SVG, donut, pipeline, recent table)
│   ├── devices/* (index, show, create, edit)
│   ├── employees/* (index, create, edit)
│   ├── attendances/* (index, print)
│   ├── fingerprints/* (index)
│   ├── operations/* (queue, notifications)
│   └── partials/* (donut, sparkline, empty-state)
│
├── Frontend (Vite)
│   ├── resources/css/app.css (63KB — Bootstrap override + CSS vars + dark mode)
│   ├── resources/js/app.js (26KB — Alpine.js? theme, sidebar, toasts, search)
│   └── resources/js/bootstrap.js
│
└── Config (Laravel standard)
    ├── database.php (MySQL, SQLite, PgSQL, SQLSRV)
    ├── auth.php (web guard, User provider)
    ├── queue.php (sync, database, redis, beanstalkd, SQS)
    └── ...
```

### Puntos de Extensión Identificados (Principal)

| Punto | Uso Actual | Capacidad para Base |
|-------|------------|---------------------|
| `AdminLayoutComposer` | Inyecta `$dash` (notifications, alerts) | **Ideal** para navegación dinámica + ciclo selector global |
| `DashboardController::kpis()` | 5 KPIs hardcoded | **Extensible** vía KpiProvider interface o config |
| `ZktecoService` | Device-bound, SDK-specific | **No reutilizable** para Firebird ETL |
| `SyncDeviceJob` | Queue + WithoutOverlapping + progress events | **Patrón reutilizable** para FirebirdSyncJob |
| `EmployeeDeviceSyncService` | Credential package pattern | **Patrón reutilizable** para sync académico |
| `routes/api.php` (vacío) | — | **Destino natural** para AJAX endpoints (grupos, alumnos, planes) |
| `app/Console/Kernel.php` | Schedule vacío | **Destino** para comandos ETL programados |

---

## 3. Mapa de Migración — Dependencias a Resolver

### 3.1 Para incorporar ETL Firebird→MySQL (NEW)

```
Nuevo: FirebirdSyncJob (ShouldQueue)
    ├── FirebirdReader (Service) — Lee Firebird via PDO
    │   └── config/database.php → connection 'firebird'
    ├── SyncStrategyInterface (Contract)
    │   ├── CycleDirectSync (GRUPOS, HORARIOS_DET, CURSOS, CICLOS)
    │   ├── AlumnosByCycleSync (ALUMNOS_NIVELES → ALUMNOS/GRUPOS/KARDEX)
    │   └── CatalogSmartSync (CFGSEDES, PROFESORES, EMPLEADOS, etc.)
    ├── FirebirdSync model (tracking: status, stage, processed, totals, log)
    ├── FirebirdSyncItem model (detalle por tabla)
    └── Event: FirebirdSyncProgressUpdated
```

**Dependencias nuevas requeridas:**
- `ext-pdo_firebird` en PHP / composer `php:^8.1` ya lo soporta
- Conexión `firebird` en `config/database.php` (DSN, user, pass, charset)
- Modelos read-only para tablas Firebird (opcional, o PDO directo en Service)
- Comando `php artisan firebird:sync {ciclo?} {--catalogos} {--delete-orphans}`

### 3.2 Para incorporar Módulo Académico (NEW)

```
Nuevo Módulo: Academia
    ├── Models (Eloquent)
    │   ├── Ciclo (INICIAL, FINAL, PERIODO, DESCRIPCION, FECHA_INICIAL, FECHA_FINAL)
    │   ├── Nivel, Turno, Sede, Plan, Materia, MetodoEval, Contrato
    │   ├── Grupo (CODIGO_GRUPO, GRADO, TURNO, NIVEL, INICIAL, FINAL, PERIODO, ID_CAMPUS)
    │   ├── Alumno (NUMEROALUMNO, PATERNO, MATERNO, NOMBRE, ...)
    │   ├── AlumnoGrupo (pivot: NUMEROALUMNO, CODIGO_GRUPO, ciclo)
    │   ├── AlumnoKardex (calificaciones por evaluación)
    │   ├── Profesor (CLAVEPROFESOR, NOMBREPROFESOR, DEPARTAMENTO, ORIGEN_HORARIO, ...)
    │   ├── HorarioDet (clases: DIA, SESION, CLAVEASIGNATURA, CLAVEPROFESOR, GRUPO, AULA, ORIGEN_HORARIO)
    │   ├── Curso / CursoDet
    │   └── HorarioBase (CFGSESIONES: NIVEL, TURNO, SESION, HORA_INICIO, HORA_FIN, RECESO)
    │
    ├── Controllers (Resource + Custom)
    │   ├── CicloController, GrupoController, AlumnoController, KardexController
    │   ├── HorarioClaseController, HorarioCursoController, HorarioAulaController
    │   ├── ProfesorController, PlanController, MateriaController
    │   └── AsistenciaClaseController (captura grid + drawer)
    │
    ├── Services
    │   ├── CicloActualService (resolve_ciclo_principal → global)
    │   ├── HorarioResolver (build_horario_grid, get_horario_base)
    │   ├── KardexCalculator (calcularKardexAsignatura)
    │   └── PersonaContratosResolver (get_persona_contratos unificado)
    │
    ├── Jobs
    │   └── (opcional) KardexExportJob, HorarioConflictCheckJob
    │
    ├── Views (Blade)
    │   ├── academia/* (dashboard, grupos, alumnos, kardex, horarios, planes)
    │   ├── components/asistencia-clase-grid.blade.php
    │   └── components/horario-grid.blade.php
    │
    └── Routes
        ├── web: academia.* (protegidas por auth)
        └── api: academia.grupos-por-ciclo, academia.alumnos-por-grupo, academia.ciclos-disponibles, academia.planes-por-nivel
```

### 3.3 Para Unificar Employee/Attendance (MERGE)

```
Cambios en modelo Employee (Principal):
    ├── Agregar: type (enum: biometric, admin, teacher)
    ├── Agregar: numero_empleado (nullable, unique) — mapea NUMEMPLEADO
    ├── Agregar: clave_profesor (nullable, unique) — mapea CLAVEPROFESOR
    ├── Agregar: departamento, cargo, contrato, status_actual, fecha_ingreso, id_campus, nivel
    ├── Relación: profesor() → hasOne(Profesor) [si se crea modelo Profesor]
    └── Scopes: scopeBiometric(), scopeAdmin(), scopeTeacher()

Cambios en modelo Attendance (Principal):
    ├── Agregar: attendance_type (enum: biometric, manual)
    ├── Agregar: source (enum: zkteco, manual_admin, manual_teacher, class)
    ├── Agregar: hora_entrada, hora_salida, hora_salida_comer, hora_regreso_comer (nullable)
    ├── Mantener: type/state ZKTeco (para biométricos)
    └── Índice único compuesto: (employee_id, recorded_at, device_id, attendance_type)

Nuevo modelo Profesor (si se decide separar):
    ├── Extiende Employee o tabla propia
    ├── Campos: clave_profesor (PK), nombre, departamento, contrato, origen_horario (HD/CA), ...
    └── Relaciones: horarios (HorarioDet), grupos (via horarios)
```

---

## 4. Componentes No Trasladables Directamente (Bloqueadores)

| Componente Base | Bloqueador | Solución |
|-----------------|------------|----------|
| `Database::firebird()` singleton | No existe conexión Firebird en Laravel | Agregar connection `firebird` en config/database.php + Service FirebirdReader |
| `Helpers.php` funciones globales (q, qm, fb, resolve_*) | Acoplamiento global, cache estático, sin DI | Migrar a: Eloquent Scopes, Services inyectables, View Composers |
| `sync_smart_sync()` / `sync_cycle_table()` | PDO raw + transacciones manuales + logging en array | Reescribir como Jobs + FirebirdSync model + Strategy pattern |
| `get_clase_asistencia_grid/stats()` | SQL complejos con 8 JOINs + cache estático | Query Builder / Eloquent con eager loading + cache Redis |
| `calcularKardexAsignatura()` | Función pura PHP, lógica de negocio académica | Service `KardexCalculator` + tests unitarios |
| `get_persona_contratos()` | Une 3 fuentes (admin, PTC, PA) con lógica compleja | Service `PersonaContratosResolver` inyectable |
| `$nav_menu` + `$breadcrumb_map` | Array PHP renderizado inline en layout | Config `navigation.php` + `AdminLayoutComposer` (ya existe) |
| `dashboard.css` completo | 956 líneas CSS custom, dark-only | Extraer tokens → `tailwind.config.js` + componentes Blade |
| `asistencia-clases.js` (drawer) | Vanilla JS acoplado a HTML específico | Componente Alpine.js/Vue + Blade component |

---

## 5. Orden Topológico Sugerido de Migración

```
1. INFRAESTRUCTURA BASE (sin código de negocio)
   ├─ 1.1 Agregar connection 'firebird' en config/database.php
   ├─ 1.2 Crear modelo FirebirdSync + migración
   ├─ 1.3 Crear FirebirdReader service (PDO wrapper)
   └─ 1.4 Configurar queue database + worker para jobs largos

2. ETL FIREBIRD → MYSQL (Core nuevo)
   ├─ 2.1 FirebirdSyncJob (base: WithoutOverlapping, progress events)
   ├─ 2.2 SyncStrategyInterface + 3 implementaciones
   ├─ 2.3 Comando artisan firebird:sync
   └─ 2.4 Tests de integración (MySQL testing DB + Firebird mock)

3. DOMINIO ACADÉMICO — Modelos + Migraciones (NEW)
   ├─ 3.1 Ciclo, Nivel, Turno, Sede, Plan, Materia, MetodoEval, Contrato
   ├─ 3.2 Grupo, Alumno, AlumnoGrupo, AlumnoKardex
   ├─ 3.3 Profesor, HorarioDet, Curso, CursoDet, HorarioBase (CFGSESIONES)
   └─ 3.4 Factories + Seeders para testing

4. UNIFICACIÓN EMPLEADO/ASISTENCIA (MERGE)
   ├─ 4.1 Migración: añadir campos a employees + attendances
   ├─ 4.2 Ajustar Employee::devices() pivot (ya soporta multi-device)
   ├─ 4.3 Unificar Attendance: attendance_type, source, campos comida
   └─ 4.4 Migrar datos existentes (seeder/manual)

5. CONTROLADORES + VISTAS ACADÉMICAS (NEW)
   ├─ 5.1 Controllers Resource + Custom (Ciclo, Grupo, Alumno, Kardex, Horarios, Profesor)
   ├─ 5.2 Services: CicloActual, HorarioResolver, KardexCalculator, PersonaContratos
   ├─ 5.3 Views Blade (reutilizar layout principal + componentes)
   ├─ 5.4 Routes web + api (AJAX endpoints)
   └─ 5.5 Components: asistencia-clase-grid, horario-grid, persona-contratos

6. FRONTEND — SISTEMA DISEÑO UNIFICADO (MERGE)
   ├─ 6.1 Tokens CSS → tailwind.config.js (colors, spacing, radius, shadows)
   ├─ 6.2 Migrar dashboard.css clases → Tailwind utilities + @layer components
   ├─ 6.3 Componentes Blade: KPI card, Table, Badge, Drawer, Modal, Toast
   ├─ 6.4 Alpine.js para interactividad (sidebar, drawers, ciclo selector, tabs)
   └─ 6.5 Dark mode: mantener principal (3 estados) + verificar cobertura academia

7. NAVEGACIÓN + CICLO GLOBAL (ADAPT)
   ├─ 7.1 Config navigation.php (estructura $nav_menu)
   ├─ 7.2 AdminLayoutComposer → inyecta navigation + ciclo_actual + kpis nav
   ├─ 7.3 Middleware/Helper ciclo_actual() global
   └─ 7.4 Selector ciclo en topbar (merge con theme-toggle)

8. TESTING + DOCS
   ├─ 8.1 Feature tests academia (render, filters, CRUD, kardex, asistencia clase)
   ├─ 8.2 Unit tests: KardexCalculator, HorarioResolver, FirebirdSyncStrategies
   └─ 8.3 ADRs para decisiones: Employee unification, Firebird ETL, Academia module
```

---

## 6. Dependencias Externas de Infraestructura (INFRASTRUCTURE_DEPENDENCY)

| Dependencia | Requerida para | Estado | Acción |
|-------------|----------------|--------|--------|
| **Firebird DB accesible** | ETL sincronizar, modelos read-only, testing | ❓ No verificado | Verificar host/port/credenciales en .env; documentar si no disponible |
| **PDO Firebird extension** | PHP conectar a Firebird | ✅ PHP 8.1 lo incluye | Confirmar `extension=pdo_firebird` en php.ini |
| **Queue worker corriendo** | SyncDeviceJob, FirebirdSyncJob, exports | ❓ No verificado | Documentar: `php artisan queue:work --timeout=600` |
| **Scheduler cron** | Jobs programados (sync automático, limpieza) | ❓ No verificado | Documentar: `* * * * * php artisan schedule:run` |
| **Redis (opcional)** | Cache resolve_*, session, queue redis | ❌ No configurado | Opcional; usar database queue + cache array/file por ahora |
| **ZKTeco devices en red** | Funcionalidad principal (ya funciona) | ✅ Asumido | No bloquea migración Base |

---

## 7. Resumen de Complejidad de Dependencias

| Capa | Complejidad | Bloqueadores | Esfuerzo Relativo |
|------|-------------|--------------|-------------------|
| Infraestructura (DB, Queue, Firebird) | Media | Firebird access | 1x |
| ETL Firebird (Jobs, Strategies, Models) | **Muy Alta** | Firebird schema knowledge | 2x |
| Dominio Académico (15+ modelos, migraciones) | **Muy Alta** | Schema Firebird → MySQL mapping | 3x |
| Unificación Employee/Attendance | Alta | Decisión semántica (merge vs separate) | 1.5x |
| Controllers/Views Academia | Alta | Cantidad de CRUDs + grids complejos | 2x |
| Frontend Unificación (Tailwind + Components) | Media | CSS migration effort | 1x |
| Navegación/Ciclo Global | Baja | AdminLayoutComposer ya existe | 0.5x |

**Total estimado: ~11x** (donde 1x = esfuerzo actual del proyecto principal)