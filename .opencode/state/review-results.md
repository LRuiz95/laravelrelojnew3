# Code Review Results — TASK-ACAD-001 Academia Hierarchy

**Reviewer:** reviewer (nemotron-3-ultra-free)
**Date:** 2026-09-09
**Task Boundary:** `.opencode/state/current-task.md`

---

## Summary

**Verdict: CONSENSUS with OBSERVATIONS** — The implementation correctly delivers the declared scope (cycle as primary context, unified selector, filtered alumnos/profesores, plan usage badges). Three **performance-critical N+1 issues** and several **maintainability concerns** must be addressed before DONE.

---

## Detailed Findings

### 1. Code Correctness — Logic Matches Plan ✓

| Requirement | Status | Notes |
|-------------|--------|-------|
| `Alumno::scopeInscritosEnCiclo()` filters by cycle + eager-loads grupo | ✓ | Correctly uses `whereExists` + `with(['grupo' => ...])` |
| `Profesor::scopeConHorariosEnCiclo()` filters by cycle | ✓ | Uses `whereHas('horarios', ...)` — correct |
| `Profesor::scopeTodos()` returns unfiltered query | ✓ | Trivial but explicit |
| `AlumnoController::index()` returns only inscritos in cycle | ✓ | Uses new scope, passes `$ciclo` to view |
| `ProfesorController::index()` toggle `solo_ciclo` (default true) | ✓ | Boolean param, default true — matches spec |
| `PlanController::index()` badge "Usado en X ciclos" | ✓ | Distinct cycle count via materias → horarios_det join |
| Cycle selector component preserves all query params | ✓ | Iterates `request()->except(['ciclo_principal'])` |
| All reviewed views use selector component | ✓ | Alumnos, Profesores, Planes verified |

**Missing from allowed files (per Task Boundary):**
- `resources/views/layouts/academia.blade.php` — **NOT CREATED** (listed as NEW in Allowed Files §48)
- `GrupoController`, `CursoController`, `HorarioController`, `KardexController`, `ApiController` — verified as "Solo lectura/verificación" but no evidence they pass `$ciclo` to views
- Remaining 10+ views (grupos, cursos, horarios, kardex) — not in changed files list; must be verified before DONE

---

### 2. Performance — N+1 Queries Found ⚠️ BLOCKING

| Location | Issue | Impact | Fix |
|----------|-------|--------|-----|
| `AlumnoController::index()` line 54 | Eager loads `grupo` (AlumnoGrupo pivot) but **not** `grupo.grupo` (Grupo model). View accesses `$alumno->grupo->first()->grupo->codigo_grupo` | **N+1**: 1 extra query per alumno (25/page = 25 queries) | Change `with(['grupo'])` → `with(['grupo.grupo'])` or `with(['grupo.grupo:codigo_grupo,inicial,final,periodo,nivel,turno,id_campus'])` |
| `PlanController::index()` lines 51-58 | Separate query for `ciclosPorPlan` runs after pagination fetch | Acceptable (1 extra query), but could be a subquery join | Consider `withCount` with custom subquery or `addSelect` subquery for single query |
| `ProfesorController::show()` lines 100-121 | Three separate `count()` queries for stats (total, PTC, PA) | 3 extra queries per show | Combine into single query with conditional aggregation |

**Evidence:**
```php
// AlumnoController.php:54 - CURRENT
$query->with(['sede', 'nivelRel', 'turnoRel', 'grupo'])

// AlumnoGrupo.php:40-46 - has grupo() relationship to Grupo model
// View line 93-96 accesses $alumno->grupo->first()->grupo->codigo_grupo
```

---

### 3. Maintainability — Naming & Duplication ⚠️

| Issue | Location | Recommendation |
|-------|----------|----------------|
| **Misleading relationship name** | `Alumno::grupo()` returns `AlumnoGrupo` (pivot) collection, not `Grupo` model | Rename to `inscripciones()` or `alumnoGrupos()`; add `grupoActual()` accessor for common case |
| **Trivial scope** | `Profesor::scopeTodos()` just returns `$query` | Remove scope; use `Profesor::query()` directly in controller |
| **Cycle query duplicated in 3 views** | `alumnos/index.blade.php:10`, `profesores/index.blade.php:10`, `planes/index.blade.php:11` | Extract to `CicloActualService::getAllForSelector()` (already exists!) or view composer |
| **Magic string 'ciclo_principal'** | Component, service, routes | Define constant `CicloActualService::PARAM_KEY = 'ciclo_principal'` |
| **Hardcoded pagination (25)** | 3 controllers | Move to config or constant |

---

### 4. Regression — Existing Functionality Preserved ✓

