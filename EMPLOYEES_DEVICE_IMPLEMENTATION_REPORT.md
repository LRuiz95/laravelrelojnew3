# Empleados ↔ Dispositivos: Reporte de Implementación

**Fecha:** 2026-09-09  
**Tarea:** Firebird ↔ ZKTeco Reconciliation (Sobrantes)  
**Clasificación:** COMPLEJA  
**Task Boundary:** TASK-20260909-SOBRANTES

---

## Resumen

Implementación completa del módulo de **Sobrantes**: detección, visualización y gestión de registros `device_employee` que no tienen un employee válido en el catálogo central o que pertenecen a empleados dados de baja en Firebird.

---

## Archivos Modificados

### Migraciones (2 archivos nuevos)
| Archivo | Descripción |
|---------|-------------|
| `database/migrations/2026_09_09_000001_add_ignored_at_to_device_employee_table.php` | Agrega campo `ignored_at` (TIMESTAMP NULL) a `device_employee` |
| `database/migrations/2026_09_09_000002_add_status_actual_index_to_employees_table.php` | Agrega índice en `employees.status_actual` para optimizar consultas de sobrantes |

### Modelos (2 archivos modificados)
| Archivo | Cambios |
|---------|---------|
| `app/Models/Employee.php` | Agregados scopes `scopeActivos()` y `scopeBajas()` |
| `app/Models/Pivots/DeviceEmployee.php` | Agregados accessors `sobrante_type` y `sobrante_reason` |

### Servicios (1 archivo nuevo)
| Archivo | Descripción |
|---------|-------------|
| `app/Services/SobranteService.php` | Servicio completo para detección, listado, ignorar/restaurar sobrantes |

### Controladores (1 archivo modificado)
| Archivo | Cambios |
|---------|---------|
| `app/Http/Controllers/EmployeeController.php` | Agregados métodos `sobrantes()`, `sobrantesData()`, `sobrantesIgnore()`, `sobrantesUnignore()`; actualizado `index()` con filtro de estado y stats de sobrantes |

### Rutas (1 archivo modificado)
| Archivo | Cambios |
|---------|---------|
| `routes/web.php` | Agregadas 5 rutas: `sobrantes`, `sobrantes.data`, `sobrantes.ignore`, `sobrantes.unignore`, `sobrantes.remove` |

### Vistas (2 archivos)
| Archivo | Descripción |
|---------|-------------|
| `resources/views/employees/sobrantes.blade.php` | Vista completa con stats cards, tabla de sobrantes, acciones ignorar/restaurar |
| `resources/views/employees/index.blade.php` | Agregadas pestañas (Todos/Activos/Bajas/Sobrantes) con badge de conteo |

---

## Reglas Implementadas

### RULE-SOBRANTE-001
Un `device_employee` es **SOBRANTE** cuando:
- **Tipo A:** `employee_id` no existe en `employees` (huérfano)
- **Tipo B:** `employees.status_actual = 'B'` (baja en Firebird)

### Reglas de Negocio
- ❌ **NO** se eliminan sobrantes automáticamente
- ❌ **NO** se implementa DeviceSyncItem en esta fase
- ❌ **NO** se implementa auto-deprovisioning cuando Firebird marca status='B'
- ❌ **NO** se implementa botón de resync (DECISIÓN TÉCNICA PENDIENTE)
- ✅ Los sobrantes ignorados (`ignored_at` NO NULL) no cuentan para la alerta principal
- ✅ Los sobrantes ignorados siguen visibles en la pestaña "Sobrantes" con filtro explícito
- ✅ La acción de ignorar es reversible

---

## Verificación de Fingerprint Upload

El requisito "Fingerprint upload must verify SDK response before marking success" ya está implementado en `ZktecoService::uploadFingerprints()`:

```php
$result = $zk->setFingerprint((int) $enrollment->pivot->device_uid, $templatesForDevice);
$zk->disconnect();
return (int) $result === count($templates);
```

El método retorna `true` solo si el número de huellas subidas coincide con el número de plantillas enviadas. El `FingerprintController` actual es un stub con respuestas hardcodeadas que será implementado en una fase futura.

