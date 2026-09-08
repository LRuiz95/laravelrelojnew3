---
description: Última revisión antes de DONE, en doble pasada con consenso
agent: reviewer
---

Revisa para cierre: $ARGUMENTS

Corre la checklist de `.opencode/agents/reviewer.md` en doble pasada y
aplica `.opencode/policies/consensus.md`. Escribe el resultado en
`.opencode/state/review-results.md`.

Resultado posible: `APPROVED` → si el Task Boundary marca rutas sensibles
o severidad CRITICAL, marca `PENDING HUMAN APPROVAL`; si no, marca `DONE`
y notifica a `docs` (que decide si actualiza algo, ver
`.opencode/agents/docs.md`). `REJECTED: <motivo>` → vuelve a team-lead
para reasignar. `NEEDS HUMAN REVIEW: <detalle>` → detiene el flujo.
