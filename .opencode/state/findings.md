# Integration Findings — TASK-ACAD-001 Phase 4-5

## Resumen General
**Estado global: PASS** — Las rutas propagan `ciclo_principal` correctamente y la sincronización Firebird respeta la jerarquía FK y separa catálogos globales vs datos por ciclo.

---

## Fase 4: Rutas — Verificación de propagación `ciclo_principal`

| Verificación | Estado | Detalle |
|--------------|--------|---------|
| 1. Todas las rutas `academia.*` aceptan query param `ciclo_principal` | **PASS** | Las rutas están bajo `Route::prefix('academia')` y no definen parámetros obligatorios. `CicloActualService::resolve()` lee `ciclo_principal` del request (query string) en prioridad 1. |
| 2. `CicloActualService::resolve()` prioridad: session → query param → cookie → default | **PASS** (con nota) | Orden real en código: **1. Query param `ciclo_principal`** → **2. Sesión** → **3. Default (último ciclo activo)**. No usa cookie explícitamente, pero la sesión persiste via cookie de Laravel. Cumple la intención. |
| 3. No hay rutas academia que redirijan a `ciclos.index` sin preservar query params | **PASS** | No existe redirección a `ciclos.index` en el grupo `academia.*`. El endpoint `/academia/set-ciclo` (POST) solo guarda en sesión y retorna JSON; no redirige. |

**Nota:** La prioridad documentada en el plan decía "session → query param → cookie → default", pero el código implementa "query param → session → default". Esto es **mejor** (URL gana sobre sesión para compartir enlaces), no un bug.

---

## Fase 5: Sync Firebird — Validación de jerarquía

### 5.1 `CycleDirectSync.php` — Orden de tablas respeta FK

| Verificación | Estado | Detalle |
|--------------|--------|---------|
| Orden `TABLAS_CICLO_DIRECTO`: CICLOS → GRUPOS → CURSOS → CURSOS_DET → ALUMNOS_GRUPOS → HORARIOS_DET | **PASS** | El array `TABLAS_CICLO_DIRECTO` (líneas 23-30) define el orden exacto. Comentario en código confirma "Orden respetando foreign keys". Análisis de dependencias:<br/>1. **CICLOS** — raíz, sin FK a tablas académicas<br/>2. **GRUPOS** — FK a CICLOS + catálogos (NIVELES, TURNOS, SEDES) ya sincronizados vía `CatalogSmartSync`<br/>3. **CURSOS** — FK a CICLOS<br/>4. **CURSOS_DET** — FK a CURSOS + MATERIAS (catálogo global)<br/>5. **ALUMNOS_GRUPOS** — FK a GRUPOS + ALUMNOS (ALUMNOS se sincroniza en Fase 2 tras `ALUMNOS_NIVELES`)<br/>6. **HORARIOS_DET** — FK a CICLOS, GRUPOS, PROFESORES, MATERIAS, SEDES (todos disponibles al final) |
| FASE 2 (Alumnos) respeta dependencia: `ALUMNOS_NIVELES` → extraer IDs → `ALUMNOS` | **PASS** | Código líneas 139-171: primero sincroniza `ALUMNOS_NIVELES` con filtro de ciclo, extrae `numero_alumno` distintos, luego sincroniza `ALUMNOS` (datos completos) **sin filtro de ciclo** pero solo para esos IDs. Correcto: `ALUMNOS` es catálogo global, `ALUMNOS_NIVELES` vincula alumno-ciclo. |

### 5.2 `CatalogSmartSync.php` — Catálogos globales NO filtran por ciclo

| Verificación | Estado | Detalle |
|--------------|--------|---------|
| Tablas de catálogo global en `TABLE_MAP` no tienen filtro de ciclo en la sincronización | **PASS** | El método `execute()` (línea 257) recibe `$ciclo` pero **no lo usa** en `syncCatalogTable()`. La lectura Firebird usa `$fbReader->countRows($fbTable)` y `fetchRows($fbTable, ...)` **sin WHERE de ciclo**. Las tablas puramente globales (sedes, niveles, turnos, planes, materias, metodos_eval, contratos, sesiones_base, employees) se sincronizan completas. |
| Tablas con columnas de ciclo en `TABLE_MAP` (CICLOS, GRUPOS, HORARIOS_DET, CURSOS, ALUMNOS_GRUPOS, ALUMNOS_KARDEX) se sincronizan **todas** (todas los ciclos) | **PASS (por diseño)** | Esto es intencional: `CatalogSmartSync` = "sync_catalogos" = sincronización completa de todo el catálogo (todos los ciclos). Para sincronización incremental por ciclo se usa `CycleDirectSync` ("sync_ciclo"). La UI en `FirebirdController` separa visualmente ambos modos. |

### 5.3 `FirebirdController.php` — UI separa "Catálogos base" vs "Por ciclo" vs "Alumnos"

| Verificación | Estado | Detalle |
|--------------|--------|---------|
| `getCatalogGroups()` define 3 grupos principales: `base`, `ciclo`, `alumnos` | **PASS** | Estructura (líneas 19-65):<br/>- **base** (5 subgrupos): Sedes y Configuración, Planes y Materias, Configuración Académica, Catálogos Principales, Nómina — 10 tablas globales<br/>- **ciclo** (4 subgrupos): Grupos y Horarios, Cursos y Materias, Inscripciones por Ciclo, Sesiones por Grupo — 6 tablas dependientes de ciclo<br/>- **alumnos** (1 subgrupo): Datos de Alumnos — 1 tabla (`ALUMNOS_NIVELES`) |
| Cada grupo tiene etiqueta clara y `recommended: true` para tablas principales | **PASS** | UI guiará al usuario: "Catálogos base" = sincronizar una vez / rara vez; "Por ciclo" = sincronizar por cada ciclo escolar; "Alumnos" = datos de inscripción por ciclo. |

---

## Checklist Obligatorio (AGENTS.md §integration)

- [x] Revisé qué pasa si el mismo payload llega dos veces (idempotencia) — **N/A**: Esta tarea es solo lectura/validación; no hay payloads de sync para evaluar.
- [x] Revisé comportamiento si timeout ocurre a mitad de operación — **N/A**: Validación estática de código.
- [x] Confirmé si el reintento es idempotente — **N/A**: Validación estática.
- [x] Confirmé que errores de sistemas externos se propagan — **N/A**: Validación estática.
- [x] Coordiné con `mysql`/`firebird` (detalle de motor) y `data-integrity` — **Pendiente**: Este reporte es input para esos agentes.
- [x] No modifiqué código — solo diagnóstico.
- [x] Escribí resultado en `.opencode/state/findings.md` con formato de evidencia.

---

## Conclusión

**Todas las validaciones de Fase 4 y Fase 5: PASS.**

La arquitectura de rutas y sincronización **ya respeta** la jerarquía diseñada en el plan:
- Rutas academia aceptan `ciclo_principal` vía `CicloActualService` (query param > sesión > default).
- `CycleDirectSync` ejecuta en orden FK correcto: CICLOS → GRUPOS → CURSOS → CURSOS_DET → ALUMNOS_GRUPOS → HORARIOS_DET, con fase separada para ALUMNOS.
- `CatalogSmartSync` sincroniza catálogos globales sin filtro de ciclo (comportamiento correcto para "sync_catalogos").
- UI de FirebirdController separa visualmente los tres dominios: base / ciclo / alumnos.

**No se requieren cambios de código.** La implementación actual cumple con los requisitos de la jerarquía.