- **Alumno index**: Previously showed ALL active alumnos globally. Now correctly shows only `inscritosEnCiclo`. **Intentional breaking change** per Task Boundary §15 rollback plan.
- **Profesor index**: Previously showed all profesores. Now defaults to `conHorariosEnCiclo` with toggle for "Todos". **Intentional**.
- **Plan index**: Previously no cycle context. Now receives `$ciclo` (visual only) + badge. **Intentional additive change**.
- **No changes** to `Ciclo`, `Materia`, `Plan`, `Nivel`, `Turno`, `Sede`, `AlumnoGrupo`, `CursoDet`, migrations, `CicloActualService`, Firebird sync — compliant with Forbidden Files §75-91.

---

### 5. Testing — Coverage Gaps ⚠️

**Current test file (`AcademiaHierarchyTest.php`):**
- ✓ Model scopes (7 tests)
- ✓ Relationship verification (1 test)
- ⚠ Controller tests only verify HTTP 200/302 — **no assertions on actual data returned**
- ✗ No tests for: cycle selector component, PlanController badge logic, `solo_ciclo` toggle, eager loading correctness, N+1 absence

**Required for Nivel 3 (per Task Boundary §123):**
- Integration tests with DB verifying filtered results match expected alumnos/profesores
- Assert pagination data contains correct records
- Test cycle selector preserves filters across pagination
- Test `ciclosPorPlan` count accuracy

---

### 6. Consistency — Patterns Followed ✓

- Scopes follow existing pattern (`scopeActivo`, `scopePorCiclo`, `scopePorEstatus`)
- Controllers use constructor DI for `CicloActualService`
- Blade components use `@props` with defaults
- Bootstrap 5 classes consistent
- Routes use `academia.` prefix and resource conventions

---

### 7. Security (MEDIUM severity per Task Boundary §128)

| Check | Result | Notes |
|-------|--------|-------|
| Parameter binding in scopes | ✓ | `whereExists` with closures, `whereHas` — no raw SQL |
| Cycle validation | ✓ | `CicloActualService::findByLabel()` validates against DB |
| IDOR risk on `ciclo_principal` | ✓ Low | Cycle is validated; no direct object reference to sensitive data |
| Auth middleware on all routes | ✓ | Routes wrapped in `middleware('auth')` |
| CSRF on forms | ✓ | Forms use standard Laravel POST with `@csrf` where needed |

---

## Required Fixes Before DONE

### P0 — Must Fix (Blocking)

1. **Fix N+1 in AlumnoController**
   ```php
   // Line 54: change
   ->with(['sede', 'nivelRel', 'turnoRel', 'grupo.grupo'])
   // Or selective columns:
   ->with(['sede', 'nivelRel', 'turnoRel', 'grupo.grupo:codigo_grupo,inicial,final,periodo,nivel,turno,id_campus'])
   ```

2. **Create missing `layouts/academia.blade.php`** (Task Boundary §48 deliverable)

3. **Verify all 12+ remaining views** (grupos, cursos, horarios/*, kardex/*) use selector and receive `$ciclo`

### P1 — Should Fix

4. Rename `Alumno::grupo()` → `inscripciones()` + add `grupoActual()` accessor
5. Remove `Profesor::scopeTodos()` — use `query()` directly
6. Extract cycle query to `CicloActualService::getAllForSelector()` in views
7. Strengthen controller tests with data assertions

### P2 — Nice to Have

8. Optimize `PlanController::ciclosPorPlan` with subquery
9. Combine `ProfesorController::show()` stats into single query
10. Define `CicloActualService::PARAM_KEY` constant

---

## Evidence Artifacts

- **Models diff:** `app/Models/Academia/Alumno.php` (+3 methods), `Profesor.php` (+2 scopes)
- **Controllers diff:** `AlumnoController.php` (index rewritten), `ProfesorController.php` (index + toggle), `PlanController.php` (ciclosPorPlan query)
- **Frontend:** New component `ciclo-selector.blade.php`, 3 updated index views
- **Tests:** New `AcademiaHierarchyTest.php` (9 tests, needs data assertions)

---

## Consensus Check (Double Pass)

This review constitutes **Pass 1** (model: nemotron-3-ultra-free). A second pass with a different model is required per `.opencode/policies/consensus.md`. If the second pass agrees on the P0/P1 findings → **CONSENSUS**, proceed to fix. If discrepancy on CRITICAL (e.g., one pass misses the N+1) → **HUMAN REVIEW**.

---

## Next Steps

1. **laravel** agent: Apply P0 fixes (N+1, missing layout, verify remaining views)
2. **tester** agent: Run Nivel 3 suite with strengthened assertions; update `state/test-results.md`
3. **security** agent: Second pass MEDIUM review; update `state/security-results.md`
4. **reviewer** agent: Second pass review → if CONSENSUS on fixes, mark DONE