# Conflictos de Integración — laravelrelojnew ↔ ProyectoBase

> **Generado en Fase 0** — Cada conflicto clasificado: **CRÍTICO / ALTO / MEDIO / BAJO**

---

## 1. Conflictos de Nombres (Naming)

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Modelo `Employee`** | `App\Models\Employee` (catálogo central, user_id=PIN) | Tabla `EMPLEADOS` (NUMEMPLEADO) + `PROFESORES` (CLAVEPROFESOR) | **CRÍTICO** | Renombrar Base: `AdminEmployee`, `Teacher` → decidir si unificar en `Employee` con `type` enum o mantener separados con polimorfismo |
| **Modelo `Attendance`** | `App\Models\Attendance` (biométrico, device_id, type/state ZKTeco) | Tabla `EMPLEADOS_ASISTENCIA` (checadas manuales, entrada/salida/comida) | **ALTO** | Unificar esquema: `attendance_type` (biometric/manual), `source` (zkteco/manual_admin/manual_teacher/class), campos comida opcionales |
| **Tabla `devices`** | `devices` (checadores ZKTeco) | No existe | BAJO | Sin conflicto — exclusivo principal |
| **Tabla `users`** | `users` (Laravel Auth, web guard) | `config/auth.php` array `users` (admin/operador) | **MEDIO** | Migrar users Base a tabla `users` (seeder); mantener roles en DB o Policies |
| **Campo `status`** | `devices.status` (online/offline/unknown) | `EMPLEADOS.STATUSACTUAL` (A/B), `PROFESORES.STATUSACTUAL` | BAJO | Contextos distintos — prefijos en migraciones: `device_status`, `employee_status` |
| **Campo `role`** | `device_employee.role` (0/13/14 ZKTeco) | `config/auth.php` roles (admin/operator/viewer) | **MEDIO** | Contextos distintos — `device_role` vs `system_role` |
| **Clave primaria empleado** | `employees.user_id` (string, PIN/badge global) | `EMPLEADOS.NUMEMPLEADO`, `PROFESORES.CLAVEPROFESOR` | **CRÍTICO** | Decidir: ¿`user_id` = NUMEMPLEADO? ¿O `user_id` = CLAVEPROFESOR? ¿O clave compuesta? Requiere análisis de datos reales |
| **`sync`** | `SyncDeviceJob`, `DeviceSync` (ZKTeco) | `sincronizar.php` (Firebird→MySQL ETL) | **ALTO** | Renombrar Base: `FirebirdSync`, `FirebirdSyncJob` — dominios totalmente distintos |

---

## 2. Conflictos de Namespaces / Clases

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Helpers globales** | No usa helpers globales (Eloquent, Services, DI) | `core/Helpers.php` — 20+ funciones globales (`q`, `qm`, `fb`, `db`, `resolve_*`, `csv_download`) | **CRÍTICO** | **Eliminar helpers globales**. Migrar a: Services inyectables, Eloquent Scopes, View Composers, Commands |
| **Auth estática** | `Auth` facade / `AuthController` / Policies / Middleware | `core/Auth.php` class estática (login, session, permissions, CSRF) | **CRÍTICO** | **Eliminar Auth.php**. Usar Laravel Auth completo. Migrar matriz permisos a DB/Policies |
| **Database singleton** | `DB` facade / Eloquent / `config/database.php` | `core/Database.php` singleton (Firebird + MySQL PDO) | **ALTO** | Mantener Laravel DB. Agregar connection `firebird` en config. Crear `FirebirdReader` service |
| **Modelos Eloquent** | 7 modelos + 1 pivot + factories | Ninguno (queries PDO raw) | BAJO | Sin conflicto — Base no tiene modelos |
| **Exceptions** | `ZktecoConnectionException`, `SyncCancelledException` | Try/catch genérico + session flash | BAJO | Mantener exceptions Laravel; adaptar ETL a usar exceptions tipadas |

---

## 3. Conflictos de Tablas / Esquema (Database)

