---
description: Ejecuta un plan ya aprobado o una tarea simple directa
agent: team-lead
---

Implementa: $ARGUMENTS

Si existe un Task Boundary activo en `.opencode/state/current-task.md`,
delega exactamente a los agentes y archivos que declara, respetando el
orden de dependencias del plan (`.opencode/state/plan.md`). Si es una
tarea SIMPLE sin Task Boundary previo, delega directo al especialista
correspondiente.

Al terminar la implementación, encadena automáticamente a `/test`. No
marques la tarea como DONE aquí — eso ocurre después de `/test`,
`/security` (si aplica) y `/review`.
