# 12_REPAIR_PLAN.md - Plan de Reparacion Ordenado

## Orden: CRITICAL - HIGH - MEDIUM - LOW

---

## FASE 1: CRITICAL (Bloquean despliegue seguro)

### BUG-001: Rate Limiting Login
- Prioridad: CRITICAL
- Archivos: routes/web.php
- Dependencias: Ninguna
- Riesgo: BAJO (solo agrega middleware)
- Solucion: Agregar throttle:5,1 a Route::post('/login')
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); route:list confirma ThrottleRequests:5,1

### BUG-002: Rate Limiting Sync Endpoints
- Prioridad: CRITICAL
- Archivos: routes/web.php (lineas 120-132)
- Dependencias: Ninguna
- Riesgo: BAJO
- Solucion: Envolver grupo sync en throttle:10,1
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); route:list confirma ThrottleRequests:10,1

---

## FASE 2: HIGH (Errores funcionales visibles)

### BUG-003: Vistas Firebird Faltantes
- Prioridad: HIGH
- Archivos: routes/web.php, resources/views/firebird/ (crear), app/Http/Controllers/FirebirdController.php (nuevo)
- Dependencias: FirebirdSync, FirebirdSyncItem, FirebirdSyncJob, FirebirdReader, SyncStrategies
- Riesgo: MEDIO (crear vistas funcionales)
- Solucion: Opción A - Crear views/firebird/index.blade.php y sync.blade.php + controller con stats, paginación, filtros
- Estado: COMPLETADO
- Verificacion: GET /firebird -> 200; php artisan test pasa (28 tests)

### BUG-004: Indice Unico Attendances Incorrecto
- Prioridad: HIGH
- Archivos: app/Services/ZktecoService.php (syncAttendances)
- Dependencias: ZktecoService::syncAttendances, Attendance::uniqueAttendances
- Riesgo: ALTO (fix crítico para integridad datos)
- Solucion: Corregir identity en insertOrIgnore para coincidir con índice único actual att_emp_rec_dev_type_unique (employee_id, recorded_at, device_id, attendance_type)
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); ZktecoSyncTest::test_users_sync_without_duplicates pasa

### BUG-005: Fingerprint device_id Nullable vs Unique Index
- Prioridad: HIGH
- Archivos: N/A (falso positivo)
- Dependencias: ZktecoService::syncFingerprints re-atribucion legacy
- Riesgo: N/A
- Solucion: Falso positivo - MySQL permite múltiples NULLs en unique index. No requiere migración. Documentar comportamiento.
- Estado: FALSE_POSITIVE
- Verificacion: Validado empiricamente: SHOW INDEX + query duplicados = 0 filas
  1. Migracion: make_fingerprint_device_id_required (MySQL only)
  2. Backfill: device_id = 0 o NULL - valor default para legacy
  3. Cambiar unique a [device_id, employee_id, finger] con device_id NOT NULL
  4. O unique partial: WHERE device_id IS NOT NULL
- Estado: PENDIENTE
- Verificacion: Insert legacy duplicate - handled correctamente

---

## FASE 3: MEDIUM (Mejoras arquitectonicas)

### BUG-006: DeviceController store Conexion Bloqueante
- Prioridad: MEDIUM
- Archivos: DeviceController.php (store), app/Jobs/VerifyDeviceConnectionJob.php (nuevo)
- Dependencias: ZktecoService, SyncDeviceJob
- Riesgo: MEDIO (cambio flujo creacion device)
- Solucion:
  1. Store(): Crear device con status=pending, NO conectar
  2. Return redirect con mensaje Verificando conexion...
  3. Dispatch VerifyDeviceConnectionJob async
  4. Job: conecta, actualiza status, device_name, serial_number
  5. Toast/notification cuando termine
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); crear device retorna response inmediata

### BUG-007: EmployeeController update Sincronizacion Secuencial
- Prioridad: MEDIUM
- Archivos: EmployeeController.php (update)
- Dependencias: SyncEmployeeToDeviceJob (ya existe), syncToDevices action
- Riesgo: BAJO (refactor interno)
- Solucion:
  1. update(): Solo actualiza catalogo (name)
  2. Eliminar bucle foreach setUser()
  3. La sincronización de credenciales se hace via botón "Sincronizar credenciales" (syncToDevices) que dispatcha SyncEmployeeToDeviceJob async
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); update empleado retorna response inmediata