| Conflicto | Principal (MySQL) | Base (Firebird/MySQL) | Severidad | Resolución |
|-----------|-------------------|----------------------|-----------|------------|
| **Empleados** | `employees` (id, user_id, name) | `EMPLEADOS` (60+ cols: NUMEMPLEADO, NOMBREEMPLEADO, DEPARTAMENTO, CARGO, CONTRATO, STATUSACTUAL, FECHA_INGRESO, ID_CAMPUS, NIVEL, TARJETA_ID...) | **CRÍTICO** | **Opción A**: Extender `employees` con campos Base (type, numero_empleado, clave_profesor, departamento, cargo, contrato, status_actual, fecha_ingreso, id_campus, nivel, tarjeta_id). **Opción B**: Tabla separada `admin_employees` + polimorfismo. **Opción C**: `Employee` + `Teacher` STI. Requiere decisión arquitectónica |
| **Profesores** | No existe | `PROFESORES` (CLAVEPROFESOR, NOMBREPROFESOR, DEPARTAMENTO, CONTRATO, STATUSACTUAL, ORIGEN_HORARIO, FECHA_INGRESO, ID_CAMPUS) | **ALTO** | Si Opción A: campos en `employees` con `type=teacher`. Si Opción B/C: modelo `Teacher` propio |
| **Asistencias** | `attendances` (device_id, employee_id, user_id, state, type, recorded_at) | `EMPLEADOS_ASISTENCIA` (NUMEMPLEADO, CLAVEPROFESOR, FECHA, EVENTO, HORA_ENTRADA, HORA_SALIDA, HORA_SALIDAACOMER, HORA_REGRESODECOMER, FECHA_EVENTO) | **ALTO** | Unificar: añadir `attendance_type`, `source`, `hora_entrada`, `hora_salida`, `hora_salida_comer`, `hora_regreso_comer` a `attendances`. Mantener `type`/`state` ZKTeco para biométricos. Índice único: `(employee_id, recorded_at, device_id, attendance_type)` |
| **Huellas** | `fingerprints` (employee_id, device_id, finger, template, template_hash) | No existe | BAJO | Sin conflicto |
| **Dispositivos** | `devices` (ip, port, password, serial_number, device_name, status) | No existe | BAJO | Sin conflicto |
| **Sincronización** | `device_syncs`, `device_sync_items` (ZKTeco) | No existe (log en session) | BAJO | Sin conflicto — crear `firebird_syncs`, `firebird_sync_items` nuevos |
| **Ciclo escolar** | No existe | `CICLOS` (INICIAL, FINAL, PERIODO, DESCRIPCION, FECHA_INICIAL, FECHA_FINAL) | **ALTO** | Nuevo modelo `Ciclo` + migración. Clave compuesta (INICIAL, FINAL, PERIODO) |
| **Grupos** | No existe | `GRUPOS` (CODIGO_GRUPO, GRADO, TURNO, NIVEL, INICIAL, FINAL, PERIODO, INSCRITOS, ID_CAMPUS...) | **ALTO** | Nuevo modelo `Grupo` + clave compuesta |
| **Alumnos** | No existe | `ALUMNOS`, `ALUMNOS_NIVELES`, `ALUMNOS_GRUPOS`, `ALUMNOS_KARDEX` | **ALTO** | Nuevos modelos `Alumno`, `AlumnoGrupo`, `AlumnoKardex` |
| **Horarios** | No existe | `HORARIOS_DET`, `CURSOS`, `CURSOS_DET`, `CFGSESIONES`, `EMPLEADOS_HORARIOS`, `EMPLEADOS_CFGHORARIOS` | **ALTO** | Nuevos modelos: `HorarioDet`, `Curso`, `SesionBase` (CFGSESIONES), `HorarioLaboral` |
| **Catálogos** | No existe | `CFGSEDES`, `CFGPLANES_DET`, `CFGTURNOS`, `CFGNIVELES`, `CFGPLANES_MST`, `CFGPLANES_EVAL`, `CFGPLANES_ETAPAS`, `CFGSTATUS`, `CFGAULAS` | **MEDIO** | Modelos de referencia (lookup tables): `Sede`, `Plan`, `Turno`, `Nivel`, `Aula`, `MetodoEval`, `Etapa`, `Status` |
| **Contratos** | No existe | `EMPLEADOS_CONTRATOS_CAT`, `EMPLEADOS_CFGHORARIOS`, `EMPLEADOS_CFGHORARIOS_DET` | **MEDIO** | Modelos: `ContratoCatalogo`, `HorarioLaboralConfig` |

### Conflicto Crítico: Claves Primarias Compuestas vs Surrogates

| Tabla Base | PK Firebird/MySQL | Laravel Convención | Impacto |
|------------|-------------------|-------------------|---------|
| `CICLOS` | (INICIAL, FINAL, PERIODO) | `id` autoincrement | Requiere `$primaryKey = ['inicial','final','periodo']` + `$incrementing = false` |
| `GRUPOS` | (CODIGO_GRUPO, INICIAL, FINAL, PERIODO) | `id` autoincrement | Idem |
| `ALUMNOS_NIVELES` | (NUMEROALUMNO, INICIAL, FINAL, PERIODO) | `id` autoincrement | Idem |
| `HORARIOS_DET` | (INICIAL, FINAL, PERIODO, CODIGO_GRUPO, CLAVEPROFESOR, CLAVEASIGNATURA, DIA, SESION) | `id` autoincrement | Idem — PK de 8 columnas |

