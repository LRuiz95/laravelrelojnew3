# 04_MODELS_DATABASE.md — Modelos y Base de Datos

## Resumen
- 15 modelos Eloquent + 1 Pivot personalizado
- 44 migraciones (evolucion 2014-2026)
- MySQL en produccion y testing (NO SQLite)
- Zona horaria: Dispositivos reportan local, MySQL en UTC (pendiente conversion)

---

## Modelos Core (Asistencia)

### User (app/Models/User.php)
- Fillable: name, email, password, role
- Hidden: password, remember_token
- Casts: email_verified_at datetime
- Metodos: isAdmin() -> role === 'admin'
- Tabla: users (migracion 2014_10_12_000000)

### Device (app/Models/Device.php)
- Fillable: name, ip, port, password, serial_number, device_name, status, description
- Casts: port integer, password encrypted
- Relaciones:
  - employees() BelongsToMany DeviceEmployee (pivot: device_uid, role, card_number, password, active, fingerprint_count)
  - attendances() HasMany
  - fingerprints() HasMany
  - syncs() HasMany
  - latestSync() HasOne (latestOfMany)
- Scopes/Estados: states() -> online/offline/unknown
- Tabla: devices (migracion 2024_01_01_000001)
- Indices: ip unique, serial_number unique (migracion 2026_08_20_000002)

### Employee (app/Models/Employee.php)
- Fillable: user_id, name, type (biometric|admin|teacher), numero_empleado, clave_profesor, departamento, cargo, contrato, status_actual, fecha_ingreso, id_campus, nivel, tarjeta_id
- Casts: fecha_ingreso date
- Touches: devices (actualiza updated_at al modificar enrolamientos)
- Relaciones:
  - devices() BelongsToMany DeviceEmployee
  - attendances() HasMany
  - fingerprints() HasMany
  - syncs() HasMany
- Scopes: biometric(), admin(), teacher()
- Accessors: typeLabel, statusActualLabel
- Tabla: employees (migracion 2024_01_01_000002 + migraciones 2026 centralizacion)
- Indices: user_id unique (global PIN), device_id index (legacy), academia fields

### Attendance (app/Models/Attendance.php)
- Fillable: device_id, employee_id, user_id, state, type, recorded_at
- Casts: state integer, type integer, recorded_at datetime
- Relaciones: device() BelongsTo, employee() BelongsTo
- Scopes: uniqueAttendances() - MAX id por employee_id+recorded_at
- Metodos de negocio:
  - punchStatus() - determina modo real (type prioritario, state fallback)
  - stateLabel() - etiqueta completa
  - shortStateLabel() - badge corto (Entrada/Salida/Entrada T.E./Salida T.E.)
  - stateColorClass() - clase CSS cat-green/blue/purple/lavender/gray
  - verificationTypeLabel() - metodo verificacion (huella/password/tarjeta/rostro)
- Estados validos (type): 0=Entrada, 1=Salida, 2=Salida descanso, 3=Regreso descanso, 4=Entrada T.E., 5=Salida T.E., 255=Desconocido
- Tabla: attendances (migracion 2024_01_01_000003)
- Indices: recorded_at, unique [device_id, user_id, recorded_at, state] (att_unique_punch)
- **PROBLEMA**: No hay indice unico compuesto [employee_id, recorded_at, device_id] para deduplicacion real

### Fingerprint (app/Models/Fingerprint.php)
- Fillable: employee_id, device_id, finger, template, template_hash
- Casts: finger integer
- Hidden: template (binario sensible)
- Relaciones: employee() BelongsTo, device() BelongsTo
- Tabla: fingerprints (migracion 2024_01_01_000004 + 2026_08_23_000002 add device_id)
- Indices: 
  - MySQL: unique [device_id, employee_id, finger] (plantillas por dispositivo)
  - SQLite: unique [employee_id, finger] (migracion 2026_08_20_000004 driver-specific)
- **PROBLEMA**: device_id nullable pero unique index requiere device_id en MySQL - filas legacy sin device_id pueden duplicar finger por empleado