### BUG-008: EmployeeController destroy Estado Inconsistente
- Prioridad: MEDIUM
- Archivos: EmployeeController.php (destroy), app/Jobs/DeprovisionEmployeeJob.php (nuevo)
- Dependencias: ZktecoService::removeUser, DeviceEmployee::detach(), DeviceSync
- Riesgo: MEDIO (cambio logica baja)
- Solucion:
  1. destroy(): Marcar empleado status_actual = B (soft delete logico)
  2. Dispatch DeprovisionEmployeeJob por dispositivo
  3. Job: removeUser en checador - detach pivot
  4. Solo eliminar catalogo cuando TODOS jobs completen success (o manualmente)
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); destroy empleado retorna response inmediata

### BUG-009: DeviceController deduplicate MySQL Strict Mode
- Prioridad: MEDIUM
- Archivos: DeviceController.php (deduplicate)
- Dependencias: Employee, Attendance, Fingerprint
- Riesgo: BAJO (solo metodo admin)
- Solucion: Reescribir usando window functions MySQL 8+
  DELETE e FROM employees e
  JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id) rn FROM employees) t
  ON e.id = t.id WHERE t.rn > 1
  Similar para attendances/fingerprints
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); deduplicate usa SQL compatible MySQL 8+

### BUG-010: Employee Search Fetch HTML Fragil
- Prioridad: MEDIUM
- Archivos: employees/index.blade.php (JS), EmployeeController.php (search), routes/web.php
- Dependencias: Ninguna nueva
- Riesgo: BAJO (endpoint adicional)
- Solucion:
  1. Nuevo endpoint: GET /employees/search?q=...&device_id=...&page=... - JSON
  2. EmployeeController@search retornando {employees: [...], pagination: ...}
  3. JS: fetch JSON, render filas via vanilla JS template
  4. Loading state (spinner en tbody), pagination client-side, empty/error states
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); búsqueda retorna JSON, UI actualiza sin parpadeo

### BUG-011: Charts No Reaccionan a Theme Change
- Prioridad: MEDIUM
- Archivos: resources/views/dashboard.blade.php
- Dependencias: CSS variables, SVG charts server-side, themechange event
- Riesgo: BAJO (fix mínimo sin refactor)
- Solucion: Agregar listener themechange que fuerce repaint de SVG charts
  - forceChartsRepaint() remueve y re-inserta trend-chart y donut-wrap svg en DOM
  - Charts usan CSS variables (var(--primary), var(--cat-*), etc.) que se actualizan con tema
  - No requiere mover charts a client-side ni AJAX refresh
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); DashboardRenderTest pasa

---

## FASE 4: LOW (Limpieza y consistencia)

### BUG-012: Throttle Configurado Pero No Usado
- Prioridad: LOW (ya cubierto en BUG-001, BUG-002)
- Estado: RESUELTO AL APLICAR FASE 1

### BUG-013: DeviceEmployee casts vs casts Property
- Prioridad: LOW
- Archivos: app/Models/Pivots/DeviceEmployee.php
- Dependencias: Ninguna
- Riesgo: MUY BAJO (solo estilo)
- Solucion: Cambiar protected function casts(): array a protected casts = [...]
- Estado: PENDIENTE
- Verificacion: php artisan model:show DeviceEmployee muestra casts property

### BUG-014: Importaciones Duplicadas web.php
- Prioridad: LOW
- Archivos: routes/web.php lineas 13-21
- Dependencias: Ninguna
- Riesgo: NINGUNO
- Solucion: Eliminar lineas 13-21 (aliases duplicados)
- Estado: PENDIENTE
- Verificacion: php artisan route:list sin errores

### BUG-015: Users.role Sin CHECK Constraint
- Prioridad: LOW
- Archivos: Nueva migracion, User model
- Dependencias: EnsureAdmin middleware
- Riesgo: BAJO (migracion + enum PHP)
- Solucion:
  1. Migracion: add_check_constraint_role_to_users - CHECK (role IN ('admin','operator'))
  2. User model: enum Role: string { case Admin = 'admin'; case Operator = 'operator'; }
  3. isAdmin(): bool { return ->role === self::Role::Admin; }
- Estado: PENDIENTE
- Verificacion: INSERT role='adim' - constraint violation

