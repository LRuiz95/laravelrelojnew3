# 02_ROUTES.md — Inventario de Rutas

## Resumen
- Total rutas web: ~65 rutas definidas en routes/web.php
- Rutas API: 1 (/api/user)
- Rutas Console: 1 (inspire)
- Todas las rutas web requieren autenticacion (middleware auth) excepto login/logout

## Rutas de Autenticacion

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | /login | login | AuthController@create | guest |
| POST | /login | login.store | AuthController@store | guest |
| POST | /logout | logout | AuthController@destroy | auth |

## Rutas Principales (Dashboard)

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | / | dashboard | DashboardController@index | auth |
| GET | /kpis/json | dashboard.kpisJson | DashboardController@kpisJson | auth |

## Rutas Academia (prefijo: /academia, name: academia.)

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | /academia | academia.dashboard | Academia\DashboardController@index | auth |
| GET|POST|PUT|DELETE | /academia/ciclos | academia.ciclos.* | Academia\CicloController | auth |
| POST | /academia/ciclos/{ciclo}/activo | academia.ciclos.activo | Academia\CicloController@setActivo | auth |
| GET | /academia/grupos | academia.grupos.index | Academia\GrupoController@index | auth |
| GET | /academia/grupos/{grupo} | academia.grupos.show | Academia\GrupoController@show | auth |
| GET | /academia/grupos/{grupo}/asistencia | academia.grupos.asistencia | Academia\GrupoController@asistencia | auth |
| POST | /academia/grupos/{grupo}/asistencia | academia.grupos.asistencia.guardar | Academia\GrupoController@guardarAsistencia | auth |
| GET | /academia/alumnos | academia.alumnos.index | Academia\AlumnoController@index | auth |
| GET | /academia/alumnos/{alumno} | academia.alumnos.show | Academia\AlumnoController@show | auth |
| GET | /academia/alumnos/{alumno}/kardex | academia.alumnos.kardex | Academia\AlumnoController@kardex | auth |
| GET | /academia/alumnos/{alumno}/historial | academia.alumnos.historial | Academia\AlumnoController@historial | auth |
| GET | /academia/profesores | academia.profesores.index | Academia\ProfesorController@index | auth |
| GET | /academia/profesores/{profesor} | academia.profesores.show | Academia\ProfesorController@show | auth |
| GET | /academia/profesores/{profesor}/horario | academia.profesores.horario | Academia\ProfesorController@horario | auth |
| GET | /academia/horarios/clase | academia.horarios.clase | Academia\HorarioController@clase | auth |
| GET | /academia/horarios/profesor | academia.horarios.profesor | Academia\HorarioController@profesor | auth |
| GET | /academia/horarios/aula | academia.horarios.aula | Academia\HorarioController@aula | auth |
| GET | /academia/horarios/base | academia.horarios.base | Academia\HorarioController@base | auth |
| GET | /academia/horarios/persona | academia.horarios.persona | Academia\HorarioController@persona | auth |
| GET | /academia/kardex | academia.kardex.index | Academia\KardexController@index | auth |
| GET | /academia/kardex/show | academia.kardex.show | Academia\KardexController@show | auth |
| GET | /academia/kardex/historial | academia.kardex.historial | Academia\KardexController@historial | auth |
| GET | /academia/kardex/print | academia.kardex.print | Academia\KardexController@print | auth |
| GET|POST|PUT|DELETE | /academia/cursos | academia.cursos.* | Academia\CursoController | auth |
| POST | /academia/cursos/{curso}/materia | academia.cursos.materia.add | Academia\CursoController@addMateria | auth |
| DELETE | /academia/cursos/{curso}/materia/{materia} | academia.cursos.materia.remove | Academia\CursoController@removeMateria | auth |
| GET|POST|PUT|DELETE | /academia/planes | academia.planes.* | Academia\PlanController | auth |

## Rutas API Academia (prefijo: /api/academia, name: api.academia.)

