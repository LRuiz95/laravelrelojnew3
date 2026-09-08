# AGENTS.md — Reglas del proyecto (v2.0)

> Reglas generales únicamente. El conocimiento específico de cada tecnología
> vive en `.opencode/agents/*.md`. Las políticas transversales (severidad,
> niveles de test, consenso, evidencia, fallback de modelos) viven en
> `.opencode/policies/*.md`. No dupliques contenido aquí.

## 1. Misión

Mantener y evolucionar el sistema (Laravel/PHP, MySQL, Firebird 2.5,
ZKTeco, frontend Bootstrap 5) con cambios seguros, probados y mínimos.

## 2. Reglas generales

- Antes de modificar código no trivial: **entender → diseñar → revisar
  contexto → programar**.
- Cambios pequeños y acotados a lo que declara el Task Boundary
  (`.opencode/policies/task-boundary.md`).
- Commits lógicos y atómicos.
- No inventar arquitectura nueva si ya existe un patrón establecido.
- No sustituir librerías/frameworks existentes sin aprobación humana.
- Todo agente que produce un resultado debe dejar **evidencia estructurada**
  (`.opencode/policies/evidence.md`), nunca una afirmación sin respaldo del
  tipo "parece que funciona".

## 3. Clasificación de tareas (SIMPLE vs COMPLEJA)

`team-lead` clasifica toda tarea con estos criterios objetivos. Si se
cumple cualquiera, es **COMPLEJA** y pasa por `architect`:

- Toca autenticación, autorización, sesiones o permisos.
- Toca pagos o datos financieros.
- Toca migraciones o cambios de schema (MySQL o Firebird).
- Involucra sincronización entre sistemas (Firebird ↔ Laravel ↔ MySQL ↔
  ZKTeco) — esto es COMPLEJA casi siempre.
- Afecta más de 3 archivos o más de una capa.
- `team-lead` no tiene certeza de qué módulos se ven afectados.

Todo lo demás es **SIMPLE**.

## 4. Flujo de trabajo

```
Usuario → team-lead → [architect si COMPLEJA] → task-boundary
        → especialista(s) → integration/data-integrity (si aplica)
        → tester → security + reviewer (paralelo) → consensus
        → [HUMAN APPROVAL si aplica] → DONE → cleanup → reporte al usuario
```

`cleanup` solo corre si el resultado fue `DONE`. Si el flujo terminó en
`NEEDS HUMAN REVIEW`, `PENDING HUMAN APPROVAL` o `BLOCKED`, el estado se
preserva intacto y `cleanup` no se invoca todavía.

Ver matriz completa de delegación en `.opencode/policies/permissions.md`.

## 5. Task Boundary (obligatorio para tareas COMPLEJAS)

Antes de que cualquier especialista escriba código en una tarea COMPLEJA,
debe existir un Task Boundary en `.opencode/state/current-task.md` con:
Task ID, Scope, Allowed files, Forbidden files, Agents involved, Expected
outputs. Ver formato completo en `.opencode/policies/task-boundary.md`.
Para tareas SIMPLE es opcional pero recomendado.

## 6. Estado persistente (`.opencode/state/`)

Los agentes no dependen de memoria conversacional para pasar información
entre sí. Cada rol escribe su resultado en el archivo de estado
correspondiente:

| Agente | Escribe en |
|---|---|
| architect | `state/plan.md`, `state/decisions.md` |
| laravel / frontend / mysql / firebird / integration / data-integrity | `state/findings.md` |
| tester | `state/test-results.md` |
| security | `state/security-results.md` |
| reviewer | `state/review-results.md` |
| cualquier agente que cree un archivo temporal/scratch | `state/generated-files.md` (declarar, nunca borrar por sí mismo) |
| cleanup | `state/cleanup-results.md`, `state/archive/TASK-<id>.md`; resetea el resto de `state/*.md` (excepto `decisions.md`) |

`team-lead` lee estos archivos en vez de asumir "lo que dijo el agente
anterior". Esto es especialmente importante con modelos gratuitos y
sesiones largas donde el contexto conversacional se pierde o se compacta.

## 7. Protocolo de debugging

```
Bug reportado → reproducir → test que falla → fix → test pasa → regresión
```
Ningún fix se considera terminado sin un test que lo cubra primero.

## 8. Niveles de testing (no siempre "suite completa")

Ver definición completa en `.opencode/policies/test-levels.md`. Resumen:

- **Nivel 0** — solo documentación → sin tests.
- **Nivel 1** — CSS/visual simple → tests afectados.
- **Nivel 2** — JS/Blade/Controller → tests específicos + relacionados.
- **Nivel 3** — DB/auth/permisos/integración entre sistemas → suite
  relevante amplia.
- **Nivel 4** — crítico/seguridad/sincronización → suite completa.

`architect` (o `team-lead` si es SIMPLE) asigna el nivel en el plan.