**Decisión**: Mantener PK compuestas en modelos read-only (Firebird sync) + agregar `id` surrogate en MySQL para Eloquent relations. Usar `unique` constraint en PK compuesta.

---

## 4. Conflictos de Rutas / Endpoints

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Entry point único** | `routes/web.php` (36 rutas RESTful) | `rh-dashboard2.php?sec=...` (28 secciones) | **ALTO** | Migrar todas las secciones a rutas Laravel: `Route::prefix('academia')->group(...)` + controllers |
| **API AJAX** | `kpisJson`, `queueData`, `syncStatus`, `progress` | `rh-dashboard2.php?api=...` (grupos, alumnos, ciclos, planes) | **MEDIO** | Unificar en `routes/api.php` con prefix `api/academia/` |
| **Auth routes** | `/login`, `/logout` (AuthController) | `login.php`, `logout.php` | BAJO | Mantener Laravel routes |
| **Export CSV** | `attendances.export` (AttendanceController) | `csv_download()` en cada section | BAJO | Trait `Exportable` en controllers |
| **Dashboard** | `/` (DashboardController) | `rh-dashboard2.php?sec=reportes` | BAJO | Mantener `/` como dashboard principal; academia dashboard en `/academia` |

---

## 5. Conflictos de Middleware / Autenticación / Autorización

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Guards** | `web` (session, User provider) | Sesión nativa `$_SESSION` + `Auth::isAuthenticated()` | **CRÍTICO** | **Mantener Laravel web guard**. Migrar `Auth::requireAuth()` → middleware `auth` |
| **Roles/Permisos** | `EnsureAdmin` middleware + `User::isAdmin()` | `Auth::hasPermission()` + `Auth::hasRole()` sobre config array | **ALTO** | Migrar a: tabla `roles`/`permissions` + Policies Laravel, o paquete `spatie/laravel-permission` |
| **CSRF** | `VerifyCsrfToken` middleware (automático) | `Auth::generateCSRFToken()` / `validateCSRFToken()` manual en forms | **ALTO** | **Eliminar CSRF manual**. Usar `@csrf` blade directive + middleware |
| **Session timeout** | Laravel config (120 min default) | `config/auth.php` session.timeout = 3600 (1 hora) | BAJO | Alinear en `config/session.php` |
| **Admin check** | `EnsureAdmin` middleware (User::isAdmin) | `Auth::hasPermission('sync')` / `Auth::hasRole('admin')` | MEDIO | Unificar: Policy `DevicePolicy::sync` + Gate `sync-firebird` |

---

## 6. Conflictos de Dependencias (Composer / NPM)

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Framework** | Laravel 10 (`laravel/framework:^10.0`) | Ninguno (PHP nativo) | BAJO | Sin conflicto |
| **ZKTeco SDK** | `coding-libs/zkteco-php:^0.0.35` | No usa | BAJO | Exclusivo principal |
| **Firebird PDO** | No configurado | `pdo_firebird` extension nativa | **ALTO** | Agregar `ext-pdo_firebird` en `composer.json` require + php.ini |
| **Bootstrap** | 5.3.3 (CDN en layout + Vite) | 5.x (CDN en login.php + CSS custom) | BAJO | Unificar: Vite + Bootstrap npm package (ya en principal) |
| **Bootstrap Icons** | 1.11.3 (CDN) | No usa (emojis + CSS) | BAJO | Mantener CDN |
| **Vite** | `laravel-vite-plugin`, `vite` | No usa (CSS/JS directo) | BAJO | Mantener Vite para assets academia |
| **Alpine.js** | No instalado (app.js parece vanilla) | No usa (vanilla JS) | **MEDIO** | **Instalar Alpine.js** para drawers, modals, tabs, ciclo selector — reemplaza JS custom |
| **Chart.js / ApexCharts** | No usa (SVG inline) | No usa (CSS bars) | BAJO | Evaluar si necesita librería gráficas para academia |

---

