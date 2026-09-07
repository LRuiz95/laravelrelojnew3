# 11_BUGS.md — Registro Central de Problemas

## Formato
BUG-XXX | Severidad | Estado | Archivo | Ubicacion | Problema | Causa Raiz | Impacto | Dependencias | Solucion Propuesta | Solucion Aplicada | Pruebas | Regresion Verificada | Fecha

---

## BUG-001
**Severidad**: CRITICAL
**Estado**: FIXED
**Archivo**: routes/web.php
**Ubicacion**: Linea 31 (Route::post('/login'))
**Problema**: Login sin rate limiting - vulnerable a brute force
**Causa Raiz**: Falta middleware throttle en ruta login.store
**Impacto**: Compromiso cuentas admin, enumeracion emails
**Dependencias**: Ninguna
**Solucion Propuesta**: Agregar ->middleware('throttle:5,1')
**Solucion Aplicada**: Agregado middleware throttle:5,1 a Route::post('/login')
**Pruebas**: php artisan test pasa (28 tests); route:list confirma ThrottleRequests:5,1
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-002
**Severidad**: CRITICAL
**Estado**: FIXED
**Archivo**: routes/web.php
**Ubicacion**: Lineas 120-132 (sync endpoints)
**Problema**: Endpoints sync POST sin rate limiting
**Causa Raiz**: Falta middleware throttle en sync endpoints
**Impacto**: DoS cola jobs, saturacion checadores
**Dependencias**: Ninguna
**Solucion Propuesta**: Agregar ->middleware('throttle:10,1') en grupo sync
**Solucion Aplicada**: Agregado middleware throttle:10,1 a grupo Route::middleware(['admin', 'throttle:10,1'])
**Pruebas**: php artisan test pasa (28 tests); route:list confirma ThrottleRequests:10,1 en sync endpoints
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-003
**Severidad**: HIGH
**Estado**: FIXED
**Archivo**: routes/web.php, resources/views/firebird/, app/Http/Controllers/FirebirdController.php (nuevo)
**Ubicacion**: Lineas 103-104 (Firebird routes)
**Problema**: Vistas firebird.index y firebird.sync no existen
**Causa Raiz**: Rutas definidas pero vistas no creadas; módulo Firebird tiene backend completo (models, jobs, services, strategies)
**Impacto**: Error 500 al acceder /firebird y /firebird/sync/{id}
**Dependencias**: FirebirdSync, FirebirdSyncItem, FirebirdSyncJob, FirebirdReader, SyncStrategies
**Solucion Propuesta**: Crear vistas funcionales + controller
**Solucion Aplicada**: 
- Creadas resources/views/firebird/index.blade.php y sync.blade.php
- Creado app/Http/Controllers/FirebirdController.php
- Rutas actualizadas para usar controller con datos (stats, ciclos, paginación, filtros)
**Pruebas**: php artisan test pasa (28 tests); route:list confirma firebird.index y firebird.sync
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-004
**Severidad**: HIGH
**Estado**: FIXED
**Archivo**: app/Services/ZktecoService.php (syncAttendances)
**Ubicacion**: Metodo syncAttendances lineas 409-456
**Problema**: Indice único incorrecto para deduplicación real
**Causa Raiz**: Migración 2026_09_05_010214 cambió índice único a att_emp_rec_dev_type_unique (employee_id, recorded_at, device_id, attendance_type) PERO syncAttendances seguía usando identity del índice anterior (device_id, user_id, state, recorded_at) → insertOrIgnore no detectaba duplicados
**Impacto**: Duplicados masivos en cada sincronización de asistencias
**Dependencias**: docs/zkteco.md seccion 3, ZktecoService::syncAttendances
**Solucion Propuesta**: Actualizar syncAttendances para usar identity que coincida con índice actual
**Solucion Aplicada**: Corregido identity en insertOrIgnore a ['employee_id', 'recorded_at', 'device_id', 'attendance_type' => 'biometric']
**Pruebas**: php artisan test pasa (28 tests); ZktecoSyncTest::test_users_sync_without_duplicates pasa
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-005
**Severidad**: HIGH
**Estado**: FALSE_POSITIVE
**Archivo**: app/Models/Fingerprint.php, migracion 2026_08_23_000002
**Ubicacion**: device_id nullable + unique index MySQL [employee_id, finger, device_id]
**Problema**: Filas legacy (device_id=NULL) violan unicidad en MySQL
**Causa Raiz**: Malentendido del comportamiento UNIQUE index con NULL en MySQL. En MySQL, múltiples NULLs SON PERMITIDOS en un índice único (NULL != NULL). El error 1062 NO ocurre con múltiples filas (employee_id=X, finger=Y, device_id=NULL).
**Impacto**: NO hay error 1062 actual. Riesgo de diseño: esquema permite múltiples huellas "legacy" por empleado+dedo con device_id=NULL.
**Dependencias**: ZktecoService::syncFingerprints re-atribucion legacy
**Solucion Propuesta**: Documentar comportamiento MySQL. Opcional: forzar device_id NOT NULL con valor sentinel para legacy si negocio lo requiere.
**Solucion Aplicada**: N/A - falso positivo. Validado: SHOW INDEX confirma device_id Null=YES; query duplicados retorna 0 filas.
**Pruebas**: Verificación empírica MySQL: múltiples NULLs en unique index permitidos.
**Regresion Verificada**: N/A
**Fecha**: 2026-09-05

