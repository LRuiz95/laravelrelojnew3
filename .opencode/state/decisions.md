# Decisiones de Arquitectura — TASK-20260907-separar-controllers

## 2026-09-07: Extracción de FingerprintController y DeviceSyncController

### Contexto
EmployeeController (567 líneas) y DeviceController (526 líneas) violan el principio de responsabilidad única al mezclar CRUD básico con operaciones de sincronización biométrica y de dispositivos.

### Decisión
Extraer dos controllers nuevos mediante refactor mínimo:
- **FingerprintController**: operaciones sobre plantillas de huella (assign, copy, delete, upload, remove)
- **DeviceSyncController**: operaciones de sincronización con dispositivos ZKTeco (sync users/fingerprints/attendances/all, setTime, clearAttendance, restore)

### Alternativas consideradas
1. **Service classes** — Rechazado: añade indirección sin necesidad; los controllers ya son delgados en lógica de negocio (delegan a ZktecoService y Jobs).
2. **Mantener status quo** — Rechazado: dificulta testing, onboarding y mantenimiento.
3. **Extraer a traits** — Rechazado: no resuelve el problema de rutas ni testing aislado.

### Consecuencias
- **Positivas**: Controllers más pequeños y enfocados; testing aislado por responsabilidad; rutas más semánticas (`/fingerprints/*`, `/devices/*/sync-*`).
- **Negativas**: Más archivos; routes/web.php crece ligeramente; `queueSync` duplicado o compartido (ver nota técnica).

### Nota técnica: `queueSync`
`queueSync` es `protected` en DeviceController y usado internamente por los métodos de sync. Al mover esos métodos a DeviceSyncController, `queueSync` debe moverse también (como `private` o `protected`). DeviceController NO lo necesita tras el refactor (sus métodos restantes: progress, syncStatus, refreshData, checkStatus, syncNow, deduplicate no lo usan).

### Testing
Nivel 3 asignado: toca integración ZKTeco, DB (DeviceSync, Fingerprint, Employee, Device), middleware auth/admin/throttle. Requiere suite relevante amplia.

### Rollback
Documentado en `state/plan.md`.

---

## 2026-09-08: Fix Device Sync Queue & Controller Stubs (TASK-2026-09-08-device-sync-fix)

### Contexto
Tres problemas bloquean la sincronización Devices ↔ ZKTeco:
1. **CRÍTICO**: `SyncDeviceJob` sin propiedad `$queue` → va a cola `default`; worker escucha `device-sync`.
2. **MEDIUM**: `DeviceSyncController::syncUsers|syncFingerprints|syncAttendances` son stubs que retornan fake JSON.
3. **INFO**: BD vacía (estado inicial esperado).

### Decisiones

#### 1. Cola dedicada `device-sync` (mantener arquitectura actual)
- **Decisión**: Agregar `public string $queue = 'device-sync';` a `SyncDeviceJob`.
- **Razón**: Aislar jobs de sincronización de dispositivos de otros jobs del sistema (emails, notificaciones, etc.). Worker dedicado `--queue=device-sync` permite escalar/monitorear independiente.
- **Alternativa rechazada**: Cambiar worker a `--queue=default` — rompe aislamiento y mezclaría prioridades.

#### 2. Patrón consistente para todos los botones de sync
- **Decisión**: `syncUsers`, `syncFingerprints`, `syncAttendances` usan mismo flujo que `syncAll`:
  1. Crear `DeviceSync` con `operation` correspondiente
  2. `SyncDeviceJob::dispatch($device, $sync, $operation)`
  3. Retornar `['status' => 'queued', 'sync_id' => $sync->id, 'operation' => '...']`
- **Razón**: 
  - `SyncDeviceJob` ya soporta `operation` = `'users' | 'fingerprints' | 'attendances' | 'all'` (líneas 82-119)
  - JS de vista ya hace polling genérico a `sync-status` — compatible sin cambios
  - Evita duplicar lógica de progreso, error handling, `WithoutOverlapping`

#### 3. Import `Illuminate\Http\Request` en DeviceSyncController
- **Decisión**: Agregar `use Illuminate\Http\Request;` y type-hint `Request $request` en los 3 métodos.
- **Razón**: Corrige bug actual (parámetro sin type-hint) y sigue convención Laravel/PSR-12.

#### 4. Sin cambios en ZktecoService ni Vista
- **Decisión**: `ZktecoService` ya tiene `syncUsers()`, `syncAttendances()`, `syncFingerprints()` funcionales. Vista JS ya maneja polling y progress bars.
- **Razón**: Cambio mínimo — solo conectar controller → job → service existente.

### Consecuencias
- **Positivas**: Fix mínimo (< 30 líneas totales), reutiliza arquitectura existente, zero breaking changes.
- **Negativas**: Requiere worker `--queue=device-sync` corriendo (ya está activo PID 984).

### Testing
Nivel 4 (suite completa) — ver `plan.md` justificación.

### Rollback
Ver `plan.md` sección "Rollback".