# 03_CONTROLLERS.md — Documentación de Controladores

## Resumen
16 controladores totales: 6 core (asistencia) + 8 academia + 1 Auth + 1 Operations

---

## AuthController (app/Http/Controllers/AuthController.php)
**Lineas**: 45 | **Metodos**: 3 | **Rutas**: 3

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| create() | GET /login | Vista login |
| store() | POST /login | Validacion email/password, Auth::attempt, redirect intended |
| destroy() | POST /logout | Auth::logout, session invalidate, regenerate token |

**Dependencias**: Auth facade, Request, RedirectResponse, View
**Validacion**: Inline en store() (email required, password required)
**Seguridad**: Session regeneration en login, CSRF en logout

---

## DashboardController (app/Http/Controllers/DashboardController.php)
**Lineas**: 287 | **Metodos**: 1 publico + 8 privados | **Rutas**: 2

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| index() | GET / | Vista dashboard con KPIs, trend, pipeline, donut, recent |
| kpisJson() | GET /kpis/json | JSON para Heartbeat polling (5 KPIs) |
| kpis() | private | 6 KPIs: chequeos hoy, empleados hoy, entradas, salidas, dispositivos online |
| pipeline() | private | 6 steps: dispositivos, sin asignar, activos, huellas, offline, chequeos hoy |
| donut() | private | Distribucion por type (modo checado) solo hoy |
| trend() | private | Despacha a hourlyToday/dailyTrend/monthlyTrend segun rango |
| todayInfo() | private | Banner contexto: checks hoy, empleados, primera/ultima checada |

**Dependencias**: Attendance, Device, Employee, Fingerprint, Request, JsonResponse, View
**Queries**: Multiples queries separadas (potencial optimizacion con subqueries)
**JS Consumer**: Heartbeat (app.js) cada 20s llama /kpis/json

---

## DeviceController (app/Http/Controllers/DeviceController.php)
**Lineas**: 555 | **Metodos**: 23 | **Rutas**: 23

### CRUD Basico
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| index() | GET /devices | Lista paginada con stats + sparklines semanales |
| create() | GET /devices/create | Vista crear |
| store() | POST /devices | Valida, conecta ZKTeco, crea/actualiza por serial_number |
| show() | GET /devices/{device} | Detalle con empleados, asistencias, syncs, huellas, sparklines |
| edit() | GET /devices/{device}/edit | Vista editar |
| update() | PUT /devices/{device} | Valida, actualiza, verifica status ZKTeco |
| destroy() | DELETE /devices/{device} | Elimina dispositivo |

### Acciones de Sync (todas POST, middleware admin)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| checkStatus() | POST /devices/{device}/check-status | Ping ZKTeco, actualiza status |
| syncUsers() | POST /devices/{device}/sync-users | Encola SyncDeviceJob (users) |
| syncAttendances() | POST /devices/{device}/sync-attendances | Encola SyncDeviceJob (attendances) |
| syncFingerprints() | POST /devices/{device}/sync-fingerprints | Encola SyncDeviceJob (fingerprints, opcional employee_id, batch) |
| syncAll() | POST /devices/{device}/sync-all | Encola SyncDeviceJob (all) |
| setTime() | POST /devices/{device}/set-time | Sincroniza hora dispositivo |
| syncNow() | POST /devices/{device}/sync-now | Alias setTime con now() |
| clearAttendance() | POST /devices/{device}/clear-attendance | Limpia log dispositivo (despues de sync confirmado) |
| restore() | POST /devices/{device}/restore | Habilita dispositivo (enableDevice) |
| deduplicate() | POST /devices/deduplicate | Limpia empleados/asistencias duplicados (transaccion) |

### Endpoints JSON (para vista show)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| refreshData() | GET /devices/{device}/refresh-data | Counts + empleados + asistencias + syncs + huellas |
| progress() | GET /devices/{device}/progress | Progreso sync actual (status, stage, processed, total) |
| syncStatus() | GET /devices/{device}/sync-status | Status sync detallado |

**Dependencias**: Device, Attendance, Employee, Fingerprint, DeviceSync, ZktecoService, SyncDeviceJob, DB, Redirect, JsonResponse, View
**Validacion**: Inline en store/update (name, ip, port, password, description, serial_number unique)
**Jobs**: SyncDeviceJob despachado con WithoutOverlapping por device
**Vista show**: NO consulta ZKTeco en render (evita bloqueo 60s), usa datos locales

---

## EmployeeController (app/Http/Controllers/EmployeeController.php)
**Lineas**: 510 | **Metodos**: 20 | **Rutas**: 14

### CRUD Basico
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| index() | GET /employees | Lista paginada con filtro device_id y busqueda q |
| create() | GET /employees/create | Vista crear con lista dispositivos |
| edit() | GET /employees/{employee}/edit | Vista editar con enrolamientos, huellas disponibles, sync devices |
| store() | POST /employees | Crea en catalogo + enrola en dispositivo (ZktecoService::setUser) |
| update() | PUT /employees/{employee} | Actualiza nombre (catalogo) + propaga rol/password/tarjeta a TODOS sus dispositivos |
| destroy() | DELETE /employees/{employee} | Da de baja en TODOS dispositivos, elimina catalogo si sin enrolamientos |

