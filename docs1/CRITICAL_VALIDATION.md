# CRITICAL_VALIDATION.md — Validación de Bugs CRITICAL/HIGH (BUG-001 a BUG-005)

Fecha: 2026-09-05
Auditor: Senior Laravel Architect

---

## BUG-001 — Login / Rate Limiting

### Título
Login sin rate limiting - vulnerable a brute force

### Severidad documentada
CRITICAL

### Severidad real
CRITICAL

### Archivo
`routes/web.php` línea 31

### Código involucrado
```php
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');
```

### Qué afirma la auditoría
POST /login sin middleware throttle, vulnerable a brute force ilimitado y enumeración de emails.

### Qué se encontró realmente
**CONFIRMADO**. La ruta `/login` (POST) solo tiene middleware `guest`. No hay `throttle` aplicado. El controlador `AuthController@store` hace validación inline y `Auth::attempt()` pero no hay protección a nivel de ruta ni de controlador contra intentos repetidos.

### ¿Se reproduce?
Sí. Cualquier atacante puede hacer requests ilimitados a `/login` sin recibir 429.

### Prueba utilizada
Inspección de `routes/web.php` línea 31. Verificado que no hay `->middleware('throttle:5,1')` ni similar.

### Causa raíz
Oversight en definición de rutas: el middleware `throttle` está registrado en `Kernel.php` (alias `throttle` → `ThrottleRequests`) pero no se aplica a la ruta de login.

### Impacto real
- Brute force ilimitado contra cuentas admin
- Enumeración de emails válidos (respuesta distinta para email existente vs inexistente)
- DoS potencial saturando workers PHP

### Dependencias
Ninguna. Fix independiente.

### Riesgo de reparación
**BAJO**. Solo agregar middleware a una ruta. No cambia lógica de negocio.

### Solución recomendada
```php
Route::post('/login', [AuthController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login.store');
```
Límite: 5 intentos por minuto por IP.

---

## BUG-002 — Sync / Rate Limiting / DoS

### Título
Endpoints sync POST sin rate limiting

### Severidad documentada
CRITICAL

### Severidad real
CRITICAL

### Archivo
`routes/web.php` líneas 120-132

### Código involucrado
```php
Route::middleware('admin')->group(function () {
    Route::post('/deduplicate', [DeviceController::class, 'deduplicate'])->name('deduplicate');
    Route::post('/{device}/sync-users', [DeviceController::class, 'syncUsers'])->name('sync-users');
    Route::post('/{device}/sync-fingerprints', [DeviceController::class, 'syncFingerprints'])->name('sync-fingerprints');
    Route::post('/{device}/sync-attendances', [DeviceController::class, 'syncAttendances'])->name('sync-attendances');
    Route::post('/{device}/sync-all', [DeviceController::class, 'syncAll'])->name('sync-all');
    Route::post('/{device}/employees/{employee}/upload-fingerprints', [EmployeeController::class, 'uploadFingerprintsOnDevice'])->name('employees.upload-fingerprints');
    Route::delete('/{device}/employees/{employee}', [EmployeeController::class, 'removeFromDevice'])->name('employees.remove');
    Route::post('/{device}/set-time', [DeviceController::class, 'setTime'])->name('set-time');
    Route::post('/{device}/sync-now', [DeviceController::class, 'syncNow'])->name('sync-now');
    Route::post('/{device}/clear-attendance', [DeviceController::class, 'clearAttendance'])->name('clear-attendance');
    Route::post('/{device}/restore', [DeviceController::class, 'restore'])->name('restore');
});
```

### Qué afirma la auditoría
Endpoints de sincronización sin throttle, podrían saturar cola de jobs, checadores y agotar recursos.

### Qué se encontró realmente
**CONFIRMADO**. Los 11 endpoints POST de sincronización solo tienen middleware `admin`. No hay `throttle` en ningún endpoint de sync. Los controladores despachan `SyncDeviceJob` o `SyncEmployeeToDeviceJob` directamente sin ningún rate limiting a nivel HTTP.

### ¿Se reproduce?
Sí. Un usuario admin autenticado (o atacante con sesión robada) puede hacer POST repetidos a `/devices/{id}/sync-attendances` y cada request encola un job nuevo.

### Prueba utilizada
Inspección de `routes/web.php` líneas 120-132. Verificado que el grupo solo tiene `->middleware('admin')` sin `throttle`.

