# Reglas para la Siguiente Fase — Implementación de Fusión

> **Generado en Fase 0** — Guía obligatoria para Fase 1+. No implementar nada en Fase 0.

---

## 1. Qué Hacer Primero (Orden Estricto)

### Paso 0 — Preparación de Infraestructura (Bloqueador)
```bash
# 1. Verificar/Instalar pdo_firebird
php -m | grep pdo_firebird
# Si no está: instalar en php.ini + reiniciar php-fpm

# 2. Verificar conectividad Firebird
php -r "new PDO('firebird:dbname=host:/path/DATOS.FDB;charset=UTF-8', 'SYSDBA', 'masterkey'); echo 'OK';"

# 3. Agregar connection 'firebird' en config/database.php (ver ARCHITECTURE_TARGET.md)
# 4. Variables .env: FIREBIRD_DSN, FIREBIRD_USER, FIREBIRD_PASS

# 5. Queue worker DEBE estar corriendo para jobs ETL
php artisan queue:work --timeout=600 --tries=3 --queue=default
```

**⚠️ SIN Firebird accesible → NO se puede desarrollar/probar ETL. Documentar como INFRASTRUCTURE_DEPENDENCY.**

---

### Paso 1 — Decisiones Arquitectónicas (ADRs) — **Antes de escribir código**

