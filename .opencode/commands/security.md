---
description: Corre la checklist de seguridad en doble pasada con severidad
agent: security
---

Revisa seguridad para: $ARGUMENTS

Corre la checklist fija de `.opencode/agents/security.md` en doble pasada
(dos modelos distintos, ver `.opencode/policies/models.md`) y aplica
`.opencode/policies/consensus.md` para resolver discrepancias. Clasifica
cada hallazgo por severidad (`.opencode/policies/severity.md`).

Escribe el resultado en `.opencode/state/security-results.md`.

Resultado posible: `APPROVED` → encadena a `/review`. `REJECTED: <motivo>`
→ vuelve a team-lead para reasignar el fix. `NEEDS HUMAN REVIEW: <detalle>`
→ detiene el flujo, no continúa a `/review` hasta que el usuario decida.