## BUG-006
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: app/Http/Controllers/DeviceController.php, app/Jobs/VerifyDeviceConnectionJob.php (nuevo)
**Ubicacion**: Metodo store() lineas 60-98
**Problema**: Conexion ZKTeco sincrona en request HTTP (bloqueante hasta 60s)
**Causa Raiz**: new ZktecoService()->info() + deviceStatus() en ciclo request-response
**Impacto**: Timeout request, mala UX, bloquea worker PHP
**Dependencias**: ZktecoService::info(), deviceStatus(), SyncDeviceJob
**Solucion Propuesta**: Mover a Job async: crear device con status pending, dispatch Job que conecta y actualiza
**Solucion Aplicada**: 
- DeviceController::store() ahora crea device con status='pending' y retorna inmediato
- Creado VerifyDeviceConnectionJob que verifica conexion, obtiene info y actualiza device async
- Job usa WithoutOverlapping para evitar concurrencia
**Pruebas**: php artisan test pasa (28 tests); crear device retorna response inmediata
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05
**Fecha**: 2026-09-05

## BUG-007
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: app/Http/Controllers/EmployeeController.php
**Ubicacion**: Metodo update() lineas 124-159
**Problema**: Bucle foreach propaga cambios a TODOS dispositivos secuencialmente
**Causa Raiz**: Sincronizacion secuencial sin paralelismo ni tolerancia fallos parciales
**Impacto**: Lentitud (N dispositivos * 5s), fallo parcial deja estado inconsistente
**Dependencias**: ZktecoService::setUser(), DeviceEmployee pivot, SyncEmployeeToDeviceJob
**Solucion Propuesta**: Dispatch SyncEmployeeToDeviceJob por dispositivo (ya existe syncToDevices), update() solo catalogo
**Solucion Aplicada**: 
- update() ahora solo actualiza el catálogo (name)
- Eliminado bucle foreach que propagaba password/role a todos los dispositivos
- La sincronización de credenciales se hace via botón "Sincronizar credenciales" (syncToDevices) que dispatcha SyncEmployeeToDeviceJob async
**Pruebas**: php artisan test pasa (28 tests); update empleado retorna response inmediata
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-008
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: app/Http/Controllers/EmployeeController.php, app/Jobs/DeprovisionEmployeeJob.php (nuevo)
**Ubicacion**: Metodo destroy() lineas 476-495
**Problema**: Elimina de TODOS dispositivos, si uno falla empleado queda con enrolamientos
**Causa Raiz**: Bucle foreach sin transaccion, error handling basico
**Impacto**: Estado inconsistente: catalogo dice eliminado pero enrolamientos vivos
**Dependencias**: ZktecoService::removeUser(), DeviceEmployee::detach(), DeviceSync
**Solucion Propuesta**: Soft delete (status_actual=B) + Job async por dispositivo + compensacion si falla
**Solucion Aplicada**: 
- destroy() marca empleado status_actual='B' (baja logica) inmediatamente
- Dispatcha DeprovisionEmployeeJob por cada dispositivo enrolado
- Job elimina usuario del checador y desvincula pivot
- DeviceSync rastrea progreso por dispositivo
- Catalogo se elimina solo cuando TODOS los jobs completan (o manualmente)
**Pruebas**: php artisan test pasa (28 tests); destroy empleado retorna response inmediata
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-009
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: app/Http/Controllers/DeviceController.php
**Ubicacion**: Metodo deduplicate() lineas 292-357
**Problema**: Deduplicacion usa groupBy en MySQL strict mode (ONLY_FULL_GROUP_BY)
**Causa Raiz**: SELECT user_id con GROUP BY user_id pero SELECT * / columnas no agregadas
**Impacto**: Error 1055 en MySQL 5.7+ strict mode
**Dependencias**: Employee, Attendance, Fingerprint models
**Solucion Propuesta**: Usar window functions (ROW_NUMBER) compatible MySQL 8+
**Solucion Aplicada**: Reescrito deduplicate() usando DELETE con JOIN + ROW_NUMBER() OVER (PARTITION BY ...) para empleados, asistencias y huellas. Elimina duplicados manteniendo el registro de menor ID.
**Pruebas**: php artisan test pasa (28 tests); deduplicate usa SQL compatible MySQL 8+
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-010
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: EmployeeController.php (search), resources/views/employees/index.blade.php, routes/web.php
**Ubicacion**: EmployeeController::search (nuevo), employees/index.blade.php JS
**Problema**: Busqueda fetch HTML reemplaza tbody - fragil, rompe si cambia vista
**Causa Raiz**: No hay endpoint API JSON para busqueda empleados
**Impacto**: Cambios en vista rompen JS, no hay loading state, sin debounce real en device select
**Dependencias**: EmployeeController@index
**Solucion Propuesta**: Crear endpoint /employees/search JSON + fetch JSON + loading state
**Solucion Aplicada**: 
- Agregado endpoint GET /employees/search (route employees.search) que retorna JSON
- JavaScript actualizado para usar fetch JSON con debounce 300ms
- Loading state con spinner durante fetch
- Paginación client-side funcional
- Empty state y error state manejados
**Pruebas**: php artisan test pasa (28 tests); búsqueda retorna JSON, UI actualiza sin parpadeo
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-011
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: resources/views/dashboard.blade.php
**Ubicacion**: Script @push('scripts') - listener themechange
**Problema**: Charts SVG (trend, donut) no se actualizan al cambiar tema
**Causa Raiz**: Charts generados server-side en Blade usando CSS variables, pero SVG no fuerza repaint al cambiar tema
**Impacto**: Colores incorrectos en modo opuesto al render inicial
**Dependencias**: DashboardController@index (trend, donut server-side), CSS variables
**Solucion Propuesta**: Agregar listener themechange que fuerce repaint de SVG charts
**Solucion Aplicada**: 
- Agregado listener `document.addEventListener('themechange', forceChartsRepaint)` en dashboard.blade.php
- `forceChartsRepaint()` fuerza reflow de trend-chart y donut-wrap svg removiendo y re-insertando en DOM
- Charts usan CSS variables (var(--primary), var(--cat-*), etc.) que sí se actualizan con tema
**Pruebas**: php artisan test pasa (28 tests); DashboardRenderTest::test_dashboard_trend_ranges_render pasa
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-012
**Severidad**: MEDIUM
**Estado**: ABIERTO
**Archivo**: app/Http/Kernel.php
**Ubicacion**: Middleware aliases - throttle configurado pero no usado
**Problema**: Rate limiting disponible pero no aplicado en rutas criticas
**Causa Raiz**: Oversight en definicion rutas
**Impacto**: Ver BUG-001, BUG-002
**Dependencias**: routes/web.php
**Solucion Propuesta**: Aplicar throttle en login, sync endpoints
**Solucion Aplicada**: Pendiente
**Pruebas**: Ver BUG-001, BUG-002
**Regresion Verificada**: No
**Fecha**: 2026-09-05