## 7. Conflictos de CSS / JavaScript / Frontend

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Sistema de diseño** | `app.css` (63KB) — Bootstrap override + CSS vars + dark mode tokens | `dashboard.css` (42KB) — CSS vars dark-only, KPIs, tablas, badges, drawers, grid | **ALTO** | **Unificar en Tailwind config**: extraer tokens de ambos → `tailwind.config.js` (colors, spacing, radius, shadows, breakpoints). Migrar componentes a Blade + Tailwind utilities |
| **Dark Mode** | 3 estados (light/dark/system) + FOUC-free script + `data-theme` | Solo dark (CSS vars fijas) | **ALTO** | **Mantener principal**. Verificar que componentes academia tengan variantes `dark:` |
| **Sidebar** | Bootstrap sidebar (data-sidebar-control, tooltip en colapsado) | CSS sidebar (toggleSidebar, tooltip hover, persistencia localStorage) | **MEDIO** | **Mantener comportamiento Base** (tooltip hover en colapsado, persistencia) + implementar en layout principal con Alpine.js |
| **Topbar** | Breadcrumb + search + notif + theme + date + mobile toggle | Título + user + ciclo selector + logout | **MEDIO** | Fusionar: topbar principal + selector ciclo (si módulo academia activo) |
| **Drawers/Modals** | Bootstrap 5 Modal + Toast stack + Confirm dialog | CSS Drawer overlay (empDrawer, asistDrawer) + inline alerts | **MEDIO** | **Unificar en componentes Blade + Alpine.js**: `<x-drawer>`, `<x-modal>`, `<x-toast>` |
| **Tablas** | Blade tables + Laravel pagination + Bootstrap table classes | HTML tables + custom pagination + scroll hints CSS/JS | **MEDIO** | Componente `<x-data-table>` con slots: headers, rows, filters, pagination, export |
| **Badges** | Bootstrap badge + custom `.cat-*` + `.badge-with-dot` | CSS `.badge--*` semánticos (status, role, asistencia, eval) | **MEDIO** | Componente `<x-badge>` con variantes: `status`, `role`, `attendance`, `eval` |
| **KPIs** | `<x-stat-card>` Blade component + sparkline SVG | `.kpi` / `.kpi-grid` CSS + iconos emoji | **MEDIO** | Extender `<x-stat-card>` para variantes academia (iconos, colores, trend) |
| **Gráficas** | SVG inline (trend line/area + donut) | CSS `.bar-chart` (flex bars) | BAJO | Mantener SVG principal; evaluar Chart.js si academia necesita más tipos |
| **JS Framework** | Vanilla JS (app.js 26KB — theme, sidebar, toasts, search, cmd palette) | Vanilla JS (dashboard.js 98 líneas — sidebar, drawers, ciclo, scroll-hint) | **MEDIO** | **Adoptar Alpine.js** para ambos: reemplaza JS custom, integra con Blade, ligero |

---

## 8. Conflictos de Configuración

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Database config** | `config/database.php` (Laravel standard, MySQL default) | `config/database.php` (custom loader .env + Firebird + MySQL) | **ALTO** | Mantener Laravel config. Agregar connection `firebird`. Mover credenciales Firebird a `.env` |
| **Auth config** | `config/auth.php` (guards, providers, passwords) | `config/auth.php` (users array, roles, permissions, session) | **ALTO** | Mantener Laravel auth config. Migrar users/roles a DB (seeder + migration) |
| **Queue config** | `config/queue.php` (sync, database, redis, ...) | No queue | BAJO | Mantener; configurar `database` driver para jobs ETL |
| **Session config** | `config/session.php` (driver, lifetime, secure, ...) | `config/auth.php` session.timeout | BAJO | Unificar en `config/session.php` |
| **App config** | `config/app.php` (locale=es, timezone, providers) | No config/app.php | BAJO | Mantener principal |
| **Filesystems** | `config/filesystem.php` (local, s3, ...) | No usa | BAJO | Mantener |

---

## 9. Conflictos de Versiones / Arquitectura

| Conflicto | Principal | Base | Severidad | Resolución |
|-----------|-----------|------|-----------|------------|
| **Laravel vs PHP nativo** | Laravel 10 (MVC, DI, Eloquent, Facades, Queue, Events) | PHP 8.1 nativo (page controller, global functions, PDO raw) | **CRÍTICO** | **No hay migración gradual**. Base debe reescribirse como módulo Laravel. No mezclar paradigmas |
| **Eloquent vs PDO Raw** | ORM completo (relationships, casts, scopes, mutations) | Queries manuales + helpers | **CRÍTICO** | Reescribir todo acceso a datos Base usando Eloquent/Models |
| **Testing** | PHPUnit + MySQL testing DB + Feature/Unit tests | Sin tests | **ALTO** | Escribir tests para todo código migrado (mínimo: render tests + unit services) |
| **Deployment** | `deploy.sh` + Vite build + artisan optimize | Solo copiar archivos PHP | **MEDIO** | Unificar pipeline: `composer install --no-dev`, `npm ci && npm run build`, `artisan migrate --force`, `artisan config:cache` |

