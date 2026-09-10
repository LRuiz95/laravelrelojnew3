# FASE 2 — DISEÑO TÉCNICO DE RECONCILIACIÓN FIREBIRD ↔ ZKTECO

**Generado:** 2026-09-09  
**Estado:** Diseño completo, listo para implementación  
**Fase anterior:** Auditoría (PROJECT_HEALTH_REPORT.md)

---

## 1. Resumen

El sistema RHreloj sincroniza empleados desde Firebird 2.5 hacia MySQL y luego
los enrola en dispositivos ZKTeco. La Fase 1 confirmó que la arquitectura
correcta ya existe (catálogo central + pivote), pero faltan componentes clave:

1. **Pestaña "Sobrantes"** — detectar device_employee sin employee válido
2. **Vista consolidada** — mostrar TODOS los dispositivos y huellas por empleado
3. **Corrección de copia de huellas** — verificar respuesta del SDK antes de confirmar
4. **Rate limiting** — proteger endpoints de sincronización
5. **DeviceSyncItem** — auditoría a nivel granular por operación

Este documento define el diseño técnico para implementar estos componentes.

---

## 2. Estado Actual Validado

### 2.1 Fuentes de verdad confirmadas

| Fuente | Tabla MySQL | Identificador | Rol |
|--------|-------------|---------------|-----|
| Firebird EMPLEADOS | `employees` | `user_id` = `NUMEMPLEADO` | Catálogo maestro |
| Dispositivo ZKTeco | `device_employee` | `device_uid` por dispositivo | Presencia física |
| Biometría | `fingerprints` | `employee_id + device_id + finger` | Huellas por dispositivo |

### 2.2 Relaciones Eloquent existentes

```php
// Employee.php
public function devices(): BelongsToMany
{
    return $this->belongsToMany(Device::class, 'device_employee')
        ->using(DeviceEmployee::class)
        ->withPivot('device_uid', 'role', 'card_number', 'password', 'active', 'fingerprint_count')
        ->withTimestamps();
}

public function fingerprints(): HasMany
{
    return $this->hasMany(Fingerprint::class);
}

// Device.php
public function employees(): BelongsToMany
{
    return $this->belongsToMany(Employee::class, 'device_employee')
        ->using(DeviceEmployee::class)
        ->withPivot('device_uid', 'role', 'card_number', 'password', 'active', 'fingerprint_count')
        ->withTimestamps();
}

// Fingerprint.php
public function employee(): BelongsTo
{
    return $this->belongsTo(Employee::class);
}

public function device(): BelongsTo
{
    return $this->belongsTo(Device::class);
}
```

### 2.3 Tabla device_employee

```sql
CREATE TABLE device_employee (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    device_uid INT UNSIGNED NOT NULL,
    role TINYINT UNSIGNED DEFAULT 0,
    card_number VARCHAR(255) NULL,
    password VARCHAR(255) NULL,
    active BOOLEAN DEFAULT TRUE,
    fingerprint_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY device_employee_device_id_device_uid_unique (device_id, device_uid),
    UNIQUE KEY device_employee_device_id_card_number_unique (device_id, card_number),
    INDEX employee_id (employee_id)
);
```

### 2.4 Tabla fingerprints

```sql
CREATE TABLE fingerprints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    device_id BIGINT UNSIGNED NULL,
    finger INT NOT NULL,
    template TEXT NULL,
    template_hash VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY fingerprints_device_employee_finger_unique (device_id, employee_id, finger)
);
```

### 2.5 Estados de employee

```php
// Employee.php
public function getStatusActualLabelAttribute(): string
{
    return match ($this->status_actual) {
        'A' => 'Activo',
        'B' => 'Baja',
        default => $this->status_actual ?? '—',
    };
}
```

**Estados confirmados:**
- `'A'` = Activo (empleado vigor)
- `'B'` = Baja (empleado dado de baja)
- `NULL` o otro = sin dato (tratar como activo por seguridad)

### 2.6 Firebird sync — mapeo de estados

```php
// CatalogSmartSync.php — TABLE_MAP['EMPLEADOS']
'EMPLEADOS' => [
    'mysql'   => 'employees',
    'columns' => [
        'user_id'         => 'NUMEMPLEADO',
        'name'            => 'NOMBREEMPLEADO',
        'status_actual'   => 'STATUSACTUAL',
        // ...
    ],
    'identity' => ['user_id'],
],
```

**Confirmado:** `STATUSACTUAL` de Firebird → `employees.status_actual` en MySQL.
No existe otro campo que indique baja en el código actual.

---

## 3. Regla Formal de Sobrantes

### 3.1 Definición

```
RULE-SOBRANTE-001:
    Un device_employee es SOBRANTE si:
        A) employee_id no existe en employees (huérfano)
        O
        B) employees.status_actual = 'B' (baja en Firebird)
```

### 3.2 Justificación

| Caso | Condición | Origen | Motivo |
|------|-----------|--------|--------|
| **A** | `device_employee.employee_id NOT IN (SELECT id FROM employees)` | Borrado manual o inconsistencia | El dispositivo tiene un empleado que ya no existe en el catálogo RH |
| **B** | `employees.status_actual = 'B'` | Firebird sync (`STATUSACTUAL = 'B'`) | El empleado fue dado de baja en el sistema de nómina/Firebird pero aún está cargado en checadores |

### 3.3 Datos involucrados

```
Para cada sobrante se muestra:
- device_employee.device_id → nombre del dispositivo
- device_employee.device_uid → ID interno en el checador
- device_employee.card_number → tarjeta (si existe)
- device_employee.fingerprint_count → huellas cargadas
- device_employee.active → estado del enrolamiento
- employees.user_id → número de empleado (si existe)
- employees.name → nombre (si existe)
- employees.status_actual → 'A' o 'B'
- devices.name → nombre del checador
- devices.ip → dirección IP
- devices.status → online/offline/unknown
```

### 3.4 Casos límite

| Caso | Comportamiento esperado |
|------|------------------------|
| `device_employee` sin `employee` | SOBRANTE tipo A — motivo: "NO EXISTE EN CATÁLOGO" |
| `employee` con `status_actual = 'B'` | SOBRANTE tipo B — motivo: "BAJA EN FIREBIRD" |
| `employee` con `status_actual = NULL` | NO es sobrante — tratar como activo |
| `employee` con `status_actual = 'A'` | NO es sobrante |
| `device_employee.active = false` | **Sí es sobrante** si cumple A o B (el campo active no afecta la regla) |
| Sobrante ignorado (`ignored_at IS NOT NULL`) | Se excluye del conteo de alertas pero sigue visible en la pestaña |

---

## 4. Análisis de Migraciones Propuestas

### 4.1 `device_employee.ignored_at`

| Pregunta | Respuesta |
|----------|-----------|
| ¿Para qué sirve? | Marcar un sobrante como revisado/ignorado permanentemente |
| ¿La información ya existe? | NO |
| ¿Puede derivarse de device_syncs? | NO |
| ¿Puede derivarse de firebird_syncs? | NO |
| ¿Puede derivarse de updated_at? | NO — updated_at se actualiza en cada sincronización |
| ¿Existe otra solución sin modificar el esquema? | Usar una tabla separada `ignored_sobrantes` — más complejo |
| ¿Es necesario persistirlo? | **SÍ** — el estado de ignorado debe sobrevivir reintentos |