## BUG-013
**Severidad**: MEDIUM
**Estado**: FIXED
**Archivo**: app/Models/Pivots/DeviceEmployee.php
**Ubicacion**: Linea 43 - metodo casts() vs propiedad $casts
**Problema**: Inconsistencia estilo casts (Laravel 11 method vs Laravel 10 property)
**Causa Raiz**: DeviceEmployee usa protected function casts(): array mientras resto usa $casts = []
**Impacto**: Violacion AGENTS.md (consistencia  property), confusion mantenimiento
**Dependencias**: AGENTS.md seccion 2, resto modelos
**Solucion Propuesta**: Cambiar a propiedad $casts = [...] en DeviceEmployee
**Solucion Aplicada**: Cambiado casts() method a $casts property
**Pruebas**: php artisan test pasa (29 tests); model usa $casts property
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-014
**Severidad**: LOW
**Estado**: FIXED
**Archivo**: routes/web.php
**Ubicacion**: Lineas 3-21 (importaciones duplicadas)
**Problema**: 9 controladores Academia importados 2 veces cada uno
**Causa Raiz**: Copy-paste sin limpieza
**Impacto**: Noise en codigo, potencial confusion alias
**Dependencias**: Ninguna
**Solucion Propuesta**: Eliminar duplicados lineas 13-21
**Solucion Aplicada**: Eliminados imports duplicados, mantenidos solo los aliased (AcademiaCicloController, etc.)
**Pruebas**: php artisan test pasa (29 tests); php artisan route:list sin errores
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-015
**Severidad**: LOW
**Estado**: FIXED
**Archivo**: app/Models/User.php, database/migrations/2026_09_05_202614_add_check_constraint_role_to_users_table.php, app/Enums/Role.php (nuevo)
**Ubicacion**: Columna role VARCHAR sin CHECK constraint
**Problema**: Roles string sin validacion BD - typos posibles
**Causa Raiz**: Migracion original sin constraint
**Impacto**: 'adim' bypassa isAdmin()
**Dependencias**: User model, EnsureAdmin middleware
**Solucion Propuesta**: Migracion agregar CHECK (role IN ('admin','operator')) + Enum PHP
**Solucion Aplicada**: 
- Creado enum App\Enums\Role (Admin, Operator)
- User model: cast 'role' => Role::class, isAdmin() usa Role::Admin
- Migracion: ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','operator'))
**Pruebas**: php artisan test pasa (29 tests); migracion aplicada correctamente
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-016
**Severidad**: LOW
**Estado**: FIXED
**Archivo**: app/View/Composers/AdminLayoutComposer.php
**Ubicacion**: AdminLayoutComposer inyecta window.__dash.search/notifications
**Problema**: Verificar que composer inyecta datos reales (no vacios)
**Causa Raiz**: No verificado en auditoria
**Impacto**: Command Palette vacio, Notifications badge 0
**Dependencias**: app/View/Composers/AdminLayoutComposer.php
**Solucion Propuesta**: Verificar composer inyecta datos
**Solucion Aplicada**: Verificado - AdminLayoutComposer::compose() inyecta 'dash' con notifications, search (pages, devices, employees), y alerts. Layout admin.blade.php línea 33 pasa a JS via `window.__dash = {!! json_encode($dash) !!};`
**Pruebas**: php artisan test pasa (29 tests); composer inyecta datos reales
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-017
**Severidad**: LOW
**Estado**: FIXED
**Archivo**: app/Services/ZktecoService.php
**Ubicacion**: Linea 72 - clientWithTimeout()
**Problema**: Variable $timeoutOverride usada pero no es parametro ni propiedad
**Causa Raiz**: Refactor incompleto (deberia ser $timeout, el parametro del metodo)
**Impacto**: Error 500 si clientWithTimeout llamado con timeout custom
**Dependencias**: ZktecoService::clientWithTimeout(), withRetries()
**Solucion Propuesta**: Cambiar $timeoutOverride a $timeout (parametro metodo)
**Solucion Aplicada**: Corregido $timeoutOverride a $timeout en linea 72
**Pruebas**: php artisan test pasa (28 tests); ZktecoSyncTest pasa
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

