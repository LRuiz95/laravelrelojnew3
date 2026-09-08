# Paquete de agentes OpenCode v2.1 — Laravel/PHP + MySQL + Firebird 2.5 + ZKTeco

## Qué cambió respecto a la v2.0

- Nuevo agente **`cleanup`** — el único autorizado a eliminar archivos
  temporales que otros agentes generan (scripts de diagnóstico, exports,
  dumps, logs puntuales) y a archivar/resetear `.opencode/state/` al
  cerrar una tarea. Ver `.opencode/agents/cleanup.md`.
- Nuevo state file **`state/generated-files.md`** — registro obligatorio
  de cualquier archivo temporal creado por un agente, con doble
  confirmación (declarado + evidencia) antes de que `cleanup` lo borre.
- Nuevo state file **`state/cleanup-results.md`** y carpeta
  **`state/archive/`** para el historial de tareas cerradas.
- **Checklist explícito con checkboxes en todos los agentes** (antes solo
  `security` y `reviewer` los tenían) — cada rol tiene ahora su propia
  lista verificable antes de reportar, además del checklist fijo de
  seguridad y la Definition of Done del proyecto.
- `team-lead` invoca `cleanup` automáticamente tras cada `DONE` (nunca si
  el resultado quedó pendiente de revisión humana, para no borrar
  evidencia que el humano necesita ver).
- Nuevo comando **`/cleanup`** para invocarlo bajo demanda.
- `evidence.md` ahora incluye el campo `Generated (temporal):` en el
  formato obligatorio de todo agente.
- `CHANGELOG.md` en la raíz del paquete para llevar el historial de esta
  configuración de agentes (no del proyecto Laravel en sí, ese lo
  mantiene `docs`).

## Qué cambió en la v2.0 respecto a la v1

Esta versión corrige la sintaxis para que sea configuración real de
OpenCode V2, no solo documentación conceptual:

- `mode: primary | subagent | all` en vez de `type`.
- `model: provider/model` (un string, no `model_primary`/`model_fallback`
  — ese campo no existe nativamente).
- `permissions:` como array de reglas `{action, resource, effect}` — la
  delegación se controla con `action: subagent`, no con `can_invoke`.
- El fallback de modelos ahora es una **política de team-lead**
  (`.opencode/policies/models.md`), no un campo del agente.

Y suma mecanismos que la v1 no tenía: estado persistente en
`.opencode/state/`, Task Boundaries, severidad que controla el flujo,
niveles de testing, consenso en la doble pasada, evidencia estructurada y
stop conditions explícitas para `team-lead`.

## Instalación

Copia `.opencode/` y `AGENTS.md` a la raíz del proyecto Laravel.

```
tu-proyecto-laravel/
├── AGENTS.md
└── .opencode/
    ├── agents/
    ├── commands/
    ├── policies/
    └── state/
```

## Fases

### Fase 1 — núcleo (8 agentes, activar primero)

`team-lead` · `architect` · `laravel` · `frontend` · `tester` ·
`reviewer` · `security` · `cleanup`

Con esto ya corre el flujo completo: clasificación → Task Boundary →
implementación → testing por nivel → security con severidad → review con
consenso → DONE → cleanup / HUMAN REVIEW.

`docs` se instala desde el día 1 también, pero como agente **pasivo**: no
se invoca automáticamente en cada tarea, solo cuando el cambio lo amerita
(ver `.opencode/agents/docs.md`). `cleanup`, en cambio, sí se invoca
automáticamente — pero solo tras un `DONE`, nunca sobre una tarea que
quedó pendiente de revisión humana.

### Fase 2 — integración del dominio (+4 agentes)

`mysql` · `firebird` · `integration` · `data-integrity`

Actívalos cuando el núcleo esté validado. Son los más relevantes para
este proyecto específico: la combinación Laravel + MySQL + Firebird +
ZKTeco es exactamente el escenario donde "cada parte está bien pero
juntas fallan" — por eso `integration` (¿se comunican bien?) y
`data-integrity` (¿los datos resultantes son correctos?) importan más
aquí que un agente de performance genérico.

### Fase 3 — bajo demanda (no incluidos en este paquete)

`performance` · `migration` · `incident` · `release` ·
`context-manager` (automatiza los mapas que hoy resuelve `state/` +
`architect` manualmente)

No se construyen todavía. Primero hay que validar que Fase 1 + Fase 2
funcionan bien en la práctica.

## Piezas nuevas — cómo se usan

- **`.opencode/state/`** — memoria de trabajo entre agentes. Cada rol
  escribe su resultado ahí (ver tabla en `AGENTS.md §6`) en vez de que
  `team-lead` dependa de lo que "recuerda" de la conversación.
- **Task Boundary** (`.opencode/policies/task-boundary.md`, comando
  `/task`) — declara qué archivos puede tocar cada tarea antes de
  implementar. Los permisos de cada agente en su frontmatter son la red
  amplia; el Task Boundary es la red fina por tarea.
- **Severidad** (`.opencode/policies/severity.md`) — LOW/MEDIUM/HIGH/
  CRITICAL controla el flujo, no es solo una etiqueta del reporte.
  CRITICAL siempre implica human approval.
- **Niveles de testing** (`.opencode/policies/test-levels.md`) — de 0
  (documentación) a 4 (suite completa). Evita correr mil tests por un
  cambio de color de botón.
- **Consenso** (`.opencode/policies/consensus.md`) — la doble pasada de
  `security`/`reviewer` ya no es binaria; hay un nivel `CONSENSUS` para
  discrepancias menores, pero cualquier CRITICAL en cualquiera de las dos
  pasadas siempre detiene el flujo.
- **Evidencia** (`.opencode/policies/evidence.md`) — formato obligatorio
  de resultado para todo agente: status, evidencia concreta (comando +
  output real), archivos cambiados, nivel de confianza, riesgos,
  follow-up. Nada de "parece que funciona".
- **Stop conditions** (`.opencode/policies/stop-conditions.md`) — lista
  explícita de cuándo `team-lead` debe detenerse y escalar en vez de
  intentar resolver todo.
- **Checklists por agente** — cada archivo en `.opencode/agents/*.md`
  tiene su propia lista de verificación con checkboxes, específica a su
  dominio, que debe cumplirse antes de escribir su evidencia. No
  reemplaza la Definition of Done del proyecto (`AGENTS.md §14`), la
  complementa a nivel de cada rol individual.
- **Limpieza de artefactos** (`.opencode/agents/cleanup.md`,
  `.opencode/state/generated-files.md`) — cualquier archivo temporal que
  un agente genere (scripts de diagnóstico, exports, dumps) se declara
  explícitamente y solo `cleanup` puede borrarlo, con confirmación y tras
  verificar que no es código de producción. `cleanup` también archiva el
  estado de cada tarea cerrada en vez de perderlo al resetear.

## Sobre el fallback de modelos — una aclaración importante

`.opencode/policies/models.md` documenta dos niveles de fallback (fallo
técnico y baja confianza), pero **ninguno de los dos es una capacidad
nativa automática de OpenCode**. El Nivel A (cambiar de modelo si uno
falla) requiere que `team-lead` señale el cambio de configuración — no
ocurre solo. El Nivel B (baja confianza) depende de que cada agente
reporte `Confidence: low` explícitamente en su evidencia — OpenCode no
expone un puntaje de confianza real del modelo. Tenlo presente: es un
protocolo que los agentes siguen por instrucción, no un mecanismo técnico
verificable.

## Comandos disponibles

`/audit` · `/task` · `/plan` · `/implement` · `/test` · `/security` ·
`/review` · `/health` · `/cleanup`