**Clasificación: NECESARIO**

### 4.2 `device_employee.last_synced_at`

| Pregunta | Respuesta |
|----------|-----------|
| ¿Para qué sirve? | Saber cuándo se sincronizó por última vez este enrolamiento |
| ¿La información ya existe? | `device_employee.updated_at` se actualiza en cada `updateExistingPivot()` |
| ¿Puede derivarse de device_syncs? | SÍ — `DeviceSync.where(device_id, employee_id).latest().finished_at` |
| ¿Puede derivarse de firebird_syncs? | NO directamente |
| ¿Puede derivarse de updated_at? | **SÍ** — con la limitación de que también se actualiza en otros cambios |
| ¿Es necesario persistirlo? | **NO** — `updated_at` del pivote ya sirve como proxy; el `DeviceSync` da el detalle exacto |

**Clasificación: REDUNDANTE** — No crear esta columna. Usar `device_employee.updated_at` + `DeviceSync.finished_at`.

### 4.3 `employees.firebird_last_seen`

| Pregunta | Respuesta |
|----------|-----------|
| ¿Para qué sirve? | Detectar si un empleado aún viene de Firebird en la última sincronización |
| ¿La información ya existe? | `FirebirdSyncItem` podría tener esta info si se poblara |
| ¿Puede derivarse de device_syncs? | NO — device_syncs es de ZKTeco, no de Firebird |
| ¿Puede derivarse de firebird_syncs? | SÍ parcialmente — `FirebirdSyncItem.where(table='employees').latest()` |
| ¿Puede derivarse de updated_at? | NO — `employees.updated_at` se actualiza también por cambios manuales |
| ¿Es necesario persistirlo? | **ÚTIL PERO OPCIONAL** — podría detectar "este employee no viene de Firebird desde hace X días" |

**Clasificación: ÚTIL PERO OPCIONAL** — No incluir en la primera implementación. Puede agregarse después si se necesita detectar empleados "fantasma" que no aparecen en Firebird pero tienen status 'A'.

### 4.4 Resumen

| Campo | Clasificación | Acción |
|-------|---------------|--------|
| `device_employee.ignored_at` | **NECESARIO** | Crear migración |
| `device_employee.last_synced_at` | **REDUNDANTE** | No crear — usar `updated_at` |
| `employees.firebird_last_seen` | **ÚTIL PERO OPCIONAL** | No crear en esta fase |

---

## 5. Diseño de Consulta de Sobrantes

### 5.1 Consulta SQL principal

```sql
-- Sobrantes tipo A: device_employee sin employee
SELECT
    de.id AS pivot_id,
    de.device_id,
    de.device_uid,
    de.card_number,
    de.fingerprint_count,
    de.active,
    de.created_at,
    de.updated_at,
    NULL AS employee_id,
    NULL AS user_id,
    NULL AS name,
    NULL AS status_actual,
    d.name AS device_name,
    d.ip AS device_ip,
    d.status AS device_status,
    'A' AS sobrante_type,
    'NO EXISTE EN CATÁLOGO' AS sobrante_reason
FROM device_employee de
JOIN devices d ON d.id = de.device_id
LEFT JOIN employees e ON e.id = de.employee_id
WHERE e.id IS NULL

UNION ALL

-- Sobrantes tipo B: employee con status_actual = 'B'
SELECT
    de.id AS pivot_id,
    de.device_id,
    de.device_uid,
    de.card_number,
    de.fingerprint_count,
    de.active,
    de.created_at,
    de.updated_at,
    e.id AS employee_id,
    e.user_id,
    e.name,
    e.status_actual,
    d.name AS device_name,
    d.ip AS device_ip,
    d.status AS device_status,
    'B' AS sobrante_type,
    'BAJA EN FIREBIRD' AS sobrante_reason
FROM device_employee de
JOIN devices d ON d.id = de.device_id
JOIN employees e ON e.id = de.employee_id
WHERE e.status_actual = 'B'

-- Excluir ignorados (si el campo existe)
AND de.ignored_at IS NULL

ORDER BY device_name, device_uid;
```

### 5.2 Consulta Eloquent (repository)

```php
// app/Repositories/SobranteRepository.php

public function query(): Builder
{
    $typeA = DeviceEmployee::query()
        ->select(
            'device_employee.id as pivot_id',
            'device_employee.device_id',
            'device_employee.device_uid',
            'device_employee.card_number',
            'device_employee.fingerprint_count',
            'device_employee.active',
            'device_employee.created_at',
            'device_employee.updated_at',
            DB::raw('NULL as employee_id'),
            DB::raw('NULL as user_id'),
            DB::raw('NULL as name'),
            DB::raw('NULL as status_actual'),
            'devices.name as device_name',
            'devices.ip as device_ip',
            'devices.status as device_status',
            DB::raw("'A' as sobrante_type"),
            DB::raw("'NO EXISTE EN CATÁLOGO' as sobrante_reason")
        )
        ->join('devices', 'devices.id', '=', 'device_employee.device_id')
        ->leftJoin('employees', 'employees.id', '=', 'device_employee.employee_id')
        ->whereNull('employees.id');

    $typeB = DeviceEmployee::query()
        ->select(
            'device_employee.id as pivot_id',
            'device_employee.device_id',
            'device_employee.device_uid',
            'device_employee.card_number',
            'device_employee.fingerprint_count',
            'device_employee.active',
            'device_employee.created_at',
            'device_employee.updated_at',
            'employees.id as employee_id',
            'employees.user_id',
            'employees.name',
            'employees.status_actual',
            'devices.name as device_name',
            'devices.ip as device_ip',
            'devices.status as device_status',
            DB::raw("'B' as sobrante_type"),
            DB::raw("'BAJA EN FIREBIRD' as sobrante_reason")
        )
        ->join('devices', 'devices.id', '=', 'device_employee.device_id')
        ->join('employees', 'employees.id', '=', 'device_employee.employee_id')
        ->where('employees.status_actual', '=', 'B');

    // Combinar con UNION
    return $typeA->union($typeB);
}
```

### 5.3 Índices utilizados

| Índice | Tabla | Uso en consulta |
|--------|-------|-----------------|
| `device_employee_device_id_device_uid_unique` | `device_employee` | JOIN con devices |
| `employee_id` (INDEX) | `device_employee` | LEFT JOIN con employees |
| `PRIMARY` | `employees` | WHERE employees.id = ? |
| `PRIMARY` | `devices` | JOIN devices.id |
| `status_actual` (futuro) | `employees` | WHERE status_actual = 'B' |

**Índice recomendado agregar:**
```php
$table->index('status_actual', 'employees_status_actual_index');
```

### 5.4 Paginación y filtros