| METHOD | URI | NAME | CONTROLLER |
|--------|-----|------|------------|
| GET | /api/academia/grupos-por-ciclo | api.academia.grupos-por-ciclo | Academia\ApiController@gruposPorCiclo |
| GET | /api/academia/alumnos-por-grupo | api.academia.alumnos-por-grupo | Academia\ApiController@alumnosPorGrupo |
| GET | /api/academia/ciclos-disponibles | api.academia.ciclos-disponibles | Academia\ApiController@ciclosDisponibles |
| GET | /api/academia/planes-por-nivel | api.academia.planes-por-nivel | Academia\ApiController@planesPorNivel |
| GET | /api/academia/materias-por-plan | api.academia.materias-por-plan | Academia\ApiController@materiasPorPlan |
| GET | /api/academia/metodos-eval | api.academia.metodos-eval | Academia\ApiController@metodosEval |
| GET | /api/academia/niveles | api.academia.niveles | Academia\ApiController@niveles |
| GET | /api/academia/turnos | api.academia.turnos | Academia\ApiController@turnos |
| GET | /api/academia/sedes | api.academia.sedes | Academia\ApiController@sedes |
| GET | /api/academia/horario-base | api.academia.horario-base | Academia\ApiController@horarioBase |
| GET | /api/academia/grupo-detalle | api.academia.grupo-detalle | Academia\ApiController@grupoDetalle |

## Rutas Firebird (prefijo: /firebird, name: firebird.)

| METHOD | URI | NAME | CONTROLLER |
|--------|-----|------|------------|
| GET | /firebird | firebird.index | Closure (view firebird.index) |
| GET | /firebird/sync/{sync} | firebird.sync | Closure (view firebird.sync) |

NOTA: Las vistas firebird.index y firebird.sync NO existen en resources/views/firebird/

## Rutas Dispositivos (prefijo: /devices, name: devices.)

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | /devices | devices.index | DeviceController@index | auth |
| GET | /devices/create | devices.create | DeviceController@create | auth, admin |
| POST | /devices | devices.store | DeviceController@store | auth, admin |
| GET | /devices/{device} | devices.show | DeviceController@show | auth |
| GET | /devices/{device}/sync-status | devices.sync-status | DeviceController@syncStatus | auth |
| GET | /devices/{device}/refresh-data | devices.refresh-data | DeviceController@refreshData | auth |
| GET | /devices/{device}/progress | devices.progress | DeviceController@progress | auth |
| GET | /devices/{device}/edit | devices.edit | DeviceController@edit | auth, admin |
| PUT | /devices/{device} | devices.update | DeviceController@update | auth, admin |
| DELETE | /devices/{device} | devices.destroy | DeviceController@destroy | auth, admin |
| POST | /devices/{device}/check-status | devices.check-status | DeviceController@checkStatus | auth |
| POST | /devices/deduplicate | devices.deduplicate | DeviceController@deduplicate | auth, admin |
| POST | /devices/{device}/sync-users | devices.sync-users | DeviceController@syncUsers | auth, admin |
| POST | /devices/{device}/sync-fingerprints | devices.sync-fingerprints | DeviceController@syncFingerprints | auth, admin |
| POST | /devices/{device}/sync-attendances | devices.sync-attendances | DeviceController@syncAttendances | auth, admin |
| POST | /devices/{device}/sync-all | devices.sync-all | DeviceController@syncAll | auth, admin |
| POST | /devices/{device}/employees/{employee}/upload-fingerprints | devices.employees.upload-fingerprints | EmployeeController@uploadFingerprintsOnDevice | auth, admin |
| DELETE | /devices/{device}/employees/{employee} | devices.employees.remove | EmployeeController@removeFromDevice | auth, admin |
| POST | /devices/{device}/set-time | devices.set-time | DeviceController@setTime | auth, admin |
| POST | /devices/{device}/sync-now | devices.sync-now | DeviceController@syncNow | auth, admin |
| POST | /devices/{device}/clear-attendance | devices.clear-attendance | DeviceController@clearAttendance | auth, admin |
| POST | /devices/{device}/restore | devices.restore | DeviceController@restore | auth, admin |