Crear ADRs en `docs/ADR/` para:
1. **ADR-001**: Employee/Teacher/Attendance Unification (ver INTEGRATION_CONFLICTS.md #1)
2. **ADR-002**: Composite PK Strategy para modelos Firebird (ver INTEGRATION_CONFLICTS.md #3)
3. **ADR-003**: Módulo Académico — Same Repo vs Package vs Microservice
4. **ADR-004**: Firebird Access — Eloquent Read-only vs PDO Directo en Service

**Regla**: No escribir migraciones/modelos hasta que ADR-001 y ADR-002 estén aprobados.

---

### Paso 2 — Eliminar Helpers Globales / Auth Estática (CRÍTICO)
```bash
# Archivos a ELIMINAR (no migrar — reescribir como Services Laravel):
# ProyectoBase/core/Helpers.php     → Services inyectables + Eloquent
# ProyectoBase/core/Auth.php        → Laravel Auth + Policies + Gates
# ProyectoBase/core/Database.php    → config/database.php + DB facade

# En laravelrelojnew: crear Services equivalentes:
app/Services/FirebirdReader.php
app/Services/CicloActualService.php
app/Services/HorarioResolver.php
app/Services/KardexCalculator.php
app/Services/PersonaContratosResolver.php
app/Services/Exportable.php (trait)
```

---

## 2. Qué NO Debe Tocarse (Protegido)

| Archivo/Patrón | Motivo | Excepción |
|----------------|--------|-----------|
| `app/Models/Device.php` | Core ZKTeco, probado | Solo añadir relations si nueva tabla |
| `app/Models/Fingerprint.php` | Core biométrico | Solo añadir campos si necesario |
| `app/Services/ZktecoService.php` | Complejo, probado, crítico | Solo bugfixes, no refactor |
| `app/Jobs/SyncDeviceJob.php` | Queue + progress + events funcionando | Solo extender patrón para FirebirdSyncJob |
| `resources/views/layouts/admin.blade.php` | Shell principal, dark mode, toasts, cmd palette | Solo añadir slots (nav, ciclo selector) |
| `app/Http/Kernel.php` | Middleware base | Añadir `EnsureCicloActivo` |
| `config/auth.php`, `config/database.php` (MySQL), `config/queue.php` | Config principal | Solo añadir connection `firebird` |
| `resources/css/app.css` + `resources/js/app.js` | Build Vite funcionando | Migrar a Tailwind + Alpine.js gradualmente |
| Tests existentes (`tests/Feature/*`, `tests/Unit/*`) | Paridad MySQL | Añadir tests nuevos, no modificar existentes sin causa |

---

## 3. Componentes a Migrar (Orden de Prioridad)

### Prioridad 1 — Bloqueadores (Semanas 1-2)

| Componente | Archivos Origen (Base) | Destino (Principal) | Tipo |
|------------|------------------------|---------------------|------|
| Firebird Connection | `core/Database.php` (firebird method) | `config/database.php` + `FirebirdReader` Service | NEW |
| FirebirdSync Model + Migration | — | `database/migrations/..._create_firebird_syncs_table.php` + Model | NEW |
| FirebirdSyncJob | `sincronizar.php` (lógica POST) | `app/Jobs/FirebirdSyncJob.php` | NEW |
| SyncStrategies (3) | `sincronizar.php` (sync_cycle_table, sync_smart_sync) | `app/Services/SyncStrategies/*.php` | NEW |
| Command artisan | `sincronizar.php` (form POST) | `app/Console/Commands/FirebirdSyncCommand.php` | NEW |
| Composite PK Convention | `Helpers.php` (qm, sync_get_pk_columns) | ADR-002 + Base Model trait | NEW |

### Prioridad 2 — Dominio Académico Core (Semanas 3-5)

| Componente | Archivos Origen | Destino | Tipo |
|------------|-----------------|---------|------|
| Ciclo Model + Migration | `sections/ciclos.php`, `sincronizar.php` (CICLOS) | `app/Models/Academia/Ciclo.php` + migration | NEW |
| Catálogos (Nivel, Turno, Sede, Plan, Materia, MetodoEval, Contrato) | `sections/niveles.php`, `turnos.php`, `sedes.php`, `planes.php`, `materias.php`, `metodos-eval.php`, `contratos.php` | Models + migrations + seeders | NEW |
| Grupo Model + Migration | `sections/grupos.php`, `horarios-clase.php` | `Grupo` + `AlumnoGrupo` pivot | NEW |
| Alumno + Kardex | `sections/alumnos.php`, `alumnos-ciclo.php`, `kardex.php`, `inscripciones.php` | `Alumno`, `AlumnoGrupo`, `AlumnoKardex` | NEW |
| Profesor Model | `sections/docentes.php`, `horario-persona.php` | `Profesor` (o STI en Employee) | MERGE |
| HorarioDet + SesionBase | `sections/horarios-clase.php`, `horarios-curso.php`, `horario-base.php`, `horarios-aula.php` | `HorarioDet`, `Curso`, `SesionBase` | NEW |

### Prioridad 3 — Unificación Employee/Attendance (Semana 4-5)

| Componente | Acción | Archivos Afectados |
|------------|--------|-------------------|
| Employee fields | Migración: add `type`, `numero_empleado`, `clave_profesor`, `departamento`, `cargo`, `contrato`, `status_actual`, `fecha_ingreso`, `id_campus`, `nivel`, `tarjeta_id` | `database/migrations/..._add_academia_fields_to_employees.php`, `app/Models/Employee.php` |
| Attendance fields | Migración: add `attendance_type`, `source`, `hora_entrada`, `hora_salida`, `hora_salida_comer`, `hora_regreso_comer` | `database/migrations/..._add_academia_fields_to_attendances.php`, `app/Models/Attendance.php` |
| Unique Index Attendance | Cambiar índice único a `(employee_id, recorded_at, device_id, attendance_type)` | Migration + Model |
| Employee Scopes | `scopeBiometric()`, `scopeAdmin()`, `scopeTeacher()` | `app/Models/Employee.php` |

### Prioridad 4 — Controllers + Views Academia (Semanas 6-8)

| Controller | Secciones Base | Rutas | Vistas |
|------------|----------------|-------|--------|
| `CicloController` | `ciclos.php` | `academia.ciclos.*` | `academia/ciclos/*` |
| `GrupoController` | `grupos.php`, `inscripciones.php` | `academia.grupos.*` | `academia/grupos/*` |
| `AlumnoController` | `alumnos.php`, `alumnos-ciclo.php` | `academia.alumnos.*` | `academia/alumnos/*` |
| `KardexController` | `kardex.php` | `academia.kardex.*` | `academia/kardex/*` |
| `HorarioClaseController` | `horarios-clase.php` | `academia.horarios.clase` | `academia/horarios/clase` |
| `HorarioCursoController` | `horarios-curso.php` | `academia.horarios.curso` | `academia/horarios/curso` |
| `HorarioAulaController` | `horarios-aula.php` | `academia.horarios.aula` | `academia/horarios/aula` |
| `ProfesorController` | `docentes.php`, `horario-persona.php` | `academia.profesores.*` | `academia/profesores/*` |
| `PlanController` | `planes.php`, `materias.php`, `metodos-eval.php` | `academia.planes.*` | `academia/planes/*` |
| `AsistenciaClaseController` | `asistencia-clases.php` | `academia.asistencia-clases.*` | `academia/asistencia-clases/*` |

**API Endpoints (routes/api.php):**
- `GET api/academia/grupos-por-ciclo?ciclo=...`
- `GET api/academia/alumnos-por-grupo?ciclo=...&grupo=...`
- `GET api/academia/ciclos-disponibles?tipo=horarios|kardex|cursos`
- `GET api/academia/planes-por-nivel?nivel=...`

### Prioridad 5 — Frontend Unificación (Semanas 9-10)

| Componente | Acción |
|------------|--------|
| Tailwind Config | Extraer tokens de `dashboard.css` + `app.css` → `tailwind.config.js` |
| Componentes Blade | Crear: `data-table`, `badge`, `drawer`, `modal`, `kpi-card`, `horario-grid`, `asistencia-clase-grid` |
| Alpine.js | Instalar + migrar: sidebar toggle, drawers, modals, ciclo selector, tabs, theme toggle |
| Dark Mode | Verificar cobertura total academia (todos componentes con `dark:` variants) |
| Icons | Reemplazar emojis en Base con Bootstrap Icons |

### Prioridad 6 — Navegación + Ciclo Global (Semana 10-11)

| Componente | Acción |
|------------|--------|
| `config/navigation.php` | Crear desde `$nav_menu` Base |
| `AdminLayoutComposer` | Extender: inyectar `navigation`, `ciclo_actual`, `kpis_nav` |
| `EnsureCicloActivo` Middleware | Crear + registrar en Kernel |
| Topbar | Añadir selector ciclo (condicional: solo si academia activo) |
| KPIs Nav Badges | Queries optimizadas (cache Redis opcional) |

---

## 4. Dependencias a Resolver Antes de Migrar

| Dependencia | Requerida Para | Cómo Resolver |
|-------------|----------------|---------------|
| **Firebird accesible** | ETL development + testing | Verificar red/puerto 3050 + credenciales .env |
| **pdo_firebird extension** | PHP → Firebird | `extension=pdo_firebird` en php.ini |
| **ADR-001 aprobado** | Migraciones Employee/Attendance | Reunión técnica + documento decisión |
| **ADR-002 aprobado** | Modelos academia (PK compuestas) | Reunión técnica + documento decisión |
| **Queue worker** | FirebirdSyncJob, exports | `php artisan queue:work --daemon` en producción |
| **Scheduler cron** | Sync automático Firebird | `* * * * * php artisan schedule:run` |
| **Alpine.js instalado** | Frontend components | `npm install alpinejs` + import en `app.js` |
| **Tailwind configurado** | CSS unification | `tailwind.config.js` con tokens unificados |

---

## 5. Pruebas Necesarias (Mínimas por Componente)

| Componente | Test Tipo | Qué Verificar |
|------------|-----------|---------------|
| `FirebirdReader` | Unit (mock PDO) | Conexión, fetch columns, fetch rows, error handling |
| `SyncStrategies` | Unit (mock FirebirdReader + MySQL) | CycleDirect: DELETE+INSERT por ciclo; AlumnosByCycle: 2-phase; CatalogSmart: INSERT/UPDATE/DELETE orphans |
| `FirebirdSyncJob` | Feature (MySQL testing DB + mock Firebird) | Dispatch → progress events → status completed/failed → log persisted |
| `CicloActualService` | Unit | Resolve: URL param > Session > Default (horarios_det) |
| `KardexCalculator` | Unit | `calcularKardexAsignatura` con casos: aprobado, reprobado, sin derecho, pendiente |
| `HorarioResolver` | Unit | `build_horario_grid` + `get_horario_base` estructura correcta |
| `PersonaContratosResolver` | Unit | Unifica admin + PTC + PA sin duplicados |
| Controllers Academia | Feature (render + seed data) | Index/create/edit/show + filtros + pagination + export CSV |
| Asistencia Clases | Feature | Grid render + drawer open/close + POST save + validación estados |
| Employee Unification | Feature | CRUD Employee con type=admin/teacher/biometric + attendances polimórficas |
| Dashboard KPIs | Feature | KPIs render + kpisJson endpoint + trend ranges + donut + pipeline |
| Dark Mode | Feature (browser opcional) | Toggle 3 estados + persistencia + sin FOUC + cobertura academia |

**Regla de Oro Testing**: Todos los Feature tests **deben sembrar al menos 1 fila por modelo involucrado** (ver `DashboardRenderTest::seedRenderData()`). SQLite **prohibido** para tests de render.

---

## 6. Infraestructura Externa Necesaria (INFRASTRUCTURE_DEPENDENCY)

| Recurso | Para Qué | Estado | Acción si No Disponible |
|---------|----------|--------|-------------------------|
| **Firebird DB (DATOS.FDB)** | ETL source, schema discovery, testing real | ❓ Desconocido | Documentar como bloqueador; desarrollar con mock PDO; test real solo en staging |
| **ZKTeco Devices en red** | Funcionalidad principal (ya existe) | ✅ Asumido | No bloquea migración Base |
| **Queue Worker (database)** | SyncDeviceJob, FirebirdSyncJob, exports | ❓ Desconocido | Documentar requisito; desarrollo local con `sync` driver |
| **Scheduler Cron** | Firebird sync automático nocturno | ❓ Desconocido | Documentar; desarrollo manual via command |
| **Redis (opcional)** | Cache resolve_*, session, queue | ❌ No configurado | Opcional: usar `database` queue + `file`/`array` cache |

---

## 7. Checklist de Calidad por PR (Definition of Done)

Antes de mergear cualquier PR de migración:

- [ ] **ADR referenciado** (si toca arquitectura: Employee, PK, Firebird)
- [ ] **Migración reversible** (`up()` + `down()` completos, FK explícitos, índices)
- [ ] **Modelo tipado** (return types, parameter types, `$casts` property style)
- [ ] **Relaciones Eloquent** definidas (no `DB::raw` innecesario)
- [ ] **Service inyectable** (no helpers globales, no facades en Services)
- [ ] **Form Request** para validación (no inline en controller)
- [ ] **Policy/Gate** para autorización (no `if` sueltos)
- [ ] **Vista Blade** usa componentes (`<x-*>`) + slots
- [ ] **Dark mode** verificado (clases `dark:` en componentes nuevos/modificados)
- [ ] **Responsive** verificado (mobile-first, breakpoints `sm:`, `md:`, `lg:`)
- [ ] **Tests** añadidos: Unit (services) + Feature (controllers render + seed)
- [ ] **Paridad MySQL** confirmada (tests corren en MySQL testing DB)
- [ ] **Sin referencias rotas** (grep: clase, método, ruta, columna, vista)
- [ ] **CHANGELOG.md** actualizado (si cambio user-facing)

---

## 8. Comandos de Verificación Continua

```bash
# Antes de cada commit:
./vendor/bin/pint --test                    # Code style
./vendor/bin/phpstan analyse --level=5      # Static analysis (configurar)
php artisan test --testdox                  # Tests (MySQL testing DB)
php artisan migrate:fresh --seed --env=testing  # Migraciones limpias en testing

# Verificar referencias rotas:
grep -r "Helpers::" app/ resources/ routes/  # Debe dar 0 resultados
grep -r "Auth::" app/ --include="*.php" | grep -v "Illuminate\\Support\\Facades\\Auth"  # Solo Facade
grep -r "Database::" app/ --include="*.php"  # Debe dar 0 resultados

# Verificar que no hay SQLite en tests:
grep -r "sqlite" phpunit.xml tests/  # Debe dar 0 (salvo config por defecto no usada)
```

---

## 9. Comunicación y Seguimiento

- **Weekly Sync**: Revisar avance vs plan, bloqueadores Firebird/infra
- **ADR Reviews**: Decisiones 001-004 en primera semana
- **Code Review**: Obligatorio para todo código nuevo (reviewer agent + humano)
- **Demo Quincenal**: Dashboard unificado + ETL log + Asistencia clases grid

---

## 10. Criterio de Éxito Fase 1 (Primer Entregable Funcional)

Al final de las primeras 4 semanas, debe funcionar **end-to-end**:

1. ✅ `php artisan firebird:sync 2025-2025-3` → sincroniza Ciclos + Grupos + Catálogos sin errores
2. ✅ `FirebirdSyncJob` disparado por comando → progreso visible en `/operations/firebird-sync`
3. ✅ Modelos `Ciclo`, `Grupo`, `Nivel`, `Turno`, `Sede` creados + seeders + factories
4. ✅ `CicloActualService` resuelve ciclo global + inyectado en layout via Composer
5. ✅ Employee/Attendance unificación migrada + tests pasando
6. ✅ Zero referencias a `Helpers.php`, `Auth.php`, `Database.php` de Base

**Si Firebird no está disponible**: Entregable se limita a modelos + migraciones + command structure + tests con mock. Documentar como `INFRASTRUCTURE_DEPENDENCY`.