```php
// Controllers/EmployeeController.php → sobrantes()

public function sobrantes(Request $request): View
{
    $query = (new SobranteRepository())->query();

    // Filtro por dispositivo
    if ($deviceId = $request->query('device_id')) {
        $query->where('device_employee.device_id', $deviceId);
    }

    // Filtro por tipo de sobrante
    if ($type = $request->query('type')) {
        $query->where('sobrante_type', $type);
    }

    // Búsqueda por nombre o ID
    if ($search = trim((string) $request->query('q', ''))) {
        $query->where(function ($q) use ($search) {
            $q->where('employees.name', 'like', "%{$search}%")
              ->orWhere('employees.user_id', $search)
              ->orWhere('device_employee.device_uid', $search);
        });
    }

    $sobrantes = $query->orderBy('device_name')
        ->orderBy('device_uid')
        ->paginate(25)
        ->withQueryString();

    return view('employees.sobrantes', [
        'sobrantes' => $sobrantes,
        'devices' => Device::orderBy('name')->get(),
        'stats' => $this->getSobranteStats(),
    ]);
}
```

---

## 6. Diseño de Modelos/Relaciones

### 6.1 Employee (sin cambios a relaciones existentes)

```php
// Relaciones existentes — se mantienen
public function devices(): BelongsToMany   // → pivote device_employee
public function fingerprints(): HasMany     // → fingerprints
public function attendances(): HasMany     // → attendances
public function syncs(): HasMany           // → device_syncs

// NUEVA relación
public function deviceEmployeeEntries(): HasMany
{
    return $this->hasMany(DeviceEmployee::class);
}

// NUEVO scope
public function scopeActivos($query)
{
    return $query->where('status_actual', 'A')->orWhereNull('status_actual');
}

public function scopeBajas($query)
{
    return $query->where('status_actual', 'B');
}
```

### 6.2 DeviceEmployee (pivote — sin cambios estructurales)

```php
// Relación inversa ya existe
public function employee(): BelongsTo
public function device(): BelongsTo

// NUEVO accessor
public function getSobranteTypeAttribute(): ?string
{
    if (!$this->employee) {
        return 'A'; // Huérfano
    }
    if ($this->employee->status_actual === 'B') {
        return 'B'; // Baja
    }
    return null; // No es sobrante
}

public function getSobranteReasonAttribute(): ?string
{
    if (!$this->employee) {
        return 'NO EXISTE EN CATÁLOGO';
    }
    if ($this->employee->status_actual === 'B') {
        return 'BAJA EN FIREBIRD';
    }
    return null;
}
```

### 6.3 Fingerprint (sin cambios)

```php
// Relaciones existentes — se mantienen
public function employee(): BelongsTo
public function device(): BelongsTo
```

### 6.4 Device (sin cambios a relaciones existentes)

```php
// Relaciones existentes — se mantienen
public function employees(): BelongsToMany
public function fingerprints(): HasMany
public function attendances(): HasMany
public function syncs(): HasMany
public function latestSync(): HasOne
```

### 6.5 Resumen de cambios en modelos

| Modelo | Cambio | Esfuerzo |
|--------|--------|----------|
| `Employee` | Agregar scopes `activos()`, `bajas()` | Bajo |
| `DeviceEmployee` | Agregar accessors `sobrante_type`, `sobrante_reason` | Bajo |
| `Fingerprint` | Sin cambios | — |
| `Device` | Sin cambios | — |

---

## 7. Diseño del Listado de Empleados

### 7.1 Estructura de columnas

```
| ID | Nombre | Departamento | Cargo | Estatus | Dispositivos | Huellas | Acciones |
```

### 7.2 Definición por columna

| Columna | Fuente | Formato | Ordenable |
|---------|--------|---------|-----------|
| **ID** | `employees.user_id` | `<code>1025</code>` | Sí |
| **Nombre** | `employees.name` | Avatar + nombre completo | Sí (`LOWER(name)`) |
| **Departamento** | `employees.departamento` | Texto | Sí |
| **Cargo** | `employees.cargo` | Texto | No |
| **Estatus** | `employees.status_actual` | Badge (Activo/Baja/Sin datos) | Sí |
| **Dispositivos** | Consolidación de `employees.devices` | Chips con nombre | No |
| **Huellas** | Consolidación de `fingerprints` | "8 huellas (D1: 5, D2: 3)" | No |
| **Acciones** | Buttons | Editar, Sync, Eliminar | No |

### 7.3 Consolidación de dispositivos (Blade)

```php
@php
    $deviceSummary = $employee->devices->map(fn($d) => $d->name)->implode(' · ');
    $fingerprintTotal = $employee->fingerprints->count();
    $fingerprintByDevice = $employee->fingerprints
        ->groupBy(fn($f) => $f->device?->name ?? 'Sin dispositivo')
        ->map(fn($group) => $group->count())
        ->implode(', ', fn($count, $name) => "{$name}: {$count}");
@endphp

<td data-label="Dispositivos">
    @if ($employee->devices->isEmpty())
        <span class="text-secondary-token">—</span>
    @else
        <span class="mono small">{{ $deviceSummary }}</span>
    @endif
</td>

<td data-label="Huellas">
    @if ($fingerprintTotal > 0)
        <span class="badge cat-green">{{ $fingerprintTotal }} huellas</span>
        @if ($fingerprintByDevice)
            <span class="text-muted small ms-1">{{ $fingerprintByDevice }}</span>
        @endif
    @else
        <span class="text-secondary-token">0</span>
    @endif
</td>
```

### 7.4 Estatus consolidado

```php
@php
    // Estado del empleado (no del enrolamiento)
    $statusClass = match($employee->status_actual) {
        'A' => 'cat-green',
        'B' => 'cat-red',
        default => 'cat-gray',
    };
    $statusLabel = $employee->status_actual_label; // accessor del modelo
@endphp

<td data-label="Estatus">
    <span class="badge badge-with-dot {{ $statusClass }}">{{ $statusLabel }}</span>
</td>
```

### 7.5 Acciones

```php
<td data-label="">
    <div class="table-row-actions justify-content-end">
        @if (auth()->user()->isAdmin())
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-ghost" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>
            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                  data-confirm data-confirm-danger
                  data-confirm-title="¿Quitar a {{ $employee->name }}?"
                  data-confirm-message="Se dará de baja en todos sus checadores.">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-icon-danger" title="Eliminar"><i class="bi bi-person-x"></i></button>
            </form>
        @else
            @foreach ($employee->devices as $device)
                <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-ghost" title="Ver {{ $device->name }}">
                    <i class="bi bi-eye"></i>
                </a>
            @endforeach
        @endif
    </div>
</td>
```

---

## 8. Diseño del Detalle del Empleados

### 8.1 Secciones

