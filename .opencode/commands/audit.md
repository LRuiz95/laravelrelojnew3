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