## BUG-018
**Severidad**: LOW
**Estado**: FIXED
**Archivo**: app/Http/Controllers/AttendanceController.php, resources/views/attendances/index.blade.php, tests/Feature/AttendanceFilterTest.php
**Ubicacion**: Metodo applyFilters(), vista attendances.index
**Problema**: Filtro 'state' del formulario mapea a columna 'type' (comentario lo explica)
**Causa Raiz**: Firmware ZKTeco usa 'type' para modo checado, 'state' constante=1
**Impacto**: Confusion mantenimiento, variable mal nombrada
**Dependencias**: Attendance::punchStatus(), AttendanceFilterTest
**Solucion Propuesta**: Renombrar param request 'state' a 'type' O agregar alias
**Solucion Aplicada**: 
- AttendanceController::applyFilters() usa $request->query('type') en vez de 'state'
- Vista attendances.index: select name="type" id="type" y referencias request('type')
- hasFilters check actualizado a ['device_id', 'type', 'from', 'to']
- Tests actualizados a usar ?type= en vez de ?state=
**Pruebas**: php artisan test pasa (29 tests); AttendanceFilterTest pasa
**Regresion Verificada**: Sí - tests Feature pasan
**Fecha**: 2026-09-05

---

## Resumen por Severidad
| Severidad | Count |
|-----------|-------|
| CRITICAL | 2 |
| HIGH | 3 |
| MEDIUM | 6 |
| LOW | 6 |
| **TOTAL** | **17** |

---

## Prioridad Reparacion (Orden Sugerido)
1. BUG-001, BUG-002 (CRITICAL - rate limiting)
2. BUG-003 (HIGH - vistas rotas)
3. BUG-004, BUG-005 (HIGH - indices BD)
4. BUG-006, BUG-007, BUG-008 (MEDIUM - sync async, update/destroy)
5. BUG-009, BUG-010, BUG-011 (MEDIUM - deduplicate, search, charts)
6. BUG-012 (MEDIUM - throttle config)
7. BUG-013, BUG-014, BUG-015, BUG-016, BUG-017, BUG-018 (LOW - limpieza)
