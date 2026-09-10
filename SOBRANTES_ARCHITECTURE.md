# Sobrantes — Arquitectura Correcta (Post-Implementación)

> Este documento documenta la arquitectura implementada para el módulo
> de Sobrantes (device_employee sin employee válido o con employee dado
> de baja).

---

## 1. Regla Formal (RULE-SOBRANTE-001)

Un `device_employee` es **SOBRANTE** cuando:
- **Tipo A:** `employee_id` no existe en `employees` (huérfano, sin catálogo)
- **Tipo B:** `employees.status_actual = 'B'` (baja en Firebird)

`device_employee.active` **NO** afecta la clasificación de sobrante.

---

## 2. Separación de Responsabilidades

```
ZktecoService
 ├── removeUserFromDevice($uid)   ← SOLO hardware
 └── removeUser($uid)             ← DEPRECATED: hardware + persistencia

SobranteService
 ├── query()                      ← Detección con LEFT JOIN + CASE
 ├── get() / paginate()           ← Collection paginada
 ├── getStats()                   ← Conteo por tipo
 ├── ignore() / unignore()        ← Marcar ignorados
 ├── classify()                   ← Clasificar un pivot individual
 ├── findById()                   ← Buscar por ID
 └── remove($deviceId, $deviceUid, $type, ?zkteco)
      ├── Tipo A: hardware + delete pivot
      └── Tipo B: hardware + delete pivot (Employee se conserva)

DeprovisionEmployeeJob
 └── removeUserFromDevice($uid) + lógica de baja explícita (NO MODIFICADO)
```

### Diseño del Query

Una sola consulta con `LEFT JOIN` sobre `employees` + `CASE` en `SELECT`:

```sql
SELECT device_employee.*, employees.*, devices.*,
       CASE WHEN employees.id IS NULL THEN 'A' ELSE 'B' END as sobrante_type,
       CASE WHEN employees.id IS NULL THEN 'NO EXISTE EN CATÁLOGO'
            ELSE 'BAJA EN FIREBIRD' END as sobrante_reason
FROM device_employee
LEFT JOIN employees ON employees.id = device_employee.employee_id
JOIN devices ON devices.id = device_employee.device_id
WHERE (employees.id IS NULL OR employees.status_actual = 'B')
```

**Nota:** Se usa `DB::table()` en vez de `DeviceEmployee::query()` porque
el modelo Pivot no hidrata columnas personalizadas de `SELECT` (tested
y verificado — `DB::table()` sí retorna `sobrante_type`, `DeviceEmployee::query()` retorna NULL).

---

## 3. Flujo por Tipo

### Tipo A (Employee NO existe)

```
SobranteService::remove($deviceId, $deviceUid, 'A')
 │
 ├─ 1. Validar tipo ('A' o 'B')
 ├─ 2. Device::find($deviceId) → verificar dispositivo existe
 ├─ 3. ZktecoService::removeUserFromDevice($deviceUid) → hardware
 │     → Si falla: log warning, continúa (dispositivo puede estar offline)
 ├─ 4. DeviceEmployee::where(...)->delete() → elimina pivot
 └─ 5. Employee NO se accede → no hay NPE
```

### Tipo B (Employee SÍ existe, status_actual='B')

```
SobranteService::remove($deviceId, $deviceUid, 'B')
 │
 ├─ 1. Validar tipo
 ├─ 2. Device::find($deviceId)
 ├─ 3. ZktecoService::removeUserFromDevice($deviceUid) → hardware
 ├─ 4. DeviceEmployee::where(...)->first() → $pivot
 ├─ 5. $pivot->delete() → elimina SOLO el pivot
 └─ 6. Employee SIEMPRE se conserva → NO se accede a $employee->delete()
```

---

## 4. Regla Inquebrantable

```
SobranteService::remove() NUNCA elimina Employee.

La eliminación de sobrantes elimina únicamente:
  - la identidad del dispositivo (hardware), y
  - su relación DeviceEmployee.

Employee NUNCA se elimina como efecto colateral.
DeprovisionEmployeeJob NO se modifica en esta fase.
```

---

## 5. Bugs Encontrados y Corregidos

| Bug | Causa | Fix |
|-----|-------|-----|
| `SobranteService::query()` retornaba `sobrante_type = NULL` | `DeviceEmployee extends Pivot` no hidrata columnas custom de SELECT | Cambiado a `DB::table('device_employee')` |
| `SobranteService::ignore()/unignore()` no funcionaban con `pivotId` | `DeviceEmployee::find()` no funciona standalone para Pivot models | Cambiado a `DB::table()->where()->update()` e identificación por `device_id + device_uid` |
| `remove()` intentaba conectar al hardware antes de validar tipo | Validación de tipo estaba al final del método | Movida al inicio |
| Test `paginate` fallaba con UniqueConstraintViolation | `rand(1,100)` con 30 inserts producía colisiones de `device_uid` | UIDs secuenciales en test |
| Test `Tipo A` fallaba con FK constraint | `cascadeOnDelete` en `device_employee.employee_id` bloqueaba inserts huérfanos | `DB::statement('SET FOREIGN_KEY_CHECKS=0')` en setUp |
| `remove()` no era testeable con Mockery | `new ZktecoService($device)` bypass container | Parámetro inyectable `?ZktecoService` |