### Huellas
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| fingerprints() | GET /fingerprints | Lista empleados con huellas, filtros device_id y q |
| uploadFingerprints() | POST /employees/{employee}/upload-fingerprints | Sube huellas a primer enrolamiento |
| uploadFingerprintsOnDevice() | POST /devices/{device}/employees/{employee}/upload-fingerprints | Sube huellas a dispositivo especifico |
| assignFingerprint() | POST /employees/{employee}/assign-fingerprint | Asigna huella existente a empleado (actualiza pivot fingerprint_count) |
| copyFingerprint() | POST /employees/{employee}/fingerprints/{fingerprint}/copy | Copia huella a otro dispositivo (requiere enrolamiento) |
| deleteFingerprint() | DELETE /employees/{employee}/fingerprints/{fingerprint} | Elimina huella (dispositivo + pivot count) |

### Credenciales y Enrolamiento
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| updateCard() | POST /employees/{employee}/card | Actualiza tarjeta RFID en dispositivo (valida unicidad por device) |
| enrollOnDevice() | POST /employees/{employee}/enroll-device | Enrola empleado en nuevo dispositivo (copia credenciales) |
| syncToDevices() | POST /employees/{employee}/sync-devices | Encola SyncEmployeeToDeviceJob para dispositivos seleccionados |

**Dependencias**: Employee, Device, Fingerprint, DeviceSync, ZktecoService, SyncEmployeeToDeviceJob, DB, Redirect, View
**Validacion**: Inline en cada metodo (name max:24, user_id digits, password digits, role in:0,13,14, card_number regex)
**Logica Critica**: 
- update() propaga a TODOS dispositivos (bucle foreach con ZktecoService)
- destroy() elimina de TODOS dispositivos, catalogo solo si queda sin enrolamientos
- enrollOnDevice() valida tarjeta no duplicada en dispositivo destino

---

## AttendanceController (app/Http/Controllers/AttendanceController.php)
**Lineas**: 94 | **Metodos**: 4 | **Rutas**: 3

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| index() | GET /attendances | Lista con filtros (device_id, state/type, from, to). Sin filtros usa uniqueAttendances() |
| export() | GET /attendances/export | CSV stream con chunk(500) |
| print() | GET /attendances/print | Vista print con mismos filtros |
| applyFilters() | private | Filtra por device_id, type (NO state), from, to |

**Dependencias**: Attendance, Device, Request, View, StreamedResponse
**Bug Conocido**: Filtro state en formulario mapea a columna type (ver AttendanceFilterTest)
**Optimizacion**: Sin filtros usa scope uniqueAttendances (MAX id por employee+fecha)

---

## OperationsController (app/Http/Controllers/OperationsController.php)
**Lineas**: ~80 | **Metodos**: 6 | **Rutas**: 6

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| queue() | GET /sync-queue | Vista cola sincronizacion (admin) |
| queueData() | GET /sync-queue/data | JSON para tabla cola (admin) |
| cancel() | POST /sync-queue/{sync}/cancel | Cancela sync (status=cancelled) |
| retry() | POST /sync-queue/{sync}/retry | Reintenta sync fallida |
| delete() | DELETE /sync-queue/{sync} | Elimina registro sync |
| notifications() | GET /notifications | Vista notificaciones |

**Dependencias**: DeviceSync, Device, Employee, OperationsController, View, JsonResponse, Redirect

---

## Academia Controllers (8 controladores)

### Academia\DashboardController
- index() - Dashboard academico con stats

### Academia\CicloController
- CRUD completo + setActivo()

### Academia\GrupoController
- index, show, asistencia (GET/POST guardar asistencia)

### Academia\AlumnoController
- index, show, kardex, historial

### Academia\ProfesorController
- index, show, horario

### Academia\HorarioController
- 5 vistas: clase, profesor, aula, base, persona

### Academia\KardexController
- index, show, historial, print

### Academia\CursoController
- CRUD + addMateria/removeMateria

### Academia\PlanController
- CRUD completo

### Academia\ApiController (10 endpoints AJAX)
- gruposPorCiclo, alumnosPorGrupo, ciclosDisponibles, planesPorNivel, materiasPorPlan, metodosEval, niveles, turnos, sedes, horarioBase, grupoDetalle
- Inyecta CicloActualService y HorarioResolver

---

## Patrones y Problemas Identificados

### Buenas Practicas
- Controllers delgados, delegan a Services/Jobs
- Tipado estricto (declare(strict_types=1), return types)
- Validacion inline con arrays de reglas
- Uso de Policies NO implementado (solo middleware admin)
- Jobs con WithoutOverlapping para evitar concurrencia

### Problemas Detectados

1. **Sin Form Requests**: Toda validacion inline en controladores (violacion AGENTS.md)
2. **Sin Policies**: Autorizacion solo via middleware admin (no granular)
3. **N+1 Potential**: 
   - DeviceController::show() carga employees, attendances, fingerprints, syncs sin with() en algunos casos
   - EmployeeController::edit() carga fingerprints con with() pero devices sin with()
4. **Duplicacion Logica**: 
   - uploadFingerprints() en EmployeeController Y DeviceController
   - syncUsers/syncAttendances/syncFingerprints duplican logica de encolado
5. **EmployeeController::update()**: Bucle foreach en TODOS dispositivos - lento si muchos dispositivos
6. **EmployeeController::destroy()**: Si un dispositivo falla, empleado queda con enrolamientos activos
7. **DeviceController::store()**: Conecta a ZKTeco en request HTTP (bloqueante) - deberia ser job
8. **Validacion serial_number**: unique en store pero no en update (tiene ignore ID)
9. **OperationsController**: No existe en la lista de controladores leidos - verificar

### Referencias Rotas a Verificar
- EmployeeController@uploadFingerprintsOnDevice ruta: devices.employees.upload-fingerprints - OK
- EmployeeController@removeFromDevice ruta: devices.employees.remove - OK
- Vistas referenciadas: todas existen en resources/views/
