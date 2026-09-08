# Task Boundary

Obligatorio para tareas COMPLEJAS antes de que cualquier especialista
escriba código. Opcional pero recomendado para tareas SIMPLE. Lo produce
`architect` (COMPLEJA) o `team-lead` (SIMPLE) y se guarda en
`.opencode/state/current-task.md`.

## Formato

```
TASK-<AAAA>-<NNNN>

Scope:
  <descripción breve de qué incluye y qué NO incluye la tarea>

Test level: 0-4 (ver policies/test-levels.md)

Allowed files:
  - <archivo o patrón 1>
  - <archivo o patrón 2>

Forbidden files:
  - <rutas sensibles que este task NO puede tocar, incluso si el agente
    técnicamente tendría permiso de escritura ahí en otro contexto>

Agents involved:
  - <lista de agentes que architect asignó, con qué archivo cada uno>

Expected outputs:
  - <qué se espera que exista al final: código, tests, migración, etc.>

Requires security: sí/no (y severidad esperada si se puede anticipar)
Requires human approval: sí/no (ver policies/permissions.md, rutas
  sensibles, y stop-conditions.md)
```

## Ejemplo

```
TASK-2026-0912

Scope:
  Corregir bug: la sincronización de asistencia desde ZKTeco duplica
  registros cuando el dispositivo reintenta un ponche que ya se
  sincronizó exitosamente. No incluye cambios de UI ni de reportes.

Test level: 3

Allowed files:
  - app/Services/AttendanceSyncService.php
  - app/Jobs/SyncAttendanceJob.php
  - tests/Feature/AttendanceSyncTest.php

Forbidden files:
  - database/migrations/*
  - .env
  - config/auth.php
  - resources/css/*

Agents involved:
  - integration → revisa idempotencia del payload de ZKTeco
  - data-integrity → revisa si ya existen duplicados en producción
  - laravel → implementa la corrección de idempotencia
  - tester → test que reproduce la duplicación antes del fix

Expected outputs:
  - Fix con manejo idempotente del reintento de ZKTeco.
  - Test que reproduce la duplicación y confirma que ya no ocurre.
  - Reporte de data-integrity sobre duplicados existentes (si los hay,
    no se corrigen en esta tarea, solo se documentan).

Requires security: no (no es de auth/pagos)
Requires human approval: no, salvo que data-integrity encuentre
  duplicados masivos ya en producción — en ese caso, STOP y escalar.
```

Esto reduce daños accidentales de agentes con herramientas de escritura:
`laravel` en este ejemplo tiene, a nivel de frontmatter, permiso general
para editar `app/**`, pero el Task Boundary lo acota a los dos archivos
específicos de esta tarea.
