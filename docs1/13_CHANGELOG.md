# 13_CHANGELOG.md — Historial de Cambios

## Formato
CHANGE-XXX | Fecha | BUG Relacionado | Archivos Modificados | Cambio | Motivo | Impacto | Pruebas | Resultado

---

## Pendientes de Ejecucion (Post-Auditoria)

### CHANGE-001
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-001
**Archivos Modificados**: routes/web.php
**Cambio**: Agregar throttle:5,1 a Route::post('/login')
**Motivo**: Rate limiting login - prevencion brute force
**Impacto**: BAJO - solo agrega middleware
**Pruebas**: Test 6 requests/min -> 429; php artisan test pasa (28 tests)
**Resultado**: APLICADO

### CHANGE-002
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-002
**Archivos Modificados**: routes/web.php (lineas 120-132)
**Cambio**: Envolver grupo sync endpoints en throttle:10,1
**Motivo**: Rate limiting sync endpoints - prevencion DoS
**Impacto**: BAJO - solo agrega middleware
**Pruebas**: Test 11 requests rapidos -> 429; php artisan test pasa (28 tests)
**Resultado**: APLICADO

### CHANGE-003
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-003
**Archivos Modificados**: routes/web.php, resources/views/firebird/index.blade.php, resources/views/firebird/sync.blade.php, app/Http/Controllers/FirebirdController.php (nuevo)
**Cambio**: Crear vistas Firebird funcionales + controller
**Motivo**: Vistas faltantes causan error 500; módulo Firebird tiene backend completo
**Impacto**: MEDIO - restaura funcionalidad legada
**Pruebas**: GET /firebird -> 200; php artisan test pasa (28 tests)
**Resultado**: APLICADO

### CHANGE-004
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-004
**Archivos Modificados**: app/Services/ZktecoService.php (syncAttendances method)
**Cambio**: Corregir identity de insertOrIgnore para coincidir con índice único actual att_emp_rec_dev_type_unique (employee_id, recorded_at, device_id, attendance_type)
**Motivo**: Migración 2026_09_05_010214 ya cambió el índice único pero syncAttendances usaba el identity del índice anterior (device_id, user_id, state, recorded_at) → causaba duplicados en cada sync
**Impacto**: ALTO - fix crítico para integridad de datos
**Pruebas**: php artisan test pasa (28 tests); syncAttendances ahora usa identity correcto
**Resultado**: APLICADO

### CHANGE-005
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-005
**Archivos Modificados**: Nueva migracion (make_fingerprint_device_id_required), Fingerprint model
**Cambio**: Hacer device_id NOT NULL en MySQL + unique index correcto
**Motivo**: Filas legacy device_id=NULL violan unicidad MySQL
**Impacto**: ALTO - cambio esquema BD
**Pruebas**: Insert legacy duplicate -> handled correctamente
**Resultado**: PENDIENTE

### CHANGE-006
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-006
**Archivos Modificados**: DeviceController.php (store), app/Jobs/VerifyDeviceConnectionJob.php (nuevo)
**Cambio**: Device store async - crear device status=pending, dispatch Job verificar conexion
**Motivo**: Conexion ZKTeco bloqueante en request HTTP (hasta 60s)
**Impacto**: MEDIO - cambio flujo creacion
**Pruebas**: php artisan test pasa (28 tests); crear device retorna response inmediata
**Resultado**: APLICADO

### CHANGE-007
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-007
**Archivos Modificados**: EmployeeController.php (update)
**Cambio**: update() solo catalogo, sincronizacion credenciales via Job async (syncToDevices)
**Motivo**: Bucle foreach secuencial lento y sin tolerancia fallos
**Impacto**: MEDIO - refactor logica update
**Pruebas**: php artisan test pasa (28 tests); update empleado retorna response inmediata
**Resultado**: APLICADO

### CHANGE-008
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-008
**Archivos Modificados**: EmployeeController.php (destroy), app/Jobs/DeprovisionEmployeeJob.php (nuevo)
**Cambio**: destroy() soft delete (status_actual=B) + Jobs async por dispositivo
**Motivo**: Estado inconsistente si dispositivo falla
**Impacto**: MEDIO - cambio logica baja
**Pruebas**: php artisan test pasa (28 tests); destroy empleado retorna response inmediata
**Resultado**: APLICADO

### CHANGE-009
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-009
**Archivos Modificados**: DeviceController.php (deduplicate)
**Cambio**: Reescribir deduplicate usando window functions MySQL 8+ (ROW_NUMBER)
**Motivo**: Error 1055 ONLY_FULL_GROUP_BY en MySQL strict mode
**Impacto**: BAJO - solo metodo admin
**Pruebas**: php artisan test pasa (28 tests); deduplicate usa SQL compatible MySQL 8+
**Resultado**: APLICADO

### CHANGE-010
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-010
**Archivos Modificados**: EmployeeController.php (search), employees/index.blade.php (JS), routes/web.php
**Cambio**: Endpoint /employees/search JSON + JS fetch JSON + loading state
**Motivo**: Fetch HTML fragil, sin loading state
**Impacto**: BAJO - endpoint adicional
**Pruebas**: php artisan test pasa (28 tests); búsqueda retorna JSON, UI actualiza sin parpadeo
**Resultado**: APLICADO

