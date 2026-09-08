---
description: Audita el sistema de agentes mismo (modelos activos, fallbacks, discrepancias) — no el proyecto
agent: team-lead
---

Genera un reporte de salud del sistema de agentes.

Cubre: modelos activos (por agente, primario respondiendo sí/no, y
fallbacks del Nivel A activados recientemente según
`.opencode/policies/models.md`), tareas recientes (SIMPLE vs COMPLEJA,
cuántas DONE vs NEEDS HUMAN REVIEW vs BLOCKED), doble-pasadas de
security/reviewer que terminaron en HUMAN REVIEW (señal de que la
checklist o los modelos no bastan), casos de `Confidence: low` reportados
por especialistas, y cualquier conflicto de escritura detectado.

Termina con recomendaciones concretas — por ejemplo, actualizar
`.opencode/policies/models.md` si un modelo del catálogo gratuito dejó de
responder. No modifica código ni configuración, solo reporta.
