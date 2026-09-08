# Stop conditions de team-lead

`team-lead` debe **detener el flujo y escalar a humano** —no intentar
resolverlo por su cuenta— si ocurre cualquiera de estas condiciones. Un
buen orquestador también sabe detenerse.

## Condiciones de STOP obligatorio

- Falta contexto crítico para continuar (schema desconocido, dependencia
  externa sin documentar) y ningún especialista pudo resolverlo.
- No se puede reproducir el bug reportado después de que `tester` lo
  intentó siguiendo el protocolo de debugging.
- Conflicto de arquitectura: dos especialistas proponen soluciones
  incompatibles y `architect` no puede resolver la ambigüedad con la
  información disponible.
- Discrepancia CRITICAL en la doble pasada de `security` o `reviewer`
  (ver `policies/consensus.md`) — siempre `HUMAN REVIEW`, nunca se
  continúa.
- Migración de base de datos marcada como riesgosa (afecta tabla grande,
  producción, o no hay plan de rollback claro).
- Cualquier hallazgo de `security` con severidad CRITICAL.
- `tester` reporta que un test es imposible de ejecutar (falta entorno,
  falta acceso a Firebird de prueba, etc.) para una tarea Nivel 3 o 4.
- Un agente intenta modificar un archivo fuera de lo declarado en el Task
  Boundary (`Forbidden files`) — esto se bloquea también a nivel de
  permisos (`policies/permissions.md`), pero si ocurre, es señal de que
  el Task Boundary estaba mal definido y debe revisarse.
- Un especialista reporta `Confidence: low` de forma repetida sobre el
  mismo punto después de un reintento con otro modelo (Nivel A de
  fallback agotado sin resolver la incertidumbre).
- `cleanup` reporta `Status: BLOCKED` porque un archivo listado en
  `state/generated-files.md` también aparece como `Changed:` en algún
  reporte de evidencia (ambigüedad entre temporal y producción) — no se
  decide en automático, se resuelve con `team-lead` o el humano.

## Qué hace team-lead al detectar un STOP

1. Detiene la delegación a nuevos agentes para esa tarea.
2. Consolida en su reporte al usuario: qué se completó, qué está
   bloqueado, y por cuál de estas condiciones específicas.
3. No marca la tarea como DONE ni como BLOCKED silenciosamente — usa el
   estado `PENDING HUMAN APPROVAL` o `NEEDS HUMAN REVIEW` según
   corresponda, con el detalle completo disponible en `.opencode/state/`.