## Rutas Empleados (prefijo: /employees, name: employees.)

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | /employees | employees.index | EmployeeController@index | auth |
| GET | /employees/create | employees.create | EmployeeController@create | auth, admin |
| GET | /employees/{employee}/edit | employees.edit | EmployeeController@edit | auth, admin |
| POST | /employees | employees.store | EmployeeController@store | auth, admin |
| PUT | /employees/{employee} | employees.update | EmployeeController@update | auth, admin |
| POST | /employees/{employee}/upload-fingerprints | employees.upload-fingerprints | EmployeeController@uploadFingerprints | auth, admin |
| POST | /employees/{employee}/assign-fingerprint | employees.assign-fingerprint | EmployeeController@assignFingerprint | auth, admin |
| POST | /employees/{employee}/fingerprints/{fingerprint}/copy | employees.copy-fingerprint | EmployeeController@copyFingerprint | auth, admin |
| DELETE | /employees/{employee}/fingerprints/{fingerprint} | employees.delete-fingerprint | EmployeeController@deleteFingerprint | auth, admin |
| POST | /employees/{employee}/card | employees.update-card | EmployeeController@updateCard | auth, admin |
| POST | /employees/{employee}/enroll-device | employees.enroll-device | EmployeeController@enrollOnDevice | auth, admin |
| POST | /employees/{employee}/sync-devices | employees.sync-devices | EmployeeController@syncToDevices | auth, admin |
| DELETE | /employees/{employee} | employees.destroy | EmployeeController@destroy | auth, admin |

## Rutas Huellas

| METHOD | URI | NAME | CONTROLLER |
|--------|-----|------|------------|
| GET | /fingerprints | fingerprints.index | EmployeeController@fingerprints |

## Rutas Asistencias

| METHOD | URI | NAME | CONTROLLER |
|--------|-----|------|------------|
| GET | /attendances | attendances.index | AttendanceController@index |
| GET | /attendances/export | attendances.export | AttendanceController@export |
| GET | /attendances/print | attendances.print | AttendanceController@print |

## Rutas Operaciones (prefijo: /sync-queue, name: operations.)

| METHOD | URI | NAME | CONTROLLER | MIDDLEWARE |
|--------|-----|------|------------|------------|
| GET | /sync-queue | operations.queue | OperationsController@queue | auth, admin |
| GET | /sync-queue/data | operations.queue.data | OperationsController@queueData | auth, admin |
| POST | /sync-queue/{sync}/cancel | operations.cancel | OperationsController@cancel | auth, admin |
| POST | /sync-queue/{sync}/retry | operations.retry | OperationsController@retry | auth, admin |
| DELETE | /sync-queue/{sync} | operations.delete | OperationsController@delete | auth, admin |
| GET | /notifications | operations.notifications | OperationsController@notifications | auth |

## Rutas API (routes/api.php)

| METHOD | URI | MIDDLEWARE |
|--------|-----|------------|
| GET | /api/user | auth:sanctum |

## Rutas Console (routes/console.php)

| COMMAND | DESCRIPTION |
|---------|-------------|
| inspire | Display an inspiring quote |

## Rutas Duplicadas / Sospechosas

1. **Importaciones duplicadas en web.php** (lineas 3-21): 
   - Academia\CicloController importado 2 veces (linea 9 y 13)
   - Academia\CursoController importado 2 veces (linea 10 y 14)
   - Academia\PlanController importado 2 veces (linea 11 y 15)
   - Academia\GrupoController importado 2 veces (linea 4 y 16)
   - Academia\AlumnoController importado 2 veces (linea 5 y 17)
   - Academia\ProfesorController importado 2 veces (linea 6 y 18)
   - Academia\HorarioController importado 2 veces (linea 7 y 19)
   - Academia\KardexController importado 2 veces (linea 8 y 20)
   - Academia\ApiController importado 2 veces (linea 12 y 21)

2. **Vistas Firebird faltantes**: Rutas /firebird y /firebird/sync/{sync} referencian vistas que no existen

3. **Rutas sin consumidor claro**: 
   - /devices/{device}/refresh-data - ¿usada por JS?
   - /devices/{device}/progress - ¿usada por JS?
   - /devices/{device}/sync-status - ¿usada por JS?

## Rutas con Nombres Inconsistentes

- employees.upload-fingerprints vs devices.employees.upload-fingerprints - misma accion, distinta ruta
- employees.sync-devices vs devices.sync-users - nomenclatura mixta

## Rutas Rotas / Pendientes de Verificar

1. **Firebird views** - No existen resources/views/firebird/index.blade.php ni sync.blade.php
2. **Rate limiting** - No hay throttle en /login ni en endpoints de sync
3. **CSRF en AJAX** - Endpoints api/academia/* no tienen CSRF explicito (usan session)