```
┌─────────────────────────────────────────────────┐
│  EMPLEADO                                       │
│  ┌───────────────────────────────────────────┐  │
│  │ Nombre: Juan Pérez                        │  │
│  │ ID: 1025                                  │  │
│  │ Departamento: Ventas                      │  │
│  │ Cargo: Ejecutivo                          │  │
│  │ Estatus: Activo (Firebird)                │  │
│  │ Fecha ingreso: 01/03/2024                 │  │
│  └───────────────────────────────────────────┘  │
│                                                 │
│  PRESENCIA EN DISPOSITIVOS (3 dispositivos)     │
│  ┌───────────────────────────────────────────┐  │
│  │ D1 · IP: 192.168.1.10 · Online           │  │
│  │   UID: 1025 · Tarjeta: 12345              │  │
│  │   Rol: Usuario · Huellas: 5               │  │
│  ├───────────────────────────────────────────┤  │
│  │ D2 · IP: 192.168.1.11 · Offline          │  │
│  │   UID: 501 · Tarjeta: —                   │  │
│  │   Rol: Supervisor · Huellas: 3            │  │
│  ├───────────────────────────────────────────┤  │
│  │ D3 · IP: 192.168.1.12 · Online           │  │
│  │   UID: 2001 · Tarjeta: —                  │  │
│  │   Rol: Usuario · Huellas: 0               │  │
│  └───────────────────────────────────────────┘  │
│                                                 │
│  HUELLAS (8 total)                              │
│  ┌───────────────────────────────────────────┐  │
│  │ D1 (5 huellas)                            │  │
│  │   Dedo 0: sha256...a1b2c3                 │  │
│  │   Dedo 1: sha256...d4e5f6                 │  │
│  │   ...                                     │  │
│  │ D2 (3 huellas)                            │  │
│  │   Dedo 0: sha256...g7h8i9                 │  │
│  │   ...                                     │  │
│  └───────────────────────────────────────────┘  │
│                                                 │
│  HISTORIAL DE SINCRONIZACIONES                   │
│  ┌───────────────────────────────────────────┐  │
│  │ Completed · D1 · sync_full · 09/09 12:00  │  │
│  │ Completed · D2 · sync_full · 09/09 11:30  │  │
│  │ Failed · D3 · sync_full · 09/09 10:00     │  │
│  └───────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
```

### 8.2 Eloquent loading (edit method)

```php
// EmployeeController::edit()
public function edit(Employee $employee): View
{
    $employee->load([
        'devices',                          // BelongsToMany con pivot completo
        'fingerprints' => fn ($q) => $q     // HasMany
            ->with('device:id,name')
            ->orderBy('finger'),
        'syncs' => fn ($q) => $q            // HasMany
            ->with('device:id,name')
            ->latest()
            ->limit(8),
    ]);

    // Para "Presencia en dispositivos" necesitamos info del pivote
    $enrollments = $employee->devices; // Ya trae pivot con withPivot()

    return view('employees.edit', compact('employee', 'enrollments'));
}
```

---

## 9. Diseño de Huellas

### 9.1 Representación

```
Total: 8 huellas

D1 (D1 · 192.168.1.10)
├── Dedo 0 · sha256...a1b2c3
├── Dedo 1 · sha256...d4e5f6
├── Dedo 2 · sha256...g7h8i9
├── Dedo 3 · sha256...j0k1l2
└── Dedo 4 · sha256...m3n4o5

D2 (D2 · 192.168.1.11)
├── Dedo 0 · sha256...p6q7r8
├── Dedo 1 · sha256...s9t0u1
└── Dedo 2 · sha256...v2w3x4
```

### 9.2 Consulta optimizada

```php
// Para el detalle del empleado
$fingerprints = Fingerprint::query()
    ->where('employee_id', $employee->id)
    ->with('device:id,name,ip')
    ->orderBy('device_id')
    ->orderBy('finger')
    ->get()
    ->groupBy(fn($f) => $f->device_id); // Agrupar por dispositivo
```

### 9.3 Blade para huellas agrupadas

```php
@php
    $fingerprintsByDevice = $employee->fingerprints
        ->groupBy(fn($f) => $f->device_id)
        ->map(fn($group) => $group->sortBy('finger'));
@endphp

@foreach ($fingerprintsByDevice as $deviceId => $fingerprints)
    @php $device = $fingerprints->first()->device; @endphp
    <div class="fingerprint-group">
        <h6>{{ $device?->name ?? 'Sin dispositivo' }}
            <span class="badge cat-gray">{{ $fingerprints->count() }} huellas</span>
        </h6>
        @foreach ($fingerprints as $fingerprint)
            <div class="fingerprint-row">
                <span>Dedo {{ $fingerprint->finger }}</span>
                <code>{{ substr($fingerprint->template_hash, 0, 12) }}...</code>
            </div>
        @endforeach
    </div>
@endforeach
```

---

## 10. Diseño de Sobrantes (Pestaña)

### 10.1 Tabs del listado de empleados

```
┌──────────────────────────────────────────────────────────────┐
│  Empleados                                                   │
│                                                              │
│  [Todos (150)] [Activos (142)] [Bajas (5)] [Sobrantes (8)]  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  (tabla de resultados)                               │    │
│  └──────────────────────────────────────────────────────┘    │
│                                                              │
│  Paginación: « 1 2 3 4 5 »                                  │
└──────────────────────────────────────────────────────────────┘
```

### 10.2 Rutas

```php
// routes/web.php
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/sobrantes', [EmployeeController::class, 'sobrantes'])->name('sobrantes');
    // ... resto de rutas existentes
});
```

### 10.3 Vista sobrantes.blade.php

```php
@extends('layouts.admin')

@section('title', 'Sobrantes en Dispositivos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Sobrantes en Dispositivos</h1>
        <p class="text-muted mb-0">Empleados en checadores que no existen en el catálogo o fueron dados de baja.</p>
    </div>
</div>

<!-- Filtros -->
<form id="sobranteFilters" class="filter-row mb-3">
    <div class="input-group" style="width:auto">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Buscar por nombre o ID...">
    </div>
    <select name="device_id" class="form-select" style="width:auto">
        <option value="">Todos los dispositivos</option>
        @foreach ($devices as $device)
            <option value="{{ $device->id }}" @selected(request('device_id') == $device->id)>{{ $device->name }}</option>
        @endforeach
    </select>
    <select name="type" class="form-select" style="width:auto">
        <option value="">Todos los tipos</option>
        <option value="A" @selected(request('type') == 'A')">No existe en catálogo</option>
        <option value="B" @selected(request('type') == 'B')">Baja en Firebird</option>
    </select>
    <button class="btn btn-ghost"><i class="bi bi-search"></i> Buscar</button>
</form>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-value">{{ $stats['total'] }}</div>
            <div class="kpi-label">Total sobrantes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-value text-warning">{{ $stats['type_a'] }}</div>
            <div class="kpi-label">Sin catálogo</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-value text-danger">{{ $stats['type_b'] }}</div>
            <div class="kpi-label">Bajas Firebird</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-value text-muted">{{ $stats['ignored'] }}</div>
            <div class="kpi-label">Ignorados</div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Motivo</th>
                        <th>Dispositivo</th>
                        <th>UID</th>
                        <th>Tarjeta</th>
                        <th>Huellas</th>
                        <th>Última sync</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sobrantes as $sobrante)
                        <tr>
                            <td><code>{{ $sobrante->user_id ?? '—' }}</code></td>
                            <td>{{ $sobrante->name ?? 'Desconocido' }}</td>
                            <td>
                                @if ($sobrante->sobrante_type === 'A')
                                    <span class="badge cat-yellow">NO EXISTE EN CATÁLOGO</span>
                                @else
                                    <span class="badge cat-red">BAJA EN FIREBIRD</span>
                                @endif
                            </td>
                            <td><a href="{{ route('devices.show', $sobrante->device_id) }}">{{ $sobrante->device_name }}</a></td>
                            <td><code>{{ $sobrante->device_uid }}</code></td>
                            <td>{{ $sobrante->card_number ?? '—' }}</td>
                            <td>{{ $sobrante->fingerprint_count }}</td>
                            <td>{{ $sobrante->updated_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <div class="table-row-actions">
                                    @if ($sobrante->employee_id)
                                        <a href="{{ route('employees.edit', $sobrante->employee_id) }}" class="btn btn-sm btn-ghost" title="Ver empleado">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('employees.sobrantes.ignore', $sobrante->pivot_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-ghost" title="Ignorar"><i class="bi bi-eye-slash"></i></button>
                                    </form>
                                    <form action="{{ route('employees.sobrantes.remove', $sobrante->pivot_id) }}" method="POST" class="d-inline"
                                          data-confirm data-confirm-danger
                                          data-confirm-title="¿Eliminar del dispositivo?"
                                          data-confirm-message="Se eliminará este enrolamiento del checador {{ $sobrante->device_name }}.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-icon-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-check-circle text-success fs-1"></i>
                                <p class="mt-2 mb-0">No hay sobrantes</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $sobrantes->links() }}</div>
@endsection
```

