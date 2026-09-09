# Decisiones de Arquitectura — TASK-2026-09-09-firebird-queue-fix

## ADR-001: Estrategia de ejecución de colas en XAMPP sin Supervisor

**Fecha**: 2026-09-09  
**Estado**: Aceptada  
**Contexto**: `.env` usa `QUEUE_CONNECTION=database` (config/queue.php líneas 37-43). No hay `queue:work` corriendo. `FirebirdSyncJob` (líneas 17-22) implementa `ShouldQueue` con `tries=3`, `timeout=3600`, middleware `WithoutOverlapping`. El usuario en Windows XAMPP no lanza workers manualmente.

**Decisión**: Enfoque híbrido (Opción C del plan):
- Mantener `ShouldQueue` + `dispatch()` async como default (producción con Horizon/Supervisor).
- Añadir `FirebirdSyncJob::dispatchNow()` que usa `dispatchSync()` **solo cuando se invoca explícitamente desde botón "Ejecutar ahora"**.
- Para ETL largo (1h), el botón "Ejecutar ahora" lanza `Artisan::call('queue:work', ['--once'=>true, '--stop-when-empty'=>true])` en proceso CLI separado, no `dispatchSync()` directo.
- Health-check `hasWorker()` en `FirebirdController` detecta worker vivo (tabla `jobs` con `reserved_at` < 2 min).

**Justificación**:
- Garantiza resolución en XAMPP sin acción manual recurrente.
- No rompe producción: worker real sigue procesando async normal.
- `Artisan::call queue:work --once` respeta `after_commit`, `retry_after`, `WithoutOverlapping`, y corre sin timeout web.
- Cambio mínimo: ~40 líneas en Job + Controller + Vista + Route.

**Alternativas rechazadas**:
- A) Solo documentar worker: no resuelve "nunca resuelve".
- B) `dispatchSync()` directo: bloquea request HTTP 1h, timeout Apache/PHP, rompe UX.

**Consecuencias**:
- Nuevo endpoint `POST /firebird/execute-pending` (admin middleware).
- Vista `/firebird` muestra banner condicional + botón.
- `FirebirdSyncJob` gana método estático `dispatchNow(FirebirdSync $sync, ...)`.
- Requiere test Nivel 3 (queue, BD, sincronización).

---

## ADR-002: Unificación de `/sync-queue` sin mergear dominios

**Fecha**: 2026-09-09  
**Estado**: Aceptada  
**Contexto**: `DeviceSync` (ZKTeco) y `FirebirdSync` (ETL Legacy) son agregados distintos con tablas, estados y ciclos de vida diferentes. `OperationsController@queue` y `@queueData` (líneas 17-74) solo manejan `DeviceSync`. Usuario pide "mesclarlo en /sync-queue para que muestre todo lo que hay en la cola".

**Decisión**: Un solo endpoint combinado `operations.queue.data.unified` + UI combinada en `operations/queue.blade.php`.

**Estructura JSON normalizada**:
```php
// FirebirdSync → normalized
[
  'id' => $sync->id,
  'type' => 'firebird',
  'device' => $sync->operation_label, // o tabla principal
  'operation' => $sync->operation,
  'operation_label' => $sync->operationLabel,
  'status' => $sync->status,
  'stage' => $sync->stage,
  'processed' => $sync->processed,
  'total' => $sync->total,
  'created' => $sync->created_count,
  'updated' => $sync->updated_count,
  'error' => $sync->error_message,
  'cancel_url' => route('firebird.cancel', $sync), // NUEVO route
  'retry_url' => $sync->status === 'failed' ? route('firebird.retry', $sync) : null, // NUEVO route
  'delete_url' => route('firebird.delete', $sync), // NUEVO route
  'items' => $sync->items->map(...),
  'created_at' => $sync->created_at->format('d/m/Y H:i'),
]

// DeviceSync → normalized (ya existe en queueData líneas 52-72)
```

**Vista**: Tabla única con columna "Tipo" (badge `firebird`/`device`). Filtros: Estado, Operación, **Tipo**. Acciones por fila delegan a controlador correspondiente.

**Rutas nuevas (admin)**:
- `POST /firebird/{sync}/cancel` → `FirebirdController@cancel`
- `POST /firebird/{sync}/retry` → `FirebirdController@retry` (re-dispatch mismo job)
- `DELETE /firebird/{sync}` → `FirebirdController@delete`

**Justificación**:
- Un solo lugar para ver "todo lo que hay en la cola" (petición usuario).
- Dominios separados en backend: cada controlador maneja su agregado.
- Reutiliza `DeviceSync` actions existentes; añade equivalentes para `FirebirdSync`.
- Testing Nivel 3 (integración dos agregados).

