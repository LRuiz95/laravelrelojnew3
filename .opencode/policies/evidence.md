# Evidencia estructurada

Ningún agente cierra su parte con una afirmación sin respaldo ("parece que
funciona", "debería estar bien"). Todo resultado que se escribe en
`.opencode/state/*.md` sigue este formato:

```
RESULT
Status: PASS | FAIL | PARTIAL | BLOCKED

Evidence:
- <comando ejecutado o verificación concreta>
- <resultado exacto — no "funcionó", sino el output real>

Changed:
- <archivo 1>
- <archivo 2>

Generated (temporal):
- <archivo temporal/scratch creado, ej. script de diagnóstico, dump,
  export puntual — este NO va en Changed>, o "none"
  (si hay alguno, además agrégalo a `.opencode/state/generated-files.md`
  para que `cleanup` lo pueda procesar al cerrar la tarea)

Confidence: high | medium | low
  (si es low, indicar la razón — ver policies/models.md, Nivel B)

Risks:
- <riesgo detectado, o "none">

Follow-up:
- <qué falta o qué debería revisarse después, o "none">
```

## Ejemplo real (tester)

```
RESULT
Status: PASS

Evidence:
- php artisan test --filter AttendanceSyncTest
- 14 passed, 0 failed

Changed:
- app/Services/AttendanceSyncService.php
- tests/Feature/AttendanceSyncTest.php

Generated (temporal):
- none

Confidence: high

Risks:
- none

Follow-up:
- none
```

## Ejemplo real (security, con baja confianza)

```
RESULT
Status: PARTIAL

Evidence:
- Revisé AttendanceSyncService.php contra la checklist completa.
- No pude confirmar cómo se maneja el timeout de la conexión a
  Firebird porque el código de reintentos vive en un archivo de
  configuración que no tuve en contexto.

Changed:
- (ninguno, solo lectura)

Generated (temporal):
- none

Confidence: low — falta ver la configuración de retry/timeout de
  Firebird para confirmar si hay riesgo de duplicación en sync.

Risks:
- Posible: sync duplicado si el timeout no maneja idempotencia.

Follow-up:
- Revisar config/firebird.php y el manejo de retry antes de aprobar.
```

Este segundo caso es exactamente el tipo de señal que activa el
Nivel B de `policies/models.md` — `team-lead` decide si reintenta con más
contexto o si esto ya amerita revisión humana.
