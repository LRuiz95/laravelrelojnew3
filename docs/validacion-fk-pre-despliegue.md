# Validación de FKs e índices — pre-despliegue (MySQL)

La migración `2026_08_23_000004` ejecuta `$table->dropForeign(['device_id'])`, que Laravel resuelve al **nombre convencional** `employees_device_id_foreign`. Si en producción esa FK tiene otro nombre (creada a mano, o por una migración histórica con nombre explícito), el `DROP COLUMN` fallará a mitad del DDL sin transacción.

Ejecutar **antes** de correr `deploy.sh`.

## 1. FKs existentes sobre `employees`

```sql
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'employees'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

Resultado esperado:

| CONSTRAINT_NAME              | COLUMN_NAME | REFERENCED_TABLE_NAME |
|------------------------------|-------------|-----------------------|
| `employees_device_id_foreign` | `device_id` | `devices`             |

Si el nombre difiere, renombrarla antes del despliegue:

```sql
ALTER TABLE employees DROP FOREIGN KEY <nombre_actual>;
ALTER TABLE employees
  ADD CONSTRAINT employees_device_id_foreign
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE;
```

## 2. Índices únicos que la migración busca por nombre

```sql
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME  = 'employees'
GROUP BY INDEX_NAME;
```

Debe existir `employees_device_id_user_id_unique` (`device_id,user_id`) y **no** debe existir aún un índice no único global sobre `user_id`. La migración crea `employees_user_id_unique` y es tolerante si los nombres previos no existen (usa `indexExists()`), pero conviene confirmarlo.

## 3. Índices de `fingerprints` (afectados por migración 2)

```sql
SHOW INDEX FROM fingerprints;
-- Esperado: único compuesto sobre (employee_id, finger) que será reemplazado
-- por (device_id, employee_id, finger).
```

**Hallazgo real (MySQL/MariaDB error 1553):** el único `(employee_id, finger)` es el índice soporte de la FK `fingerprints_employee_id_foreign`. La migración 2 ya está corregida para crear primero el nuevo único `(employee_id, finger, device_id)` — que también inicia por `employee_id` y hereda el soporte de la FK — y soltar el viejo después. Si adaptas esta migración a otro proyecto, conserva ese orden o el `DROP INDEX` fallará.

## 4. Equivalente vía Artisan/Tinker (sin SQL manual)

```bash
php artisan tinker --execute="dump(DB::getSchemaBuilder()->getForeignNames('employees'));"
php artisan tinker --execute="dump(collect(DB::getSchemaBuilder()->getIndexes('employees'))->pluck('name'));"
```

## 5. Verificación post-despliegue (paridad de datos)

Tras `deploy.sh`, comprobar que ningún empleado quedó sin su enrolamiento migrado (todo legado con `device_id` debe tener exactamente 1 fila en la pivote):

```sql
-- Empleados sin enrolamiento: deben ser solo los que ya tenían device_id NULL.
SELECT COUNT(*) FROM employees e
LEFT JOIN device_employee de ON de.employee_id = e.id
WHERE de.employee_id IS NULL;

-- UIDs duplicados dentro del mismo dispositivo (debe ser 0):
SELECT device_id, device_uid, COUNT(*) c FROM device_employee
GROUP BY device_id, device_uid HAVING c > 1;

-- Huellas huérfanas sin origen (aceptable; se re-atribuyen al primer sync):
SELECT COUNT(*) FROM fingerprints WHERE device_id IS NULL;
```
