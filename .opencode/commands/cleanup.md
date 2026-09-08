---
description: Limpia archivos temporales generados por agentes y archiva/resetea el estado de la tarea cerrada
agent: cleanup
---

Limpia el proyecto para: $ARGUMENTS

Sigue la checklist completa de `.opencode/agents/cleanup.md`:

1. Lee `.opencode/state/generated-files.md` y confirma cada entrada contra
   la evidencia del agente que la creó (`Generated (temporal):` en
   `.opencode/policies/evidence.md`).
2. Descarta de la limpieza cualquier archivo que también aparezca en
   `Changed:` en algún reporte de evidencia — eso es código de
   producción, repórtalo como inconsistencia en vez de borrarlo.
3. Solicita confirmación explícita (`effect: ask`) antes de cada `rm`.
4. Archiva el contenido de `.opencode/state/*.md` de la tarea cerrada en
   `.opencode/state/archive/TASK-<id>.md`.
5. Resetea `current-task.md`, `findings.md`, `test-results.md`,
   `security-results.md`, `review-results.md` y `generated-files.md` a su
   plantilla vacía. Nunca toques `decisions.md`.
6. Escribe el resultado en `.opencode/state/cleanup-results.md`.

Si se ejecuta sin `$ARGUMENTS` (invocación automática al cierre de
`team-lead`), usa el `TASK-<id>` del Task Boundary que se acaba de cerrar
en `.opencode/state/current-task.md`.