---

## 11. Diseño de Acciones

### 11.1 Ver Detalle

| Campo | Valor |
|-------|-------|
| **Entrada** | `GET /employees/{employee}` (si tiene employee_id) |
| **Controller** | `EmployeeController::edit` |
| **Service** | No aplica |
| **Job** | No aplica |
| **SDK** | No aplica |
| **Resultado** | Muestra vista edit del empleado |
| **Persistencia** | No modifica datos |
| **Auditoría** | No registra |

### 11.2 Eliminar del Dispositivo

| Campo | Valor |
|-------|-------|
| **Entrada** | `DELETE /sobrantes/{pivot_id}/remove` |
| **Controller** | `EmployeeController::sobrantesRemove` |
| **Service** | `ZktecoService::removeUser(device_uid)` |
| **Job** | `DeprovisionEmployeeJob` (reutilizar existente) |
| **SDK** | `$zk->removeUser($uid)` |
| **Resultado** | Elimina del hardware + detach pivot |
| **Persistencia** | `device_employee` se elimina (cascade) |
| **Auditoría** | `DeviceSync` con operation='deprovision' |

### 11.3 Marcar Ignorado

| Campo | Valor |
|-------|-------|
| **Entrada** | `POST /sobrantes/{pivot_id}/ignore` |
| **Controller** | `EmployeeController::sobrantesIgnore` |
| **Service** | No aplica |
| **Job** | No aplica |
| **SDK** | No aplica |
| **Resultado** | `device_employee.ignored_at = now()` |
| **Persistencia** | Update pivot |
| **Auditoría** | Log en `activity_log` (si existe) |

### 11.4 Resincronizar

| Campo | Valor |
|-------|-------|
| **Entrada** | `POST /sobrantes/{pivot_id}/resync` |
| **Controller** | `EmployeeController::sobrantesResync` |
| **Service** | `ZktecoService::syncUsers()` |
| **Job** | `SyncDeviceJob` con operation='users' |
| **SDK** | Descarga usuarios del dispositivo |
| **Resultado** | Re-vincula si el employee existe en catálogo |
| **Persistencia** | Actualiza/crea pivotes según corresponda |
| **Auditoría** | `DeviceSync` con operation='users' |

---

## 12. Diseño de Eliminación del Dispositivo

### 12.1 Orden transaccional

```
1. Confirmación administrativa (data-confirm dialog)
2. Verificar si el dispositivo está online
   ├── Online → ejecutar removeUser inmediatamente
   └── Offline → marcar como "pendiente de eliminación" y enqueue job
3. Ejecutar ZktecoService::removeUser(device_uid)
4. Verificar respuesta SDK ($result === true)
5. Si éxito → detach pivot (employee_id, device_id)
6. Si fallo → registrar error, NO detach pivot
7. Actualizar DeviceSync con resultado
8. Si employee queda sin dispositivos Y status='B' → marcar para revisión
```

### 12.2 Dispositivo offline

```php
// En sobrantesRemove()
if ($device->status !== 'online') {
    // No podemos eliminar del hardware si está offline
    return back()->with('error', 'El dispositivo está offline. No se puede eliminar del hardware.');
}
```

**Regla:** NO eliminar `device_employee` sin haber confirmado la eliminación en el dispositivo. Si el dispositivo está offline, la acción queda bloqueada con mensaje informativo.

---

## 13. Diseño de Ignorar

### 13.1 Comportamiento

```
ignored = oculto de alertas, no de la vista

Un sobrante ignorado:
- NO aparece en el badge de "Sobrantes" del dashboard
- SÍ aparece en la pestaña "Sobrantes" con filtro "Ignorados"
- SÍ puede ser revisado/revertido
- NO se elimina del dispositivo
```

### 13.2 Si el empleado vuelve a Firebird

```
Escenario:
1. Empleado 1025 dado de baja en Firebird (status='B')
2. Sobrante detectado → admin marca como "ignorado"
3. Firebird reactiva al empleado (status='A')
4. Próxima sincronización Firebird → employees.status_actual = 'A'
5. device_employee ya NO es sobrante → desaparece de la pestaña automáticamente
6. ignored_at se mantiene pero no afecta (no es sobrante)
```

**Conclusión:** No se necesita lógica especial para "reactivación". El cambio de `status_actual` de 'B' a 'A' en Firebird resuelve automáticamente el caso.

---

## 14. Diseño de Baja en Firebird

### 14.1 Flujo actual

```
Firebird STATUSACTUAL='B'
    ↓
CatalogSmartSync → employees.status_actual = 'B'
    ↓
employee sigue existiendo en MySQL
    ↓
device_employee sigue existiendo
    ↓
Sobrantes lo detecta como tipo B
    ↓
Administrador decide qué hacer
```

### 14.2 Flujo propuesto (sin cambio automático)

```
Firebird STATUSACTUAL='B'
    ↓
CatalogSmartSync → employees.status_actual = 'B'
    ↓
Próxima consulta a sobrantes → aparece como sobrante
    ↓
Notificación al admin: "Hay X empleados dados de baja con enrolamientos activos"
    ↓
Admin decide:
├── Eliminar del dispositivo → sobrantesRemove()
├── Ignorar → sobrantesIgnore()
└── Esperar → queda como sobrante pendiente
```

### 14.3 ¿Por qué NO automatizar ahora?

| Razón | Detalle |
|-------|---------|
| **Riesgo de pérdida de datos** | Si se elimina automáticamente y el empleado es reactivado en Firebird, se pierden las huellas |
| **Dispositivo puede estar offline** | No se puede garantizar la eliminación del hardware |
| **Auditoría incompleta** | DeviceSyncItem no está poblado, no hay trazabilidad completa |
| **Regla de negocio no definida** | El admin puede querer conservar el enrolamiento temporalmente |
| **Complejidad de rollback** | Si se elimina y luego se necesita revertir, hay que re-enrolar manualmente |

