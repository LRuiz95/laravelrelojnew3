# Archive — TASK-20260907-dashboard-optimization

**Task ID:** `TASK-20260907-dashboard-optimization`
**Status:** DONE (con observaciones de reviewer que requirieron corrección antes de cierre)
**Closed:** 2026-09-07

## Scope

Añadir índice compuesto `attendances(type, recorded_at)` y optimizar queries del `DashboardController` usando conditional aggregation para reducir de ~20 queries a ~8-9 por request.

## Agentes Involucrados

| Agente | Rol | Entregable |
|--------|-----|------------|
| `laravel` | Implementación | Migración + DashboardController refactor |
| `mysql` | Verificación | EXPLAIN plans + confirmar índice usado |
| `tester` | Testing Nivel 2 | Tests existentes + nuevos |
| `security` | Review (LOW) | Checklist de seguridad — CONSENSUS, sin bloqueos |
| `reviewer` | Code review | NEEDS CHANGES → correcciones → DONE |

## Archivos Modificados/Creados

- `database/migrations/2026_09_07_000001_add_type_recorded_at_index_to_attendances.php` — **CREATE**
- `app/Http/Controllers/DashboardController.php` — **MODIFY** (5 métodos: kpis, pipeline, donut, trend, todayInfo)
- `tests/Feature/DashboardQueryTest.php` — **CREATE** (tests de regresión)

## Decisiones de Arquitectura (→ decisions.md)

1. Índice compuesto `(type, recorded_at)` en ese orden
2. Conditional aggregation (`SUM CASE`) en vez de múltiples `count()`
3. `BETWEEN` ranges en vez de `DATE()`/`DATE_FORMAT()` en WHERE

## Hallazgos Clave

- **Security (LOW):** Missing rate limiting advisory en `kpisJson()` (fuera de task boundary)
- **Reviewer:** Detectó breaking change en `kpis()` (6 vs 5 KPIs), `pipeline()` no usaba BETWEEN, query extra en `todayInfo()`, falta `DashboardQueryTest.php` — todo corregido antes de DONE

## Estado Final

**Todos los tests pasan.** Migración aplicada. Índice visible. Queries optimizadas (~8-9 vs ~20 originales).