**Alternativas rechazadas**:
- Dos endpoints + UI con tabs: más complejo, duplica lógica polling/render.
- Mergear modelos/tablas: rompe dominio, migración riesgosa, no pedido.

---

## ADR-003: Notificaciones FirebirdSync en AdminLayoutComposer

**Fecha**: 2026-09-09  
**Estado**: Aceptada  
**Contexto**: `AdminLayoutComposer@notifications` (líneas 34-128) construye notificaciones para `Device`, `DeviceSync`, `Attendance`, `Employee`, `Fingerprint`. **No incluye `FirebirdSync`**.

**Decisión**: Añadir 3 reglas en `notifications()`:

1. **Pending > 5 min sin worker** (warning):
   ```php
   $stuckPending = FirebirdSync::where('status', 'pending')
       ->where('created_at', '<', now()->subMinutes(5))
       ->count();
   if ($stuckPending > 0) { /* item warning con link a /firebird */ }
   ```

2. **Failed** (danger):
   ```php
   $failedCount = FirebirdSync::where('status', 'failed')
       ->where('updated_at', '>=', now()->subDay())
       ->count();
   if ($failedCount > 0) { /* item danger */ }
   ```

3. **Completed con cambios** (success):
   ```php
   $lastCompleted = FirebirdSync::where('status', 'completed')
       ->where('created_count', '>', 0)
       ->orWhere('updated_count', '>', 0)
       ->latest()->first();
   if ($lastCompleted) { /* item success */ }
   ```

**Justificación**:
- Visibilidad global sin ir a `/firebird` ni `/sync-queue`.
- Coherente con notificaciones existentes de `DeviceSync` (líneas 50-61, 102-114).
- Nivel de testing 2 (Composer + Vista).

---

## ADR-004: Health-check de worker para banner condicional

**Fecha**: 2026-09-09  
**Estado**: Aceptada  
**Contexto**: Vista `/firebird` (líneas 85-103) muestra banner `$runningSync` con auto-reload 5s. Pero si no hay worker, nunca hay `running`.

**Decisión**: Método `hasWorker()` en `FirebirdController` (o trait/servicio):
```php
protected function hasWorker(): bool
{
    // Opción 1: job reservado recientemente
    $recentReserved = DB::table('jobs')
        ->where('queue', 'default')
        ->whereNotNull('reserved_at')
        ->where('reserved_at', '>', now()->subMinutes(2))
        ->exists();
    
    // Opción 2: heartbeat en cache (requiere worker que lo escriba)
    // $heartbeat = Cache::get('queue:worker:heartbeat');
    
    return $recentReserved;
}
```
Banner en vista: `if (!hasWorker() && $pendingCount > 0) { /* mostrar botón */ }`

**Justificación**:
- Simple, sin infra extra (Opción 1).
- Detecta worker real procesando, no solo "existe proceso".
- Si falsos negativos → botón aparece innecesario (seguro), no al revés.

---

## ADR-005: Niveles de testing asignados

| Fase | Nivel | Justificación |
|------|-------|---------------|
| 1. Fix crítico queue | **3** | Toca BD (jobs, firebird_syncs), queue (dispatch, worker), sincronización Firebird→MySQL. Requiere suite relevante amplia. |
| 2. UI feedback polling | **2** | JS/Blade/Controller aislado. Tests específicos + relacionados. |
| 3. Unificación /sync-queue | **3** | Integración dos agregados (FirebirdSync + DeviceSync), queue data endpoint combinado. |
| 4. Notificaciones | **2** | Composer + Vista. Tests específicos. |

**Escalamiento automático**: Si `tester` detecta que Fase 1 afecta más de lo declarado (ej. migración schema), escalar a Nivel 4 y notificar en `state/test-results.md`.

---

## ADR-006: Seguridad y Human Approval

**Severidad anticipada**: **MEDIUM** (endpoint `execute-pending` admin, `Artisan::call` en web request).

**Riesgos**:
- `Artisan::call('queue:work')` expone capacidad de procesar colas vía HTTP admin.
- Mitigación: middleware `admin` + rate limit + CSRF + log de auditoría.

**Human Approval**: **NO** (no es CRITICAL, no toca auth/pagos/migraciones schema). Pero `security` + `reviewer` obligatorios (MEDIUM).

**Stop condition**: Si `security` encuentra CRITICAL (ej. command injection en `Artisan::call`), escalar a humano inmediatamente.