### 14.4 Futuro (fase posterior)

```
Posible automatización:
1. Firebird sync detecta status='B'
2. Despachar job: `DeprovisionBajaJob(employee_id)`
3. Job espera N días (configurable) antes de eliminar
4. Si employee vuelve a 'A' en ese período → cancelar job
5. Si persiste 'B' → eliminar de dispositivos
```

---

## 15. Diseño de Copia de Huellas

### 15.1 Flujo actual (auditoría)

```
Frontend → POST /employees/{employee}/fingerprints/{fingerprint}/copy
    ↓
EmployeeController (inline en edit view)
    ↓
ZktecoService::uploadFingerprint(employee, fingerprint)
    ↓
SDK → setFingerprint(device_uid, templates)
    ↓
Response: true/false
```

### 15.2 Problema detectado

El código actual **no verifica consistentemente** la respuesta del SDK antes de confirmar la copia. En algunos casos se marca como exitosa sin confirmación.

### 15.3 Diseño corregido

```php
// ZktecoService::uploadFingerprint() — verificación estricta
public function uploadFingerprint(Employee $employee, Fingerprint $fingerprint): bool
{
    // 1. Verificar que el empleado esté enrolado en ESTE dispositivo
    $enrollment = $this->device->employees()
        ->whereKey($employee->getKey())
        ->first();

    if (! $enrollment) {
        Log::warning('Empleado no enrolado en dispositivo destino', [
            'employee' => $employee->id,
            'device' => $this->device->id,
        ]);
        return false;
    }

    // 2. Conectar al dispositivo
    $zk = $this->boot();
    if (! $zk) {
        return false;
    }

    try {
        if (! $this->withRetries(fn () => $zk->connect(), 'uploadFingerprint:connect')) {
            return false;
        }

        // 3. Preparar template con header correcto
        $template = $this->forDevice(
            (string) $fingerprint->template,
            (int) $enrollment->pivot->device_uid,
            (int) $fingerprint->finger
        );

        if ($template === null) {
            $zk->disconnect();
            return false;
        }

        // 4. Enviar al dispositivo
        $result = $zk->setFingerprint(
            (int) $enrollment->pivot->device_uid,
            [(int) $fingerprint->finger => $template]
        );

        $zk->disconnect();

        // 5. VERIFICAR RESPUESTA SDK — aquí está la corrección
        $success = (int) $result === 1;

        if (! $success) {
            Log::warning('SDK rechazó huella', [
                'employee' => $employee->id,
                'device' => $this->device->id,
                'finger' => $fingerprint->finger,
                'result' => $result,
            ]);
        }

        return $success;

    } catch (Throwable $e) {
        Log::error('Error copiando huella', [
            'employee' => $employee->id,
            'device' => $this->device->id,
            'finger' => $fingerprint->finger,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
```

### 15.4 Escenarios de error

| Escenario | Comportamiento esperado |
|-----------|------------------------|
| Destino offline | `boot()` lanza `ZktecoConnectionException` → catch → return false |
| Huella incompatible | SDK rechaza → result !== 1 → return false, log warning |
| Employee inexistente en destino | `enrollment === null` → return false, log warning |
| UID incorrecto | `forDevice()` retorna null → return false |
| SDK devuelve false | `(int) $result !== 1` → return false, log warning |
| SDK lanza excepción | catch Throwable → return false, log error |

---

## 16. DeviceSync vs DeviceSyncItem

### 16.1 Estado actual

| Tabla | Uso actual | Población |
|-------|------------|-----------|
| `device_syncs` | ✅ Creada en cada operación | Poblada correctamente |
| `device_sync_items` | ❌ Modelo existe | **NO poblada por ninguna operación** |

### 16.2 Diseño propuesto

**Decisión: NO incluir DeviceSyncItem en esta implementación.**

**Justificación:**

1. **Alcance** — La tarea principal es reconciliación Firebird ↔ dispositivos, no auditoría granular
2. **Complejidad** — Poblar DeviceSyncItem requeriría modificar todos los jobs y services
3. **Valor inmediato** — DeviceSync ya da el nivel de auditoría suficiente para la UI actual
4. **Riesgo** — Cambiar la estructura de auditoría puede introducir regresiones

**DeviceSync actual cubre:**
- Qué operación se ejecutó (`operation`)
- En qué dispositivo (`device_id`)
- Cuándo (`created_at`, `started_at`, `finished_at`)
- Resultado (`status`, `error_message`)
- Contadores (`created_count`, `updated_count`)

**DeviceSyncItem agregaría:**
- Qué empleado específico falló
- Error particular por empleado
- Estado individual de cada empleado en un lote

**Recomendación:** Dejar DeviceSyncItem para una fase posterior cuando se necesite debugging granular de sincronizaciones masivas.

---

## 17. Operaciones ZKTeco (Matriz Actualizada)

| Operación | Controller | Service | Job | SDK | SyncLog | Retry | Offline |
|-----------|------------|---------|-----|-----|---------|-------|---------|
| **Subir empleado** | `EmployeeController::store` | `ZktecoService::setUser` | ❌ Sync | `setUser` | `DeviceSync` | ✅ `withRetries` | ⚠️ Retry en boot |
| **Copiar huella** | `EmployeeController` (edit view) | `ZktecoService::uploadFingerprint` | ❌ | `setFingerprint` | ❌ | ✅ `withRetries` | ✅ Boot detecta |
| **Eliminar usuario** | `FingerprintController::removeFromDevice` | `ZktecoService::removeUser` | ✅ `DeprovisionEmployeeJob` | `removeUser` | `DeviceSync` | ✅ backoff 60/300/900 | ✅ Boot detecta |
| **Eliminar huella** | `EmployeeController` (edit view) | `ZktecoService::removeFingerprint` | ❌ | `removeFingerprint` | ❌ | ✅ `withRetries` | ✅ Boot detecta |
| **Info equipo** | `DeviceController::show` | `ZktecoService::info` | ❌ | `deviceName`, `serialNumber`, etc. | ❌ | ✅ `withRetries` | ✅ Return [] |
| **Sincronizar hora** | `DeviceSyncController::setTime` | `ZktecoService::setTime` | ❌ | `setTime` | ❌ | ✅ `withRetries` | ✅ Boot detecta |
| **Descargar asistencias** | `DeviceSyncController::syncAttendances` | `SyncDeviceJob` → `ZktecoService::syncAttendances` | ✅ `SyncDeviceJob` | `getAttendances` | `DeviceSync` | ✅ backoff 30/120/300 | ✅ Estado offline |
| **Estatus marcación** | `Attendance` model | — | — | — | — | — | — |

---

## 18. Rendimiento

### 18.1 Estrategia para listado de empleados

**Problema:** `Employee::with('devices')` ejecuta 2 queries (employees + pivot). Con `fingerprints` serían 3. Para 150 empleados esto es aceptable.

**Solución actual (aceptable):**