### DeviceSync (app/Models/DeviceSync.php)
- Fillable: device_id, status, operation, stage, employee_id, started_at, finished_at, processed, total, created_count, updated_count, error_message
- Casts: started_at/finished_at datetime, employee_id/processed/total/created_count/updated_count integer
- Relaciones: device() BelongsTo, employee() BelongsTo, items() HasMany DeviceSyncItem
- Accessor: operationLabel (users/fingerprints/attendances/all/sync_full)
- Tabla: device_syncs (migracion 2024_01_01_000006 + 2026_08_20 add progress/stage)

### DeviceSyncItem (app/Models/DeviceSyncItem.php)
- Fillable: device_sync_id, fingerprint_id, credential_type, finger, status, attempts, message
- Tabla: device_sync_items (migracion 2026_08_24_120000)
- Indices: unique [device_sync_id, credential_type, finger], index [device_sync_id, status]

---

## Pivot Personalizado

### DeviceEmployee (app/Models/Pivots/DeviceEmployee.php)
- Tabla: device_employee (migracion 2026_08_23_000001)
- Fillable: device_id, employee_id, device_uid, role, card_number, password, active, fingerprint_count
- Casts: role/device_uid/fingerprint_count integer, active boolean, password encrypted
- Touches: employee
- Constantes: ROLES = [0=>Usuario, 13=>Supervisor, 14=>Admin]
- Metodos: roleLabel()
- Relaciones: employee() BelongsTo, device() BelongsTo
- Indices: 
  - unique [device_id, device_uid] (UID fisico unico por checador)
  - unique [device_id, card_number] (tarjeta unica por checador)
  - index employee_id

---

## Modelos Academia (12 modelos)

### Ciclo (app/Models/Academia/Ciclo.php)
- Fillable: inicial, final, periodo, descripcion, activo
- Relaciones: grupos(), horarios(), cursos(), alumnosKardex()
- Scopes: activo()

### Grupo (app/Models/Academia/Grupo.php)
- Fillable: codigo_grupo, grado, turno, nivel, inscritos, id_campus, inicial, final, periodo
- Relaciones: ciclo(), nivelRel(), turnoRel(), sede(), horarios(), alumnos(), alumnosGrupo()
- Scopes: porCiclo(), activo()

### Alumno (app/Models/Academia/Alumno.php)
- Fillable: numero_alumno, paterno, materno, nombre, fecha_nacimiento, genero, curp, email, telefono, direccion, id_campus, status
- Accessor: nombre_completo
- Relaciones: grupos(), kardex(), historial()

### Profesor (app/Models/Academia/Profesor.php)
- Fillable: clave_profesor, nombre, paterno, materno, email, telefono, id_campus, status
- Accessor: nombre_completo
- Relaciones: horarios(), grupos()

### HorarioDet (app/Models/Academia/HorarioDet.php)
- Fillable: codigo_grupo, clave_asignatura, clave_profesor, id_campus, dia, sesion, ubicacion, tipo_clase, inicial, final, periodo, activo
- Relaciones: grupo(), materia(), profesor(), sede(), sesionBase(), ciclo()

### Curso (app/Models/Academia/Curso.php)
- Fillable: id_plan, clave_asignatura, nombre_asignatura, nombre_corto, semestre, horas_teoria, horas_practica, creditos, tipo, activo
- Relaciones: plan(), materias(), cursosDet()

### CursoDet (app/Models/Academia/CursoDet.php)
- Fillable: id_plan, clave_asignatura, semestre, orden
- Relaciones: plan(), materia()

### Plan (app/Models/Academia/Plan.php)
- Fillable: id_plan, nombre_plan, nivel, activo
- Relaciones: cursos(), cursosDet(), materias()

### Nivel (app/Models/Academia/Nivel.php)
- Fillable: nivel, descripcion, activo
- Relaciones: grupos(), planes()

### Materia (app/Models/Academia/Materia.php)
- Fillable: clave_asignatura, nombre_asignatura, nombre_corto, semestre, horas_teoria, horas_practica, creditos, tipo, activo
- Relaciones: cursos(), horarios()

### MetodoEval (app/Models/Academia/MetodoEval.php)
- Fillable: id_eval, nombre_corto, descripcion, tipo_examen, es_final, activo

### Sede (app/Models/Academia/Sede.php)
- Fillable: id_campus, descripcion, activo
- Relaciones: grupos(), horarios()

### Turno (app/Models/Academia/Turno.php)
- Fillable: turno, descripcion, descripcion_corta, activo
- Relaciones: grupos()

### Contrato (app/Models/Academia/Contrato.php)
- Fillable: id_contrato, descripcion, activo