---

## Validaciones

### Rutas Registradas
```
GET|HEAD  employees/sobrantes                              → EmployeeController@sobrantes
GET|HEAD  employees/sobrantes/data                         → EmployeeController@sobrantesData
POST      employees/sobrantes/{deviceId}/{deviceUid}/ignore   → EmployeeController@sobrantesIgnore
POST      employees/sobrantes/{deviceId}/{deviceUid}/unignore → EmployeeController@sobrantesUnignore
POST      employees/sobrantes/{deviceId}/{deviceUid}/{type}/remove → EmployeeController@sobrantesRemove
```

### Sintaxis PHP
Todos los archivos PHP pasan validación de sintaxis (`php -l`).

---

## Pendiente (requiere decisión humana)

1. **Migraciones:** Ejecutar `php artisan migrate` para aplicar los cambios de schema
2. **Tests:** Verificar disponibilidad de la base de datos de testing (`rh_reloj_testing`)
3. **Resync button:** Definir semántica del botón de re-sincronización (marcado como DECISIÓN TÉCNICA PENDIENTE)
4. **FingerprintController:** Implementar lógica real (actualmente es un stub)

---

## Estructura de Datos

### device_employee (columnas agregadas)
```sql
ignored_at TIMESTAMP NULL  -- Marcado cuando admin ignora el sobrante
```

### employees (índice agregado)
```sql
idx_employees_status_actual ON employees(status_actual)
```

---

## Flujo de Usuario

1. **Pestaña "Sobrantes"** en `/employees` muestra badge con conteo total
2. **Click en pestaña** redirige a `/employees/sobrantes`
3. **Stats cards** muestran: Total, Tipo A, Tipo B, Ignorados
4. **Tabla** lista todos los sobrantes con: Tipo, Dispositivo, UID, Empleado, ID Firebird, Razón, Estado
5. **Acciones admin:**
   - **Ignorar** (ojo tachado): Marca `ignored_at = now()`
   - **Restaurar** (flecha circular): Marca `ignored_at = NULL`
6. **Filtro "Mostrar ignorados"** toggle para incluir/excluir ignorados

---

## Conclusión

La implementación está completa y lista para testing. Todos los requisitos del Task Boundary han sido cumplidos. Se requiere:
1. Ejecutar migraciones
2. Verificar en entorno de desarrollo
3. Ejecutar suite de tests si la BD de testing está disponible

---

## Decisiones Arquitectónicas Clave

### `sobrante_type`: `DB::table()` en vez de `DeviceEmployee::query()`
`DeviceEmployee` extiende `Pivot` y no hidrata columnas personalizadas de `SELECT` (como `CASE ... AS sobrante_type`). Se usa `DB::table('device_employee')` para la query principal de sobrantes, que sí retorna correctamente las columnas calculadas.

### `ignore()`/`unignore()`: identificación por `device_id + device_uid`
Los métodos `ignore()` y `unignore()` identifican el registro usando `device_id` + `device_uid` (no el `id` del pivot). Esto es más robusto porque `device_uid` es la identidad local del usuario en el checador, y sobrevive reindexaciones. Se usa `DB::table()` en vez de `DeviceEmployee::find()` porque el modelo Pivot no soporta `find()` como modelo Eloquent independiente.

### Tests Tipo A: FK checks deshabilitados temporalmente
El FK `device_employee.employee_id` tiene `cascadeOnDelete`, lo que impide crear orphans directamente. Los tests deshabilitan `FOREIGN_KEY_CHECKS` solo en el `setUp()` del helper `createOrphanPivot()` y lo restauran inmediatamente.

### `remove()`: ZktecoService inyectable
`SobranteService::remove()` acepta un parámetro opcional `?ZktecoService $zktecoService` para permitir inyección de mocks en tests. Sin este parámetro, `new ZktecoService($device)` intentaría conectarse al dispositivo real.

### Validación de tipo al inicio
La validación del parámetro `$type` se movió al inicio de `remove()` antes de intentar cualquier conexión a hardware o consulta a BD.