```php
// EmployeeController::index()
$employees = Employee::query()
    ->with('devices')                          // 1 query eager load
    ->withCount('fingerprints')                // 1 query count
    ->orderByRaw('LOWER(name)')
    ->orderBy('id')
    ->paginate(25)
    ->withQueryString();
```

**Total queries para página de 25 empleados:**
1. `SELECT COUNT(*) FROM employees` (paginación)
2. `SELECT * FROM employees ORDER BY LOWER(name), id LIMIT 25` (datos)
3. `SELECT * FROM device_employee WHERE employee_id IN (25 ids)` (eager load devices)
4. `SELECT employee_id, COUNT(*) FROM fingerprints WHERE employee_id IN (25 ids) GROUP BY employee_id` (count)

**4 queries totales** — no es N+1, es aceptable.

### 18.2 Estrategia para sobrantes

**Problema:** La consulta UNION puede ser costosa si hay muchos device_employee.

**Solución:** Usar índices existentes + paginación:

```php
// Sobrantes: 2 queries UNION con paginación
// 1. COUNT para paginación
// 2. SELECT con LIMIT 25
// Ambas usan índices en device_employee.device_id y employees.id
```

### 18.3 Índices recomendados

```php
// Migración: agregar índice en status_actual
$table->index('status_actual', 'employees_status_actual_index');
```

### 18.4 Evitar N+1

| Escenario | Solución | Eager Loading |
|-----------|----------|---------------|
| Listado empleados | `with('devices')` | ✅ Ya implementado |
| Listado empleados (huellas) | `withCount('fingerprints')` | ✅ Ya implementado |
| Detalle empleado | `with(['devices', 'fingerprints.device', 'syncs.device'])` | ✅ Ya implementado |
| Sobrantes | Consulta con JOINs | ✅ Diseñado en §5 |

---

## 19. Migraciones Necesarias

### 19.1 Migración: Agregar ignored_at a device_employee

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_employee', function (Blueprint $table): void {
            $table->timestamp('ignored_at')->nullable()->after('fingerprint_count');
        });
    }

    public function down(): void
    {
        Schema::table('device_employee', function (Blueprint $table): void {
            $table->dropColumn('ignored_at');
        });
    }
};
```

### 19.2 Migración: Agregar índice en status_actual

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->index('status_actual', 'employees_status_actual_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_status_actual_index');
        });
    }
};
```

### 19.3 Resumen

| Migración | Tabla | Campo/Índice | Necesario | Justificación |
|-----------|-------|--------------|-----------|---------------|
| `ignored_at` | `device_employee` | `ignored_at TIMESTAMP NULL` | **SÍ** | Marcar sobrantes ignorados |
| `status_actual index` | `employees` | `INDEX (status_actual)` | **SÍ** | Performance en consulta de sobrantes tipo B |

---

## 20. Archivos a Modificar

### 20.1 Modelos

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `app/Models/Employee.php` | Agregar scopes `activos()`, `bajas()` | +8 |
| `app/Models/Pivots/DeviceEmployee.php` | Agregar accessors `sobrante_type`, `sobrante_reason` | +20 |

### 20.2 Controllers

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `app/Http/Controllers/EmployeeController.php` | Agregar métodos `sobrantes()`, `sobrantesRemove()`, `sobrantesIgnore()`, `sobrantesResync()` | +120 |

### 20.3 Rutas

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `routes/web.php` | Agregar rutas de sobrantes | +8 |

### 20.4 Vistas

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `resources/views/employees/index.blade.php` | Agregar tabs (Todos/Activos/Bajas/Sobrantes) | +40 |
| `resources/views/employees/sobrantes.blade.php` | **NUEVA** — vista completa de sobrantes | +200 |

### 20.5 Migraciones

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `database/migrations/2026_09_09_000001_add_ignored_at_to_device_employee.php` | **NUEVA** | +20 |
| `database/migrations/2026_09_09_000002_add_status_actual_index_to_employees.php` | **NUEVA** | +20 |

### 20.6 Repositorio (opcional)

| Archivo | Cambio | Líneas aprox. |
|---------|--------|---------------|
| `app/Repositories/SobranteRepository.php` | **NUEVO** — encapsula consulta de sobrantes | +100 |

### 20.7 Resumen de archivos

| Tipo | Cantidad | Archivos |
|------|----------|----------|
| Modificar existentes | 4 | Employee.php, DeviceEmployee.php, EmployeeController.php, web.php, index.blade.php |
| Crear nuevos | 4 | sobrantes.blade.php, SobranteRepository.php, 2 migraciones |

---

## 21. Pruebas

### 21.1 Empleado sin dispositivo

```
Given: Employee con id=1, user_id='1025', status_actual='A'
        No tiene device_employee
When:  GET /employees
Then:  1 fila, nombre visible, Dispositivos='—', Huellas=0
```

### 21.2 Empleado en D1

```
Given: Employee con id=1, user_id='1025'
        device_employee: device_id=1, device_uid=1025
When:  GET /employees
Then:  1 fila, Dispositivos='D1', Huellas=0
```

### 21.3 Empleado en D1+D2+D3

```
Given: Employee con id=1, user_id='1025'
        device_employee: (device_id=1, uid=1025), (device_id=2, uid=501), (device_id=3, uid=2001)
When:  GET /employees
Then:  1 fila, Dispositivos='D1 · D2 · D3'
        NUNCA 3 filas
```

### 21.4 Varias huellas en varios dispositivos

```
Given: Employee id=1
        fingerprints: (device_id=1, finger=0..4), (device_id=2, finger=0..2)
When:  GET /employees/1/edit
Then:  Total: 8 huellas
        D1: 5 huellas
        D2: 3 huellas
```

### 21.5 Empleado activo

```
Given: Employee id=1, status_actual='A'
        device_employee: device_id=1
When:  GET /employees/sobrantes
Then:  NO aparece como sobrante
```

### 21.6 Empleado dado de baja

```
Given: Employee id=1, status_actual='B'
        device_employee: device_id=1
When:  GET /employees/sobrantes
Then:  Aparece como sobrante tipo B, motivo='BAJA EN FIREBIRD'
```

### 21.7 Empleado inexistente

```
Given: device_employee: employee_id=999 (no existe en employees)
When:  GET /employees/sobrantes
Then:  Aparece como sobrante tipo A, motivo='NO EXISTE EN CATÁLOGO'
```

### 21.8 Sobrante ignorado

```
Given: device_employee: employee_id=1, ignored_at=now()
        Employee id=1, status_actual='B'
When:  GET /employees/sobrantes
Then:  NO aparece (excluido por ignored_at)
When:  GET /employees/sobrantes?show_ignored=1
Then:  SÍ aparece con indicador "Ignorado"
```

### 21.9 Sobrante eliminado

```
Given: device_employee: employee_id=1, device_id=1
When:  DELETE /employees/sobrantes/{pivot_id}/remove
        (dispositivo online)
Then:  removeUser ejecutado en dispositivo
        device_employee eliminado
        DeviceSync creado con status='completed'
```

### 21.10 Dispositivo offline

