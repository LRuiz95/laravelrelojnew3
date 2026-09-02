# Flujo de Trabajo Obligatorio (Laravel 10)

Sigue este orden en cada tarea, sin saltarte pasos.

## Paso 1 — Diagnóstico
* Lee el código relevante completo, no fragmentos aislados.
* Identifica errores reales (sintaxis, lógica, N+1 queries, validaciones faltantes, manejo de errores ausente, seguridad).
* Lista los problemas por severidad: crítico / importante / menor.

## Paso 2 — Plan
* Explica en 2-5 líneas qué vas a cambiar y por qué.
* Señala si el cambio puede afectar otras partes del sistema (rutas, vistas, jobs, otros servicios que consuman lo mismo).

## Paso 3 — Implementación
* Cambio más pequeño posible que resuelva el problema correctamente.
* No refactorices código no relacionado con la tarea salvo que se pida explícitamente.
* Sigue las convenciones ya usadas en el proyecto (Eloquent, Form Requests, Policies, Service classes) — no introduzcas un patrón nuevo si ya existe uno establecido para el mismo propósito.

## Paso 4 — Verificación
* Revisa que no queden referencias rotas (imports, rutas, vistas, variables, nombres de columnas usados en Blade/componentes).
* Si hay tests, ejecútalos. Si no hay, dilo y sugiere uno mínimo para el cambio hecho (sin obligar a implementarlo).
* Declara explícitamente: "esto no debería afectar a X" o "esto podría afectar a X, revisar manualmente".

## Paso 5 — Resumen final
* Archivos modificados.
* Problemas corregidos.
* Riesgos o pendientes fuera del alcance de esta tarea.

## Qué NO hacer nunca
* No elimines código "porque parece no usarse" sin confirmar ausencia de referencias en todo el repo.
* No cambies rutas, columnas de BD o parámetros de API sin actualizar todos los usos.
* No asumas versión de Laravel/PHP/paquete ZKTeco — verifica en `composer.json`.
* No generes código de conexión al checador sin manejo de errores de red (ver `docs/zkteco.md`).
* No declares "arreglado" sin haber verificado el resultado (leyendo el código final, no solo confiando en que el cambio "debería" funcionar).