---

## 10. Matriz de Severidad — Resumen

| Categoría | CRÍTICO | ALTO | MEDIO | BAJO | Total |
|-----------|---------|------|-------|------|-------|
| Nombres | 2 | 1 | 1 | 2 | 6 |
| Namespaces/Clases | 2 | 1 | 0 | 1 | 4 |
| Tablas/Esquema | 1 | 7 | 2 | 2 | 12 |
| Rutas | 0 | 1 | 1 | 2 | 4 |
| Middleware/Auth | 1 | 2 | 1 | 1 | 5 |
| Dependencias | 0 | 1 | 1 | 3 | 5 |
| CSS/JS/Frontend | 0 | 1 | 6 | 0 | 7 |
| Configuración | 0 | 2 | 0 | 3 | 5 |
| Versiones/Arquitectura | 2 | 1 | 1 | 0 | 4 |
| **TOTAL** | **8** | **16** | **12** | **14** | **50** |

---

## 11. Top 5 Conflictos Bloqueadores (Resolver Primero)

1. **Employee/Empleado/Profesor — Identidad unificada** (CRÍTICO, Nombres + Tablas)
   - Decidir: ¿Un solo modelo `Employee` con `type` enum? ¿O `Employee` + `Teacher` polimórficos?
   - Afecta: Attendance FK, DeviceEmployee pivot, Sync, Dashboard KPIs, Navegación

2. **Helpers globales / Auth estática / Database singleton** (CRÍTICO, Namespaces)
   - Eliminar `core/Helpers.php`, `core/Auth.php`, `core/Database.php`
   - Reemplazar por Services Laravel + DI + Facades

3. **PK Compuestas vs Surrogate Keys** (CRÍTICO, Tablas)
   - 8+ tablas Base con PK compuestas (Ciclo, Grupo, AlumnoNivel, HorarioDet)
   - Definir convención: `id` surrogate + `unique` en PK compuesta

4. **Firebird Connection + ETL Architecture** (ALTO, Dependencias + Arquitectura)
   - Requiere `pdo_firebird`, connection config, Job + Strategy pattern
   - Sin Firebird accesible → no se puede desarrollar/probar ETL

5. **Sistema de Diseño Unificado (Tailwind)** (ALTO, CSS/JS)
   - Extraer tokens de ambos CSS → `tailwind.config.js`
   - Migrar 956 líneas dashboard.css + 63KB app.css a utilities + components

---

## 12. Conflictos NO Existentes (Falsos Positivos Descartados)

| Área | Por qué NO hay conflicto |
|------|--------------------------|
| `Device` / Checadores | Exclusivo principal |
| `Fingerprint` / Huellas | Exclusivo principal |
| `ZktecoService` / SDK | Exclusivo principal |
| `SyncDeviceJob` / Queue | Exclusivo principal |
| `User` / Laravel Auth | Base usa array config, no modelo |
| `Bootstrap Icons` | Base usa emojis |
| `Vite` / Build | Base sin build step |
| `Policies` / Gates | Base usa `Auth::hasPermission` inline |

---

## 13. Recomendación de Resolución por Prioridad

```
FASE 1 (Bloqueadores — Semana 1-2)
├── 1.1 Decisión Employee/Teacher/Attendance (ADR)
├── 1.2 Eliminar helpers globales → Services Laravel
├── 1.3 Convención PK compuestas + surrogate keys
└── 1.4 Firebird connection + pdo_firebird

FASE 2 (Core ETL — Semana 3-4)
├── 2.1 FirebirdSyncJob + Strategies
├── 2.2 Comando artisan firebird:sync
└── 2.3 Tests ETL (mock Firebird)

FASE 3 (Dominio Académico — Semana 5-8)
├── 3.1 Migraciones + Modelos (15+)
├── 3.2 Services (CicloActual, HorarioResolver, KardexCalculator)
├── 3.3 Controllers + Routes (web + api)
└── 3.4 Views Blade + Components

FASE 4 (Unificación Frontend — Semana 9-10)
├── 4.1 Tailwind config tokens unificados
├── 4.2 Componentes Blade (Drawer, Modal, Table, Badge, KPI)
├── 4.3 Alpine.js integration
└── 4.4 Dark mode coverage academia

FASE 5 (Integración Final — Semana 11-12)
├── 5.1 Navegación unificada + Ciclo global
├── 5.2 Dashboard extendido (KPIs academia + asistencia)
├── 5.3 Testing completo (Feature + Unit)
└── 5.4 Documentación + ADRs
```