## 9. Seguridad — severidad controla el flujo

Ver checklist y niveles completos en `.opencode/agents/security.md` y
`.opencode/policies/severity.md`. Resumen:

| Severidad | Acción |
|---|---|
| LOW | Solo se registra en `state/security-results.md`. |
| MEDIUM | Pasa por `reviewer`. |
| HIGH | Pasa por `security` + `reviewer`. |
| CRITICAL | `security` + `reviewer` + **HUMAN APPROVAL obligatorio**. |

## 10. Doble pasada y consenso (security y reviewer)

Ambos agentes corren dos pasadas con modelos distintos. Ya no es un
resultado binario — ver `.opencode/policies/consensus.md`:

- Resultados iguales → se procede según la severidad más alta encontrada.
- Discrepancia de severidad baja/compatible → CONSENSUS, se procede.
- Discrepancia CRITICAL (ej. uno dice IDOR, otro dice que no) → siempre
  `HUMAN REVIEW`, nunca se autoaprueba.

## 11. Integración entre sistemas vs integridad de datos

Son preguntas distintas y las responde un agente distinto cada una:

- `integration` — ¿los sistemas se comunican correctamente? (payloads,
  timeouts, retries, idempotencia, mapping, errores entre Laravel ↔
  MySQL ↔ Firebird ↔ ZKTeco).
- `data-integrity` — ¿los datos resultantes siguen siendo correctos?
  (duplicados, huérfanos, FK, NULLs, fechas, consistencia, registros
  parciales tras una sincronización).

## 12. Reglas de base de datos

- MySQL: `EXPLAIN` antes de aprobar queries en rutas críticas, `utf8mb4`
  siempre, evitar `SELECT *`, evitar funciones sobre columnas indexadas,
  transacciones cortas.
- Firebird 2.5: usar `RDB$RELATIONS`, `RDB$RELATION_FIELDS`, `RDB$INDICES`
  para descubrir schema real antes de escribir queries legacy.
- Ninguna migración se aplica sin confirmar integridad referencial y sin
  Task Boundary explícito que la autorice.

## 12.1 Limpieza de artefactos generados

Los agentes con permiso de escritura/shell pueden dejar archivos
temporales a su paso (scripts de diagnóstico, exports, dumps, logs
puntuales). Estos **no son código de producción** y no deben acumularse
en el repositorio ni depender de que un humano los borre a mano.

- Todo archivo temporal se declara en `.opencode/state/generated-files.md`
  y en el campo `Generated (temporal):` de la evidencia del agente que lo
  creó (`.opencode/policies/evidence.md`).
- `cleanup` (`.opencode/agents/cleanup.md`) es el único agente autorizado
  a eliminarlos, y solo tras confirmar la doble referencia (declarado +
  evidencia) y que no aparezcan en `Changed:` de ningún reporte.
- `cleanup` también archiva el estado de la tarea cerrada en
  `.opencode/state/archive/TASK-<id>.md` y resetea las plantillas de
  `.opencode/state/` (nunca `decisions.md`, que es historial).
- `team-lead` invoca `cleanup` automáticamente tras `DONE` (`AGENTS.md
  §4`); también puede invocarse bajo demanda con `/cleanup`.

## 13. Git

- Un branch por tarea. Commits descriptivos en imperativo. No forzar push
  sobre historial compartido. `reviewer` revisa el diff completo.

## 14. Definition of Done

Una tarea está DONE solo si:

- [ ] Implementa exactamente lo que declara el Task Boundary, nada más.
- [ ] Pasó por `tester` al nivel de testing correspondiente y quedó
      registrado en `state/test-results.md` con evidencia.
- [ ] Pasó por `security` si la severidad lo exige, registrado en
      `state/security-results.md`.
- [ ] Pasó por `reviewer`, registrado en `state/review-results.md`, sin
      observaciones bloqueantes o con CONSENSUS alcanzado.
- [ ] Si hay CRITICAL o toca rutas sensibles, tiene aprobación humana
      registrada.
- [ ] `docs` fue notificado (actualiza solo si el cambio lo amerita, ver
      `.opencode/agents/docs.md`).
- [ ] `cleanup` corrió tras el `DONE`: estado archivado/reseteado y
      archivos temporales confirmados eliminados (ver `AGENTS.md §12.1`).

## 15. Stop conditions de team-lead

`team-lead` debe detener el flujo y escalar a humano —no "intentar
resolverlo"— si ocurre cualquiera de estas condiciones. Lista completa en
`.opencode/policies/stop-conditions.md`.

## 16. Fallback de modelos

El fallback NO es un campo del frontmatter de cada agente (no existe esa
capacidad nativa en OpenCode V2). Es una política que aplica `team-lead`
cuando un agente falla o entrega baja confianza. Ver
`.opencode/policies/models.md`.