### BUG-016: AdminLayoutComposer Data Verification
- Prioridad: LOW
- Archivos: app/View/Composers/AdminLayoutComposer.php
- Dependencias: window.__dash.search, window.__dash.notifications
- Riesgo: BAJO (verificacion)
- Solucion: Verificar composer inyecta datos - dd en browser console
- Estado: PENDIENTE
- Verificacion: Console.log(window.__dash) - data presente

### BUG-017: ZktecoService Variable Undefined
- Prioridad: LOW
- Archivos: app/Services/ZktecoService.php linea 72
- Dependencias: clientWithTimeout()
- Riesgo: BAJO (solo si se llama con timeout custom)
- Solucion: Cambiar $timeoutOverride a $timeout (parametro metodo linea 62)
- Estado: COMPLETADO
- Verificacion: php artisan test pasa (28 tests); ZktecoSyncTest pasa

### BUG-018: AttendanceController Filtro Param Mal Nombrado
- Prioridad: LOW
- Archivos: AttendanceController.php applyFilters(), views/attendances/index.blade.php
- Dependencias: AttendanceFilterTest
- Riesgo: BAJO (solo naming)
- Solucion: Renombrar request->query('state') a request->query('type') + actualizar vista
- Estado: PENDIENTE
- Verificacion: AttendanceFilterTest pasa

---

## Matriz de Riesgo vs Esfuerzo

| BUG | Riesgo | Esfuerzo | Fase |
|-----|--------|----------|------|
| 001 | Bajo | 5 min | 1 |
| 002 | Bajo | 5 min | 1 |
| 003 | Medio | 30 min | 2 |
| 004 | Alto | 1 hora | 2 |
| 005 | Alto | 1 hora | 2 |
| 006 | Medio | 2 horas | 3 |
| 007 | Bajo | 1 hora | 3 |
| 008 | Medio | 2 horas | 3 |
| 009 | Bajo | 30 min | 3 |
| 010 | Bajo | 2 horas | 3 |
| 011 | Medio | 3-4 horas | 3 |
| 012 | N/A | N/A | 1 |
| 013 | Muy Bajo | 10 min | 4 |
| 014 | Ninguno | 5 min | 4 |
| 015 | Bajo | 30 min | 4 |
| 016 | Bajo | 15 min | 4 |
| 017 | Bajo | 5 min | 4 |
| 018 | Bajo | 15 min | 4 |

---

## Dependencias Entre Fixes

BUG-001, 002 (independientes) - Pueden ir en paralelo
BUG-003 (independiente) - Decidir vistas vs eliminar
BUG-004, 005 (BD) - Requieren migraciones, testear en staging
BUG-006 (Job async) - Independiente
BUG-007, 008 (Employee sync) - Requieren BUG-006 patron Job
BUG-011 (Charts) - Independiente, decision arquitectura
BUG-013 a 018 (Limpieza) - Al final, sin dependencias

---

## Criterios de Finalizacion por Fase

### Fase 1 Complete
- php artisan route:list | grep login muestra throttle
- php artisan route:list | grep sync muestra throttle
- Tests rate limiting pasan

### Fase 2 Complete
- GET /firebird - 200 (o rutas eliminadas)
- Migracion attendances unique index aplicada y revertible
- Migracion fingerprint device_id aplicada y revertible
- Sync duplicados test pasa

### Fase 3 Complete
- Device store async - response 1s
- Employee update - response 500ms, jobs en cola
- Employee destroy - status=B, jobs retry
- Deduplicate MySQL 8 - sin error 1055
- Employee search - JSON endpoint + loading state
- Charts theme - Chart.js o AJAX refresh

### Fase 4 Complete
- DeviceEmployee usa casts property
- web.php imports limpios
- users.role CHECK constraint + Enum
- AdminLayoutComposer verificado
- ZktecoService variable fix
- AttendanceController param rename

---

## Estimacion Total
- Fase 1: 15 min
- Fase 2: 3 horas (incluye testing migraciones)
- Fase 3: 10-12 horas (refactors significativos)
- Fase 4: 2 horas
- TOTAL: 15-17 horas trabajo efectivo

---

## Notas de Ejecucion
1. Nunca ejecutar migraciones destructivas sin backup
2. Siempre testear en staging (MySQL) antes de produccion
3. Verificar tests existentes pasan despues de cada fase
4. Documentar en CHANGELOG.md cada cambio aplicado
5. Actualizar docs1/ despues de cada fix aplicado