---

## 6. Test Plan — Resultados

### SobranteServiceTest (19 tests) — ✅ ALL PASS

| Test | Descripción | Estado |
|------|-------------|--------|
| tipo_a_detected_when_employee_missing | Detección Tipo A | ✅ |
| tipo_b_detected_when_employee_is_baja | Detección Tipo B | ✅ |
| active_employee_is_not_sobrante | Employee activo NO es sobrante | ✅ |
| no_duplicates_when_both_types_exist | Sin duplicados con ambos tipos | ✅ |
| filter_by_device_id | Filtro por dispositivo | ✅ |
| filter_by_type_a | Filtro por tipo A | ✅ |
| filter_by_type_b | Filtro por tipo B | ✅ |
| search_by_name_for_tipo_b | Búsqueda por nombre (solo Tipo B) | ✅ |
| search_does_not_match_tipo_a | Búsqueda NO retorna Tipo A | ✅ |
| ignored_excluded_by_default | Ignorados excluidos por defecto | ✅ |
| ignored_included_when_flagged | Ignorados incluidos con flag | ✅ |
| ignore_sets_ignored_at | ignore() establece ignored_at | ✅ |
| unignore_clears_ignored_at | unignore() limpia ignored_at | ✅ |
| stats_count_by_type | Stats cuentan por tipo | ✅ |
| stats_exclude_ignored | Stats excluyen ignorados | ✅ |
| stats_filter_by_device | Stats filtran por dispositivo | ✅ |
| paginate_works | Paginación funciona | ✅ |
| find_by_id_returns_sobrante | findById retorna sobrante | ✅ |
| find_by_id_returns_null_for_nonexistent | findById retorna null | ✅ |

### SobranteRemoveTest (12 tests) — ✅ ALL PASS

| Test | Descripción | Estado |
|------|-------------|--------|
| remove_tipo_a_deletes_pivot | Tipo A elimina pivot | ✅ |
| remove_tipo_a_does_not_touch_employee_table | Tipo A NO toca employees | ✅ |
| remove_tipo_a_returns_correct_message | Mensaje correcto Tipo A | ✅ |
| remove_tipo_b_deletes_pivot | Tipo B elimina pivot | ✅ |
| remove_tipo_b_preserves_employee | Tipo B conserva employee | ✅ |
| remove_tipo_b_returns_message_about_employee | Mensaje Tipo B menciona employee | ✅ |
| remove_tipo_b_does_not_delete_employee | Employee NO se borra nunca | ✅ |
| remove_returns_error_for_nonexistent_pivot | Error para pivot inexistente | ✅ |
| remove_returns_error_for_invalid_type | Error para tipo inválido | ✅ |
| remove_returns_error_for_nonexistent_device | Error para dispositivo inexistente | ✅ |
| remove_tipo_a_succeeds_even_when_hardware_fails | Tipo A funciona sin hardware | ✅ |
| remove_tipo_b_succeeds_even_when_hardware_fails | Tipo B funciona sin hardware | ✅ |

---

## 7. Estado Final del Sistema de Test

```
Tests:  31 sobrantes (19 + 12) → ALL PASS ✅
Total:  105 pass, 12 fail (pre-existing, unrelated to sobrantes)
        Los 12 failures son de: AdminLayoutComposerTest, FingerprintControllerTest,
        OperationsQueueUnifiedTest, ZktecoSyncTest — todos pre-existentes.
```

---

## 8. Archivos Implementados/Modificados

| Archivo | Acción |
|---------|--------|
| `app/Services/SobranteService.php` | CREADO — query, remove, ignore/unignore, stats, classify |
| `app/Services/ZktecoService.php` | MODIFICADO — `removeUserFromDevice()` (nuevo), `removeUser()` (deprecated) |
| `app/Http/Controllers/EmployeeController.php` | MODIFICADO — métodos sobrantes + stats en index |
| `app/Models/Employee.php` | MODIFICADO — scopes `activos()`, `bajas()` |
| `app/Models/Pivots/DeviceEmployee.php` | MODIFICADO — accessors `sobrante_type`, `sobrante_reason` |
| `resources/views/employees/sobrantes.blade.php` | CREADO — vista con filtros y botón eliminar |
| `resources/views/employees/index.blade.php` | MODIFICADO — tabs (Todos/Activos/Bajas/Sobrantes) |
| `routes/web.php` | MODIFICADO — 5 rutas sobrantes |
| `database/migrations/2026_09_09_000001_*` | CREADO — columna `ignored_at` |
| `database/migrations/2026_09_09_000002_*` | CREADO — índice `status_actual` |
| `tests/Feature/SobranteServiceTest.php` | CREADO — 19 tests |
| `tests/Feature/SobranteRemoveTest.php` | CREADO — 12 tests |