### CHANGE-011
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-011
**Archivos Modificados**: resources/views/dashboard.blade.php
**Cambio**: Agregar listener themechange que fuerce repaint de SVG charts
**Motivo**: Charts SVG server-side no reaccionan a themechange
**Impacto**: BAJO - fix mínimo sin refactor de charts
**Pruebas**: php artisan test pasa (28 tests); DashboardRenderTest pasa
**Resultado**: APLICADO

### CHANGE-012
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-013
**Archivos Modificados**: app/Models/Pivots/DeviceEmployee.php
**Cambio**: Cambiar casts() method a  property
**Motivo**: Consistencia estilo Laravel 10 (AGENTS.md)
**Impacto**: MUY BAJO - solo estilo
**Pruebas**: php artisan model:show DeviceEmployee muestra casts property
**Resultado**: PENDIENTE

### CHANGE-013
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-014
**Archivos Modificados**: routes/web.php
**Cambio**: Eliminar lineas 13-21 (importaciones duplicadas Academia controllers)
**Motivo**: Limpieza codigo, 9 controladores importados 2 veces
**Impacto**: NINGUNO
**Pruebas**: php artisan route:list sin errores
**Resultado**: PENDIENTE

### CHANGE-014
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-015
**Archivos Modificados**: Nueva migracion (add_check_constraint_role_to_users), User model
**Cambio**: CHECK constraint role IN ('admin','operator') + Enum PHP Role
**Motivo**: Roles string sin validacion BD - typos posibles
**Impacto**: BAJO - migracion + enum
**Pruebas**: INSERT role='adim' -> constraint violation
**Resultado**: PENDIENTE

### CHANGE-015
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-016
**Archivos Modificados**: app/View/Composers/AdminLayoutComposer.php (verificacion)
**Cambio**: Verificar composer inyecta window.__dash.search y notifications
**Motivo**: Command Palette vacio, Notifications badge 0 si sin data
**Impacto**: BAJO - verificacion
**Pruebas**: Console.log(window.__dash) -> data presente
**Resultado**: PENDIENTE

### CHANGE-016
**Fecha**: 2026-09-05
**BUG Relacionado**: BUG-017
**Archivos Modificados**: app/Services/ZktecoService.php linea 72
**Cambio**: Cambiar $timeoutOverride a $timeout (parametro metodo)
**Motivo**: Variable undefined si clientWithTimeout llamado con timeout custom
**Impacto**: BAJO - solo si se usa timeout custom
**Pruebas**: php artisan test pasa (28 tests); ZktecoSyncTest pasa
**Resultado**: APLICADO

### CHANGE-017
**Fecha**: PENDIENTE
**BUG Relacionado**: BUG-018
**Archivos Modificados**: AttendanceController.php (applyFilters), views/attendances/index.blade.php
**Cambio**: Renombrar param 'state' a 'type' en filtro + actualizar vista
**Motivo**: Firmware usa 'type' para modo checado, 'state' constante=1
**Impacto**: BAJO - solo naming
**Pruebas**: AttendanceFilterTest pasa
**Resultado**: PENDIENTE

---

## Resumen Cambios por Fase

### Fase 1 (CRITICAL) - 2 cambios
- CHANGE-001: Login rate limit
- CHANGE-002: Sync endpoints rate limit

### Fase 2 (HIGH) - 3 cambios
- CHANGE-003: Firebird vistas/rutas
- CHANGE-004: Attendances unique index
- CHANGE-005: Fingerprint device_id index

### Fase 3 (MEDIUM) - 6 cambios
- CHANGE-006: Device store async
- CHANGE-007: Employee update async
- CHANGE-008: Employee destroy soft delete
- CHANGE-009: Deduplicate window functions
- CHANGE-010: Employee search API JSON
- CHANGE-011: Charts client-side + themechange

### Fase 4 (LOW) - 5 cambios
- CHANGE-012: DeviceEmployee casts property
- CHANGE-013: web.php imports cleanup
- CHANGE-014: Users role CHECK + Enum
- CHANGE-015: AdminLayoutComposer verification
- CHANGE-016: ZktecoService variable fix
- CHANGE-017: AttendanceController param rename

---

## Total: 17 Cambios Planificados

| Fase | Cambios | Estimacion |
|------|---------|------------|
| 1 (CRITICAL) | 2 | 15 min |
| 2 (HIGH) | 3 | 3 horas |
| 3 (MEDIUM) | 6 | 10-12 horas |
| 4 (LOW) | 6 | 2 horas |
| **TOTAL** | **17** | **15-17 horas** |

---

## Notas
- Cada CHANGE debe documentarse aqui al aplicarse
- Actualizar docs1/ correspondiente despues de cada change
- Tests deben pasar despues de cada change
- CHANGELOG.md es fuente de verdad para auditoria futura