### Causa raíz
Oversight en definición de rutas: se aplicó `admin` para autorización pero no `throttle` para rate limiting.

### Impacto real
- DoS en cola de jobs (database queue) - miles de jobs en segundos
- Saturación de checadores ZKTeco (conexiones TCP concurrentes)
- Agotamiento de workers PHP y conexiones BD
- Posible bloqueo de hardware (checadores se cuelgan con muchas conexiones)

### Dependencias
Ninguna. Fix independiente.

### Riesgo de reparación
**BAJO**. Agregar middleware a grupo de rutas existente.

### Solución recomendada
```php
Route::middleware(['admin', 'throttle:10,1'])->group(function () {
    // ... sync endpoints
});
```
Límite: 10 requests por minuto por IP (suficiente para uso admin legítimo, bloquea automatización).

---

## BUG-003 — Vistas Firebird Faltantes

### Título
Vistas firebird.index y firebird.sync no existen

### Severidad documentada
HIGH

### Severidad real
HIGH

### Archivo
`routes/web.php` líneas 103-104

### Código involucrado
```php
Route::prefix('firebird')->name('firebird.')->group(function () {
    Route::get('/', fn () => view('firebird.index'))->name('index');
    Route::get('sync/{sync}', fn ($sync) => view('firebird.sync', ['sync' => \App\Models\FirebirdSync::findOrFail($sync)]))->name('sync');
});
```

### Qué afirma la auditoría
Rutas definidas pero vistas no creadas → Error 500 al acceder.

### Qué se encontró realmente
**CONFIRMADO**. 
- `resources/views/firebird/index.blade.php` - NO EXISTE
- `resources/views/firebird/sync.blade.php` - NO EXISTE
- Directorio `resources/views/firebird/` - NO EXISTE

### ¿Se reproduce?
Sí. Acceder a `/firebird` o `/firebird/sync/{id}` lanza `InvalidArgumentException: View [firebird.index] not found.`

### Prueba utilizada
`glob('resources/views/firebird/*.blade.php')` → array vacío. Rutas referencian vistas inexistentes.

### Causa raíz
Módulo Firebird parcialmente implementado: rutas y modelos existen pero vistas no.

### Impacto real
- Error 500 en 2 rutas accesibles por admins
- Funcionalidad Firebird inutilizable desde UI

### Dependencias
- `App\Models\FirebirdSync` (existe)
- `App\Models\FirebirdSyncItem` (existe)
- `FirebirdReader`, `FirebirdSyncJob`, `SyncStrategies` (existen)

### Riesgo de reparación
**MEDIO**. Requiere decisión:
- Opción A: Crear vistas básicas (index + sync) - ~30 min
- Opción B: Si módulo deprecated, eliminar rutas y referencias - ~15 min

### Solución recomendada
**Opción A** (recomendada): Crear vistas mínimas funcionales para no perder funcionalidad legada. El módulo Firebird tiene jobs, services y modelos completos.

---

## BUG-004 — Índice Único de Attendances

### Título
Índice único incorrecto para deduplicación real

### Severidad documentada
HIGH

### Severidad real
**PARCIALMENTE CORREGIDO PERO CON BUG CRÍTICO EN SYNC**

### Archivo
- Migración: `database/migrations/2024_01_01_000003_create_attendances_table.php` (original)
- Migración: `database/migrations/2026_09_05_010214_add_academia_fields_to_attendances_table.php` (modificadora - YA EJECUTADA)
- Servicio: `app/Services/ZktecoService.php` método `syncAttendances()` líneas 409-456

### Código involucrado

**Índice actual en BD (verificado):**
```sql
UNIQUE INDEX att_emp_rec_dev_type_unique (employee_id, recorded_at, device_id, attendance_type)
```
El índice original `att_unique_punch` (device_id, user_id, recorded_at, state) FUE ELIMINADO por la migración 2026_09_05_010214.

**Sync logic en ZktecoService::syncAttendances():**
```php
$identity = [
    'device_id' => $this->device->id,
    'user_id' => $userKey,
    'state' => (int) $record['state'],
    'recorded_at' => $record['record_time'],
];
$inserted = DB::table('attendances')->insertOrIgnore(array_merge($identity, [
    'employee_id' => $employeeId,
    'type' => $type,
    'created_at' => now(),
    'updated_at' => now(),
]));
```

