# Análisis de Zona Horaria (Timezone) — CHANGE-020

**Fecha**: 2026-09-06
**Estado**: DOCUMENTADO — Pendiente decisión de implementación

---

## Situación Actual

| Componente | Zona Horaria | Comportamiento |
|------------|--------------|----------------|
| Dispositivo ZKTeco | Local (ej. America/Mexico_City, UTC-6) | Reporta `record_time` como string naive `YYYY-MM-DD HH:MM:SS` sin info de TZ |
| SDK (`Util::decodeTime`) | Naive | Devuelve string `YYYY-MM-DD HH:MM:SS` sin conversión |
| Laravel `app.timezone` | `America/Mexico_City` (config/app.php) | Usada para `now()`, `Carbon`, timestamps Eloquent |
| MySQL Connection | Sin especificar (default del servidor) | Probablemente UTC o SYSTEM |
| Columna `recorded_at` | `datetime` (no `timestamp`) | MySQL **no convierte** zona horaria, guarda tal cual |

---

## El Problema

1. Dispositivo checa a las **08:00:00** hora local (CDMX, UTC-6)
2. SDK devuelve `"2026-09-06 08:00:00"`
3. Se inserta directamente en `recorded_at` (`datetime`)
4. MySQL guarda `2026-09-06 08:00:00` **sin conversión**
5. Laravel lee con TZ `America/Mexico_City` → interpreta `08:00:00` como **CDMX**
6. **Resultado**: La hora se muestra correcta **solo si** el servidor MySQL está en la misma TZ que el dispositivo
7. **Si MySQL está en UTC**: La hora real era `14:00:00 UTC`, pero se guardó `08:00:00` → **desfase de 6 horas**

---

## Evidencia en Código

**ZktecoService::syncAttendances()** (línea 429):
```php
'recorded_at' => $record['record_time'],  // String naive del dispositivo
```

**Attendance Model** (línea 25):
```php
'recorded_at' => 'datetime',  // No usa datetime:timezone ni immutable_datetime
```

**config/database.php** — MySQL connection **sin** `'timezone' => '+00:00'`

---

## Opciones de Corrección

### Opción A — Convertir a UTC al insertar (RECOMENDADA)

**Cambios**:
1. `config/database.php` → MySQL: `'timezone' => '+00:00'` (forzar conexión UTC)
2. `Attendance::$casts` → `'recorded_at' => 'datetime:timezone'` (o `immutable_datetime:timezone`)
3. `ZktecoService::syncAttendances()` → Convertir `$record['record_time']` de TZ del dispositivo a UTC antes de insertar

```php
// En syncAttendances, asumiendo TZ del dispositivo conocida (configurable por device)
$deviceTz = $this->device->timezone ?? config('app.timezone'); // 'America/Mexico_City'
$recordedAtUtc = Carbon::parse($record['record_time'], $deviceTz)->utc()->format('Y-m-d H:i:s');

$identity = [
    'device_id' => $this->device->id,
    'employee_id' => $employeeId,
    'recorded_at' => $recordedAtUtc,  // Ya en UTC
];
```

**Ventajas**:
- Datos consistentes en UTC en BD (estándar)
- Funciona con dispositivos en distintas zonas horarias (cada device tiene su TZ)
- Laravel convierte automáticamente a TZ del usuario al mostrar

**Desventajas**:
- Requiere agregar campo `timezone` a tabla `devices` (migración)
- Cambio en casteo del modelo (afecta serialización JSON, accessors)
- Requiere backfill de datos existentes si hay datos

---

### Opción B — Forzar conexión MySQL a TZ del dispositivo

**Cambios**:
1. `config/database.php` → MySQL: `'timezone' => 'America/Mexico_City'`
2. Mantener `recorded_at` como `datetime`
3. **Requisito**: Todos los dispositivos DEBEN estar en la misma zona horaria

**Ventajas**:
- Cambio mínimo (solo config)
- No requiere migración ni backfill

**Desventajas**:
- **Frágil**: Si hay dispositivos en distinta TZ, sus horas serán incorrectas
- No escala a multi-sucursal con husos distintos
- Acopla BD a TZ de la aplicación

---

### Opción C — Guardar naive + columna TZ explícita

**Cambios**:
1. Agregar `device_timezone` VARCHAR a `attendances` (o usar `devices.timezone` via FK)
2. Mantener `recorded_at` como `datetime` naive
3. Accessor en modelo que convierta según `device_timezone`

**Ventajas**:
- Preserva hora original del dispositivo
- Permite auditoría de qué TZ reportó cada registro

**Desventajas**:
- Complejidad en queries (filtros por fecha requieren conversión)
- Overhead de storage
- Fácil cometer errores en reportes

---

## Recomendación

**Opción A** — Es el enfoque estándar en sistemas distribuidos:
1. Migración: agregar `timezone` a `devices` (default `America/Mexico_City`)
2. Config: MySQL connection `timezone => '+00:00'`
3. Modelo: `recorded_at => 'immutable_datetime:timezone'`
4. Servicio: conversión explicita en `syncAttendances` usando `$device->timezone`
5. Backfill: script único para datos existentes (si los hay)

**Impacto estimado**: 2-3 horas (migración + service + model + config + test)

---

## Decisión Requerida

Antes de implementar, confirmar:
1. ¿Todos los dispositivos actuales están en la misma TZ (CDMX)?
2. ¿Hay planes de multi-sucursal con husos distintos?
3. ¿Aceptan el cambio de casteo a `immutable_datetime:timezone` (rompe compatibilidad JSON si se serializa directo)?
4. ¿Ejecutan backfill en staging antes de producción?

---

## Referencias
- `docs/zkteco.md` §3: "Zona horaria: Confirma cómo se está normalizando..."
- Laravel Docs: [Date Mutators & Casting](https://laravel.com/docs/10.x/eloquent-mutators#date-casting)
- MySQL Docs: [Time Zone Support](https://dev.mysql.com/doc/refman/8.0/en/time-zone-support.html)