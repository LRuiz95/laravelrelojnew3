---
description: Crea el Task Boundary para la tarea activa antes de implementar
agent: architect
---

Antes de implementar, produce el Task Boundary para: $ARGUMENTS

Usa el formato exacto de `.opencode/policies/task-boundary.md` (Task ID,
Scope, Test level, Allowed files, Forbidden files, Agents involved,
Expected outputs, Requires security, Requires human approval) y guárdalo
en `.opencode/state/current-task.md`.

Si la tarea es SIMPLE según `AGENTS.md §3`, hazlo de forma abreviada de
todos modos — un Task Boundary mínimo sigue siendo útil para acotar qué
archivos se pueden tocar.