### Qué afirma la auditoría
Unique index debería ser `[employee_id, recorded_at, device_id]` pero es `[device_id, user_id, recorded_at, state]`. Causa duplicados si mismo empleado checa 2x mismo segundo.

### Qué se encontró realmente
**PARCIALMENTE CORRECTO PERO INTRODUCE NUEVO BUG CRÍTICO**.

La migración 2026_09_05_010214 YA SE EJECUTÓ y cambió el índice único a:
- `att_emp_rec_dev_type_unique` en `(employee_id, recorded_at, device_id, attendance_type)`

**PERO** el método `syncAttendances()` sigue usando el identity del índice ANTERIOR:
- `insertOrIgnore` con `['device_id', 'user_id', 'state', 'recorded_at']`

**Resultado**: `insertOrIgnore` NO detecta duplicados porque el índice único actual no coincide con las columnas del identity. Se insertarán filas duplicadas.

### ¿Se reproduce?
Sí. Cada sincronización de asistencias insertará duplicados porque:
1. El unique index actual requiere `(employee_id, recorded_at, device_id, attendance_type)`
2. El insertOrIgnore usa `(device_id, user_id, state, recorded_at)`
3. MySQL no encuentra conflicto en el índice actual → inserta fila duplicada

### Prueba utilizada
- `SHOW INDEX FROM attendances` → confirma índice actual `att_emp_rec_dev_type_unique`
- Lectura de `ZktecoService::syncAttendances()` líneas 424-435 → confirma identity usado

### Causa raíz
Migración cambió el índice único pero NO se actualizó la lógica de sincronización en `ZktecoService::syncAttendances()` para usar el nuevo identity.

### Impacto real
**CRÍTICO**: Duplicados masivos en tabla attendances en cada sincronización. Cada sync re-inserta todas las asistencias del dispositivo.

### Dependencias
- `ZktecoService::syncAttendances()` debe actualizarse
- `SyncDeviceJob` usa este método
- Tests: `ZktecoSyncTest::test_users_sync_without_duplicates`

### Riesgo de reparación
**ALTO**. Cambio en lógica de sync + posible migración adicional si se quiere quitar `attendance_type` del unique index (requiere análisis de negocio).

### Solución recomendada
**Opción 1 (Mínima - Recomendada)**: Actualizar `syncAttendances()` para usar identity que coincida con índice actual:
```php
$identity = [
    'employee_id' => $employeeId,
    'recorded_at' => $record['record_time'],
    'device_id' => $this->device->id,
    'attendance_type' => 'biometric', // valor por defecto para sync ZKTeco
];
```

**Opción 2**: Si el negocio requiere unique solo por `(employee_id, recorded_at, device_id)` sin `attendance_type`, crear migración para modificar el índice único (dropear el actual, crear nuevo).

**Nota**: La Opción 1 es más segura porque mantiene compatibilidad con el campo `attendance_type` que permite distinguir asistencias biométricas vs manuales/clase en el mismo timestamp.

---

## BUG-005 — device_id NULL + Unique Index / Error 1062

### Título
Fingerprint device_id nullable + unique index MySQL

### Severidad documentada
HIGH

### Severidad real
**FALSO POSITIVO** (el error 1062 no ocurre en MySQL como se describe)

### Archivo
- Modelo: `app/Models/Fingerprint.php`
- Migración: `database/migrations/2026_08_23_000002_add_device_id_to_fingerprints.php`

### Código involucrado

**Esquema actual (verificado):**
```sql
UNIQUE INDEX fingerprints_employee_id_finger_device_id_unique (employee_id, finger, device_id)
```
Columna `device_id`: `foreignId nullable` (Null = YES)

### Qué afirma la auditoría
"Filas legacy (device_id=NULL) violan unicidad en MySQL" → Error 1062 Duplicate entry al insertar legacy rows.

### Qué se encontró realmente
**FALSO POSITIVO** para MySQL.

En MySQL, un índice UNIQUE en columnas que permiten NULL **PERMITE múltiples valores NULL**. La semántica es: `NULL != NULL` para propósitos de unicidad.

**Verificación empírica:**
```sql
-- Tabla actual permite múltiples filas con mismo (employee_id, finger) y device_id=NULL
SELECT employee_id, finger, device_id, COUNT(*) as cnt
FROM fingerprints
GROUP BY employee_id, finger, device_id
HAVING cnt > 1;
-- Resultado: 0 filas (no hay duplicados actuales, pero el esquema LO PERMITE)
```