```
Given: device_employee: employee_id=1, device_id=1
        Device status='offline'
When:  DELETE /employees/sobrantes/{pivot_id}/remove
Then:  Error: "El dispositivo está offline"
        device_employee NO eliminado
        Sin cambios en hardware
```

### 21.11 SDK devuelve false

```
Given: ZktecoService::removeUser() retorna false
When:  DELETE /employees/sobrantes/{pivot_id}/remove
Then:  Error: "No se pudo eliminar del dispositivo"
        device_employee NO eliminado
        DeviceSync con status='failed'
```

### 21.12 SDK lanza excepción

```
Given: ZktecoService::removeUser() lanza ZktecoConnectionException
When:  DELETE /employees/sobrantes/{pivot_id}/remove
Then:  Error: "No se pudo conectar al dispositivo"
        device_employee NO eliminado
        Log de error registrado
```

### 21.13 Copiar huella exitosa

```
Given: Employee id=1, device_id=1 (origen), device_id=2 (destino)
        Fingerprint: employee_id=1, device_id=1, finger=0
When:  POST /employees/1/fingerprints/{id}/copy {device_id: 2}
Then:  uploadFingerprint retorna true
        Nueva fingerprint: employee_id=1, device_id=2, finger=0
        device_employee.fingerprint_count actualizado
```

### 21.14 Copiar huella fallida

```
Given: Employee id=1, device_id=1 (origen), device_id=2 (destino offline)
        Fingerprint: employee_id=1, device_id=1, finger=0
When:  POST /employees/1/fingerprints/{id}/copy {device_id: 2}
Then:  uploadFingerprint retorna false
        NO se crea fingerprint en destino
        Error mostrado al usuario
```

---

## 22. Criterios de Aceptación

| ID | Criterio | Verificación |
|----|----------|--------------|
| **AC-01** | Un empleado aparece una sola vez en el listado principal | `Employee::count()` === filas en tabla |
| **AC-02** | Los datos principales provienen de `employees` | Columnas ID, Nombre, Depto, Cargo, Estatus de `employees` |
| **AC-03** | Todos los dispositivos se muestran consolidados | "D1 · D2 · D3" no "D1", "D2", "D3" separados |
| **AC-04** | Las huellas se agrupan por dispositivo | Total + desglose por checador |
| **AC-05** | Empleado con `status_actual='B'` aparece como sobrante | Filtrado en pestaña Sobrantes |
| **AC-06** | `device_employee` sin `employee` aparece como sobrante | LEFT JOIN detecta NULL |
| **AC-07** | No se elimina automáticamente ningún sobrante | Sin DELETE automático en ningún flujo |
| **AC-08** | Eliminar del dispositivo requiere acción explícita | Botón + confirmación + verificación SDK |
| **AC-09** | La respuesta del SDK debe confirmarse antes de marcar éxito | `(int) $result === count($templates)` |
| **AC-10** | Operaciones fallidas no dejan inconsistencias locales | Rollback: no detach si falla hardware |
| **AC-11** | El listado no genera N+1 evidente | Máximo 4 queries por carga |
| **AC-12** | La auditoría permite determinar qué ocurrió con cada operación | `DeviceSync` con status, error_message, timestamps |

---

## 23. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| **Consulta UNION lenta con muchos datos** | Baja | Medio | Índices existentes + paginación 25 |
| **Cambio de comportamiento en EmployeeController::index** | Media | Alto | Mantener lógica actual; agregar tabs sin cambiar consulta base |
| **DeviceEmployee puede quedarse huérfano** | Baja | Medio | Caso A detectado por sobrantes; manual limpieza |
| **ZktecoService::removeUser no verifica employee** | Media | Alto | Verificar enrollment antes de llamar SDK |
| **Dispositivo offline bloquea eliminación** | Alta | Medio | Mensaje informativo; no force delete |
| **Ignorar no tiene reversa automática** | Baja | Bajo | Revertir es manual (quitar ignored_at) |
| **Migración ignored_at en tabla grande** | Baja | Bajo | ALTER TABLE rápido en MySQL |

---

## 24. Decisiones Pendientes

| # | Decisión | Estado | Propuesta |
|---|----------|--------|-----------|
| 1 | ¿Automatizar deprovisionamiento en baja? | **PENDIENTE** | NO en esta fase; documentar como futuro |
| 2 | ¿Usar tabla separada para ignorados? | **PENDIENTE** | NO; usar campo `ignored_at` en pivote |
| 3 | ¿Poblar DeviceSyncItem? | **PENDIENTE** | NO en esta fase; dejar para auditoría granular futura |
| 4 | ¿Crear `employees.firebird_last_seen`? | **PENDIENTE** | NO; usar `updated_at` + FirebirdSyncItem |
| 5 | ¿Agregar `device_employee.last_synced_at`? | **PENDIENTE** | NO; usar `updated_at` |

---

## 25. Orden Exacto de Implementación

### Paso 1: Migraciones
1. Crear migración `add_ignored_at_to_device_employee`
2. Crear migración `add_status_actual_index_to_employees`
3. Ejecutar `php artisan migrate`

### Paso 2: Modelos
1. Agregar scopes `activos()`, `bajas()` a `Employee`
2. Agregar accessors `sobrante_type`, `sobrante_reason` a `DeviceEmployee`

### Paso 3: Repositorio
1. Crear `SobranteRepository.php` con consulta principal
2. Crear métodos para estadísticas (total, type_a, type_b, ignored)

### Paso 4: Rutas
1. Agregar `GET /employees/sobrantes` → `EmployeeController::sobrantes`
2. Agregar `POST /employees/sobrantes/{pivot_id}/ignore`
3. Agregar `DELETE /employees/sobrantes/{pivot_id}/remove`
4. Agregar `POST /employees/sobrantes/{pivot_id}/resync`

### Paso 5: Controller
1. Implementar `EmployeeController::sobrantes()` con filtros
2. Implementar `EmployeeController::sobrantesRemove()` con verificación SDK
3. Implementar `EmployeeController::sobrantesIgnore()` simple
4. Implementar `EmployeeController::sobrantesResync()` con job

### Paso 6: Vista Sobrantes
1. Crear `resources/views/employees/sobrantes.blade.php`
2. Implementar tabla con columnas diseñadas
3. Agregar filtros (dispositivo, tipo, búsqueda)
4. Agregar estadísticas (kpi cards)
5. Agregar acciones (ver, ignorar, eliminar)

### Paso 7: Vista Index (tabs)
1. Modificar `resources/views/employees/index.blade.php`
2. Agregar tabs: Todos, Activos, Bajas, Sobrantes
3. Agregar conteos en cada tab

### Paso 8: Rate Limiting
1. Agregar `throttle:30,1` a rutas de dispositivos POST
2. Agregar `throttle:30,1` a rutas de empleados POST

### Paso 9: Corrección Copia Huellas
1. Revisar `ZktecoService::uploadFingerprint()` verificación de respuesta
2. Agregar logging detallado en caso de fallo

### Paso 10: Pruebas
1. Ejecutar pruebas definidas en §21
2. Verificar AC-01 a AC-12

### Paso 11: Checkpoint Git
1. Commit incremental por cada paso
2. Tag final con versión

---

*Fin del Diseño Técnico*
