---
description: Auditoría de salud del proyecto completo (solo lectura)
agent: architect
---

Genera un reporte de salud del proyecto para: $ARGUMENTS

Cubre, en este orden: Project health, Architecture, Security (a nivel
estático, no reemplaza al agente security), Database (MySQL + Firebird:
índices faltantes, N+1 conocidas, dependencias legacy), Laravel
(convenciones, controllers gordos), Frontend (consistencia de
componentes, estados faltantes), Testing (cobertura aproximada por
módulo), Performance (cuellos de botella conocidos), Technical debt
(lista priorizada), Priority (top 3-5 a atender primero, con
justificación).

No modifiques nada — es solo lectura, input para decidir próximos pasos.

## Modo `--deep` (auditoría de completitud y redundancia)

Si `$ARGUMENTS` incluye `--deep`, además de lo anterior invoca a
`completeness-auditor` sobre el mismo alcance para cubrir lo que un
health-check normal no toca:

- Trazado end-to-end ruta → controller → vista → modelo → assets, con
  cualquier eslabón roto marcado explícitamente.
- Funcionalidad redundante: pantallas/botones que duplican un camino ya
  más simple hacia el mismo resultado en otra parte del proyecto (ver
  skill `redundancy-and-completeness`).
- Código muerto: rutas, métodos, vistas o componentes sin ningún
  llamador real (verificado por grep, no por suposición).
- Organización y distribución de UI: botones/menús comparados contra el
  patrón ya establecido en pantallas hermanas (`structural-symmetry`).

`completeness-auditor` es solo lectura y clasifica cada hallazgo como
CONFIRMED (trazado completo) o SUSPECTED (patrón detectado, pendiente de
confirmar). El reporte final debe fusionar ambas salidas bajo las
secciones de arriba más: Rutas rotas, Redundancia funcional, Código
muerto, Organización/UI — nada se elimina ni se modifica en este paso,
es insumo para que `architect` proponga el plan de limpieza.
