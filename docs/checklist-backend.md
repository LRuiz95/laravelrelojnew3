# Checklist de errores y optimización — Backend

Usa esta lista al revisar código o al responder "busca errores/optimizaciones". Ordena siempre los hallazgos por severidad: crítico → importante → menor.

## Base de datos y queries
* [ ] Queries N+1 (falta `with()`/eager loading).
* [ ] `DB::raw` o queries sin protección contra inyección SQL.
* [ ] Migraciones con tipos de columna inconsistentes (ej. fechas como string, montos como float en vez de decimal).
* [ ] Índices faltantes en columnas usadas en `WHERE`/`JOIN` frecuentes (`employee_id`, `fecha`, `device_serial`).
* [ ] Falta de índice único donde el negocio lo requiere (ej. `employee_id`+`timestamp`+`device_id` en asistencias, ver `docs/zkteco.md`).
* [ ] Transacciones de BD ausentes en operaciones multi-tabla que deben ser atómicas (ej. crear empleado + asignarlo a varios dispositivos).

## Pivotes BelongsToMany y whereHas (regla dura — bug real ocurrido)
* [ ] Dentro de un closure de `whereHas()`/`orWhereHas()`, el parámetro inyectado es el **Builder del modelo relacionado**, NO la relación `BelongsToMany`: `wherePivot()` no existe ahí. El dynamic-where lo interpreta como `where('pivot', ...)` → MySQL lanza error 1054 en runtime, y SQLite lo ejecuta sin quejarse devolviendo 0 filas (bug silencioso).
    * ❌ `Employee::whereHas('devices', fn ($q) => $q->wherePivot('active', true))`
    * ✅ `Employee::whereHas('devices', fn ($q) => $q->where('device_employee.active', true))` — calificar siempre `tabla_pivote.columna`.
* [ ] `wherePivot()`/`wherePivotIn()`/etc. solo son válidos sobre el objeto relación mismo: `$employee->devices()->wherePivot(...)`, o dentro de `with(['devices' => fn ($q) => $q->wherePivot(...)])` — ahí `$q` sí es `BelongsToMany`.

## Paridad de drivers (tests = producción)
* [ ] Los feature tests corren sobre **MySQL/MariaDB** (`phpunit.xml` → BD exclusiva `rh_reloj_testing`, que `migrate:fresh` vacía en cada proceso). NUNCA re-apuntar esa variable a la BD de datos reales ni volver a `sqlite :memory:` sin decisión explícita del equipo: SQLite tolera SQL inválida que MySQL rechaza (ver regla anterior) y oculta bugs hasta producción.
* [ ] Migraciones nuevas deben pasar en el driver de producción; si usan ramas por driver (`DB::getDriverName()`), verificar que ambas converjan al MISMO esquema final (columnas, tipos, índices).
* [ ] Un test de render contra BD vacía NO ejercita hidratación de relaciones: los eager loads (`with()`) se resuelven por fila al hidratar, así que una relación inexistente o SQL inválida dentro del eager load pasa silenciosa en test y explota en producción (bug real: composer con `Employee::with('device')` tras eliminar esa relación). Toda feature test que renderice vistas debe sembrar al menos una fila por modelo involucrado — ver `DashboardRenderTest::seedRenderData()` como patrón.
* [ ] Al barrer referencias legadas, cubrir TODO `app/**` (Composers, Observers, Jobs, Rules, Services), no solo controladores y vistas — los view composers corren en cada request del layout y quedan fuera de los greps acotados a `Http/`.

## Validación y seguridad
* [ ] Falta de validación en Form Requests o controladores.
* [ ] Rutas sin middleware de autenticación/autorización donde deberían tenerlo.
* [ ] Datos sensibles (templates de huella, PIN, RFID) expuestos en respuestas JSON sin necesidad, o logueados en texto plano.
* [ ] Credenciales o configuración sensible hardcodeada en vez de usar `.env`.
* [ ] Falta de rate limiting en endpoints públicos o de autenticación.
* [ ] Mass assignment sin `$fillable`/`$guarded` correctamente definido en modelos Eloquent.

## Manejo de errores y resiliencia
* [ ] Excepciones no capturadas, especialmente en la integración ZKTeco.
* [ ] `catch (\Throwable)` o `catch (\Exception)` genérico que oculta el tipo real de fallo, en vez de capturar excepciones específicas cuando el SDK las expone.
* [ ] Jobs/colas sin manejo de reintentos o fallos (`failed()`, `tries`, `backoff`).
* [ ] Falta de logs útiles en puntos críticos (sincronización, fallos de conexión, importaciones masivas).

## Arquitectura y mantenibilidad
* [ ] Código duplicado que debería vivir en un Service o Trait.
* [ ] Lógica de negocio no trivial dentro del controlador en vez de una Service class.
* [ ] Falta de tipado en parámetros/retornos de métodos nuevos o modificados.
* [ ] Inconsistencia en casts: el proyecto usa `$casts` property (estilo Laravel 10) de forma consistente. Si se usa `casts()` method (estilo Laravel 11), verificar que ambos estilos no convivan en el mismo proyecto sin razón.

## Frontend (cuando la tarea lo toque)
* [ ] Estados de carga/vacío ausentes en vistas que consumen datos async.
* [ ] Clases `dark:` faltantes en componentes nuevos o modificados (ver `docs/diseno.md`).
* [ ] Peticiones sin `debounce` en inputs de búsqueda/filtro que disparan requests al servidor.

## Al reportar
* [ ] Evita falsos positivos: no reportes algo como error sin haber verificado que realmente ocurre en el código, no solo que "parece" un antipatrón.
* [ ] Cuando el hallazgo es una mejora de performance (no un bug), acláralo explícitamente como "optimización" y no como "error", y estima el impacto si es posible (ej. "evita N+1 en tabla con miles de registros").