**Comportamiento real:**
- Row 1: `(employee_id=1, finger=0, device_id=NULL)` → OK
- Row 2: `(employee_id=1, finger=0, device_id=NULL)` → **OK en MySQL** (no viola unique index)
- Row 3: `(employee_id=1, finger=0, device_id=5)` → OK (device_id distinto)
- Row 4: `(employee_id=1, finger=0, device_id=5)` → **ERROR 1062** (duplicado real)

### ¿Se reproduce el error 1062 documentado?
**NO**. El error 1062 "Duplicate entry" solo ocurre si hay valores NO-NULL duplicados en la tupla del índice único. Múltiples NULLs son permitidos.

### Prueba utilizada
- `SHOW INDEX FROM fingerprints` → confirma índice `fingerprints_employee_id_finger_device_id_unique` con `device_id` Null=YES
- Query de duplicados agrupando por `(employee_id, finger, device_id)` → 0 resultados

### Causa raíz (del reporte)
Malentendido del comportamiento de UNIQUE indexes con columnas NULL en MySQL vs SQL Server/Oracle (donde únicos filtran NULLs).

### Impacto real
**NO HAY ERROR 1062 ACTUAL**. Pero hay **RIESGO DE DISEÑO**:
- El esquema permite múltiples huellas "legacy" (device_id=NULL) para el mismo empleado+dedo
- La lógica de negocio en `ZktecoService::syncFingerprintForEmployee()` usa `updateOrCreate` con `['employee_id', 'finger', 'device_id']` - si device_id es NULL, podría actualizar la fila incorrecta si hay múltiples NULLs

### Dependencias
- `ZktecoService::syncFingerprintForEmployee()` líneas 535+
- `DeviceEmployee` pivot

### Riesgo de reparación
**BAJO** si se decide mantener esquema actual (documentar comportamiento).
**MEDIO** si se quiere forzar device_id NOT NULL (requiere migración + backfill de legacy rows).

### Solución recomendada
**Opción A (Mínima - Recomendada)**: Documentar que multiple NULL device_id por (employee_id, finger) es PERMITIDO y la lógica de sync usa `device_id` del dispositivo actual, así que no hay conflicto práctico. Agregar comentario en migración/modelo.

**Opción B (Estricta)**: Si el negocio requiere máximo 1 huella por (empleado, dedo) globalmente:
1. Migración: hacer `device_id` NOT NULL con default 0 o valor sentinel para legacy
2. Cambiar unique index a `(employee_id, finger)` sin device_id (pero pierde trazabilidad por dispositivo)

**Nota**: La arquitectura actual (centralización de empleados + pivot device_employee) sugiere que una huella SÍ pertenece a un dispositivo específico. Las filas legacy con NULL son "origen desconocido" y deberían ser raras. Opción A es pragmática.

---

## Resumen de Validación

| BUG | Severidad Doc | Severidad Real | Estado | Acción Requerida |
|-----|---------------|----------------|--------|------------------|
| BUG-001 | CRITICAL | CRITICAL | CONFIRMADO | Fix inmediato (throttle login) |
| BUG-002 | CRITICAL | CRITICAL | CONFIRMADO | Fix inmediato (throttle sync) |
| BUG-003 | HIGH | HIGH | CONFIRMADO | Decidir: crear vistas O eliminar rutas |
| BUG-004 | HIGH | CRÍTICO* | PARCIALMENTE CORREGIDO | **URGENTE**: Fix syncAttendances() para coincidir con índice actual |
| BUG-005 | HIGH | N/A | FALSO POSITIVO | Documentar comportamiento MySQL; no requiere fix de BD |

*BUG-004 se vuelve CRÍTICO porque la migración cambió el índice pero el sync no se actualizó → duplicados garantizados en cada sync.

---

## Próximos Pasos

1. **Inmediato**: Aplicar CHANGE-001 (BUG-001) y CHANGE-002 (BUG-002) - 15 min
2. **Urgente**: Fix BUG-004 en `ZktecoService::syncAttendances()` antes de cualquier sync en producción
3. **Decisión**: BUG-003 - ¿Crear vistas Firebird o eliminar módulo?
4. **Documentar**: BUG-005 - Actualizar docs1/11_BUGS.md a FALSE_POSITIVE