### SesionBase (app/Models/Academia/SesionBase.php)
- Fillable: sesion, hora_inicio, hora_fin
- Relaciones: horarios()

### AlumnoGrupo (app/Models/Academia/AlumnoGrupo.php)
- Fillable: numero_alumno, codigo_grupo, inicial, final, periodo
- Relaciones: alumno(), grupo()

### AlumnoKardex (app/Models/Academia/AlumnoKardex.php)
- Fillable: numero_alumno, clave_asignatura, calificacion, periodo, tipo_evaluacion, id_eval, inicial, final, periodo
- Relaciones: alumno(), materia(), metodoEval()

---

## Migraciones Clave (Orden Cronologico Relevante)

| Migracion | Descripcion |
|-----------|-------------|
| 2014_10_12_000000 | users |
| 2014_10_12_100000 | password_reset_tokens |
| 2019_08_19_000000 | failed_jobs |
| 2019_12_14_000001 | personal_access_tokens |
| 2024_01_01_000001 | devices |
| 2024_01_01_000002 | employees (legacy: device_id FK, uid, role, card_no, password, active) |
| 2024_01_01_000003 | attendances |
| 2024_01_01_000004 | fingerprints |
| 2024_01_01_000005 | users.role |
| 2024_01_01_000006 | device_syncs |
| 2024_01_01_000007 | jobs |
| 2024_01_01_000008 | encrypt sensitive data |
| 2026_08_20_000001 | device_syncs add progress |
| 2026_08_20_000002 | devices serial_number unique |
| 2026_08_20_000003 | device_syncs add stage |
| 2026_08_20_000004 | make_employees_and_fingerprints_global (centralizacion) |
| 2026_08_20_000005 | backfill_attendance_employee_ids |
| 2026_08_21_000001 | add_performance_indexes |
| 2026_08_22_021315 | revert_employee_user_id_unique |
| 2026_08_23_000001 | create_device_employee_table (pivot) |
| 2026_08_23_000002 | add_device_id_to_fingerprints |
| 2026_08_23_000003 | move_employee_data_to_central_catalog |
| 2026_08_23_000004 | drop_legacy_columns_from_employees |
| 2026_08_24_090807 | add_last_seen_at_to_devices_table |
| 2026_08_24_120000 | create_device_sync_items_table |
| 2026_09_05_* | Academia (18 migraciones: sedes, niveles, ciclos, grupos, alumnos, profesores, horarios, cursos, planes, materias, metodos, turnos, contratos, sesiones, kardex, FKs) |
| 2026_09_05_0133* | Firebird sync tables |

---

## Inconsistencias y Problemas Detectados

### 1. Indice Unico Faltante en Attendances
- **Esperado**: unique [employee_id, recorded_at, device_id] (docs/zkteco.md 3)
- **Actual**: unique [device_id, user_id, recorded_at, state] (att_unique_punch)
- **Riesgo**: Duplicados al sincronizar si mismo empleado checa dos veces mismo segundo en mismo dispositivo

### 2. Fingerprint device_id Nullable vs Unique Index
- MySQL: unique [device_id, employee_id, finger] - device_id NOT NULL en index
- SQLite: unique [employee_id, finger] - sin device_id
- Filas legacy (device_id=NULL) pueden violar unicidad en MySQL

### 3. Employee.user_id Unique Global
- Migracion 2026_08_22 revierte unique [device_id, user_id] a unique [user_id]
- Correcto: PIN global unico en catalogo central

### 4. Zona Horaria No Manejada
- Attendance::recorded_at guardada tal cual del dispositivo (hora local)
- MySQL en UTC
- No hay conversion en ZktecoService::syncAttendances() ni al mostrar

### 5. Casts Inconsistentes
- DeviceEmployee usa metodo casts() (estilo Laravel 11) vs propiedad  en otros modelos
- AGENTS.md indica usar propiedad  consistentemente

### 6. Firebird Models Sin Relaciones Completas
- FirebirdSync, FirebirdSyncItem existen pero no revisados en detalle

### 7. Indices Rendimiento
- attendances.recorded_at index existe
- employees.user_id unique existe
- devices.ip unique existe
- Falta index en device_employee.employee_id (existe)
- Falta index compuesto frecuente: attendances [device_id, recorded_at] para rangos de fecha
