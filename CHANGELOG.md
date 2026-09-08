# CHANGELOG — Paquete de agentes OpenCode

> Historial de esta configuración de agentes (`.opencode/`), no del
> proyecto Laravel donde se instala. Lo mantiene quien administra el
> paquete de agentes, no el agente `docs` (que documenta el proyecto en
> sí).

## v2.1

### Agregado

- Agente `cleanup` (`.opencode/agents/cleanup.md`): único autorizado a
  eliminar archivos temporales generados por otros agentes y a
  archivar/resetear `.opencode/state/` al cerrar una tarea.
- Comando `/cleanup`.
- State file `state/generated-files.md`: registro obligatorio de
  archivos temporales creados por cualquier agente, con doble
  confirmación (declarado + evidencia) antes de eliminarse.
- State file `state/cleanup-results.md` y carpeta `state/archive/` para
  el historial de tareas cerradas.
- Checklist explícito con checkboxes en **todos** los agentes
  (`architect`, `laravel`, `frontend`, `mysql`, `firebird`,
  `integration`, `data-integrity`, `docs`, `tester`, `team-lead`,
  `cleanup`). Antes solo `security` y `reviewer` lo tenían.
- Campo `Generated (temporal):` en el formato obligatorio de evidencia
  (`.opencode/policies/evidence.md`).
- Sección `AGENTS.md §12.1` sobre limpieza de artefactos generados.
- Ítem de Definition of Done sobre ejecución de `cleanup` tras `DONE`.
- Stop condition sobre archivos ambiguos entre temporal y producción
  detectados por `cleanup`.

### Cambiado

- `team-lead` ahora invoca `cleanup` automáticamente tras cada `DONE`
  (nunca si el resultado quedó pendiente de revisión humana).
- El reporte final de `team-lead` al usuario incluye una línea `Cleanup:`.
- `.opencode/policies/permissions.md` y `.opencode/policies/models.md`
  actualizados con las reglas y el modelo asignado a `cleanup`.

## v2.0

- Sintaxis real de OpenCode V2 (`mode`, `model: provider/model`,
  `permissions:` como reglas `{action, resource, effect}`).
- Estado persistente en `.opencode/state/`.
- Task Boundaries (`.opencode/policies/task-boundary.md`).
- Severidad como control de flujo (`.opencode/policies/severity.md`).
- Niveles de testing 0-4 (`.opencode/policies/test-levels.md`).
- Consenso en doble pasada de `security`/`reviewer`
  (`.opencode/policies/consensus.md`).
- Formato de evidencia estructurada (`.opencode/policies/evidence.md`).
- Stop conditions explícitas para `team-lead`
  (`.opencode/policies/stop-conditions.md`).
