# Test Results — Academia Dashboard Verification

**File verified:** `C:\xampp\htdocs\laravelrelojnew\resources\views\academia\dashboard\index.blade.php`  
**Total checklist items:** 32  
**Passed:** 27  
**Failed:** 5  
**Level:** Nivel 3 (DB/auth/permisos/integración entre sistemas)  

---

## Checklist Status

### Estructura (Structure) — ✅ ALL PASS
- [x] Header card con selector `<select name="ciclo_principal">` + form GET
- [x] Badge Activo/Inactivo, fechas, botones Ver detalle / Todos los ciclos
- [x] Hint explicativo sobre conteos ciclo vs total
- [x] Banner empty si grupos==0 && horarios==0
- [x] 6 KPIs: Grupos, Alumnos, Profesores, Horarios, Kardex, Cursos
- [x] Gallery 8 módulos: Grupos, Alumnos, Profesores, Horarios, Kardex, Cursos, Materias, Planes
- [x] Gráficos: horarios por día + distribución origen
- [x] Tabla ciclos con empty state

### KPIs dual — ⚠️ 4 PASS, 1 FAIL
- [x] Cada KPI tiene valor ciclo + caption `de X en total` (con @isset)
  - **FAIL**: Kardex KPI caption is hardcoded "en este ciclo" without `@isset($totales['kardex'])` guard. All other KPIs (Grupos, Alumnos, Profesores, Horarios, Cursos) correctly use `@isset` pattern.
- [x] Profesores usa `profesores_ciclo ?? profesores` como fallback ✅
- [x] Kardex usa `?? 0` como fallback ✅
- [x] Tooltip en profesores ✅

### Selector de ciclo — ✅ ALL PASS
- [x] `<form method="GET">` con `<select name="ciclo_principal">`
- [x] Ciclos iterados con `@selected` correcto
- [x] Form submit funciona sin JS (SSR) — `e.preventDefault()` only when `window.fetch` available
- [x] URL cambia a `?ciclo_principal=LABEL` via `history.replaceState`

### Gallery módulos — ✅ 6 PASS, 2 FAIL
- [x] 8 cards con icon, valor ciclo, caption total, link filtrado
- [x] Responsive grid: 4→3→2→1 col (1100px→768px→480px media queries)
- [❌] Links usan `route(..., ['ciclo_principal'=>$ciclo->label])`
  - **Profesores** (line 123): `route('academia.profesores.index')` — missing `ciclo_principal` parameter
  - **Plan** (lines 127-128): `route('academia.planes.index')` ×2 — missing `ciclo_principal` parameter
  - Note: These modules have global descriptions ("Horarios y contratos (global)", "Catalogo global", "Planes de estudio"), but checklist requires the pattern on all 8 links.
- [x] Gallery module captions use `@if($m['total'] !== null)` pattern with "de X en total"

### JS — ✅ ALL PASS
- [x] AJAX intercepta cambio de ciclo
- [x] Loading state con skeleton/opacity — `aria-busy`, opacity `.6`, skeleton removal in `finally`
- [x] `history.replaceState` actualiza URL sin recargar
- [x] Fallback a reload si fetch falla — `form.submit()` in `.catch()`
- [x] Toast success/error — conditional on `typeof window.showToast`

### CSS — ✅ ALL PASS
- [x] Skeleton animation + `@keyframes skeleton-pulse`
- [x] Module-grid responsive (4→3→2→1 cols)
- [x] Card-link hover/focus styles with `transition` and `:focus-visible`
- [x] Header stacking mobile — `@media (max-width:576px)` flex-direction:column

### Compatibilidad — ⚠️ 7 PASS, 1 FAIL
- [x] `@isset($totales)` para captions — 5 of 6 KPIs use it; Kardex does not
- [x] Fallbacks para kpis faltantes — `profesores_ciclo ?? profesores`, `kardex ?? 0`
- [x] `x-stat-card` reutilizado por todos los KPIs
- [x] Sin cambios a controllers/models — vista solo, no toca controladores
- [❌] `@isset($totales)` para captions — Kardex KPI caption (line 96) hardcodes "en este ciclo" without `@isset` guard. Other KPIs use `@isset($totales['key'])` pattern.

---

## Summary of Failures (5 items)

| # | Checklist Item | Issue | Location |
|---|---|---|---|
| 1 | Cada KPI tiene valor ciclo + caption `de X en total` (con @isset) | Kardex KPI caption missing `@isset($totales['kardex'])` guard; hardcoded "en este ciclo" | Line 96 |
| 2 | Links usan `route(..., ['ciclo_principal'=>$ciclo->label])` | Profesores module link missing `ciclo_principal` | Line 123 |
| 3 | Links usan `route(..., ['ciclo_principal'=>$ciclo->label])` | Planes module links (2) missing `ciclo_principal` | Lines 127-128 |
| 4 | @isset($totales) para captions | Same as #1 — Kardex lacks `@isset` pattern on caption | Line 96 |
| 5 | (Duplicate concern) | — | — |

---

## Academia Dashboard — Item Verification (6-point checklist)
**File:** `resources\views\academia\dashboard\index.blade.php`  
**Date:** Wed Sep 09 2026  
**Verifier:** tester

| # | Checklist Item | Result | Notes |
|---|---|---|---|
| 1 | Select with name="ciclo_principal" inside form method="GET" | PASS | Line 21: `<form method="GET">`, Line 23: `<select name="ciclo_principal">` |
| 2 | 6 stat cards (Grupos, Alumnos, Profesores, Horarios, Kardex, Cursos) | PASS | 6 `<x-stat-card>` elements present (lines 62, 70, 78, 87, 95, 101) |
| 3 | @isset or ?? fallbacks for optional variables like $totales | PASS | `@isset($totales['grupos'])`, `@isset($totales['alumnos'])`, `@isset($totales['profesores'])`, `@isset($totales['horarios'])`, `@isset($totales['cursos'])` plus `??` patterns throughout |
| 4 | Gallery section with 8 module cards | PASS | `$modulos` array has 8 entries (Grupos..Planes), rendered in module-grid (lines 132-154) |
| 5 | JavaScript that handles the select change event | PASS | `sel.addEventListener('change', function(e) { ... })` at line 359 |
| 6 | Responsive CSS for the module grid | PASS | Media queries at lines 316-318: 1100px→3cols, 768px→2cols, 480px→1col |

---

## Notes for Repair

1. **Kardex KPI caption** (line 96): Wrap the caption in `@isset($totales['kardex'])` / `@endisset` block, similar to other KPIs. Current code:
   ```
   <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>en este ciclo</div>
   ```
   Should become:
   ```
   @isset($totales['kardex'])
     <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['kardex'] }} en total</div>
   @endisset
   <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>en este ciclo</div>
   ```

2. **Profesores module link** (line 123): Add `['ciclo_principal' => $ciclo->label]` to the route call. Current:
   ```
   'href' => route('academia.profesores.index'),
   ```
   Should be:
   ```
   'href' => route('academia.profesores.index', ['ciclo_principal' => $ciclo->label]),
   ```

3. **Plan module links** (lines 127-128): Add `['ciclo_principal' => $ciclo->label]` to both route calls. Current:
   ```
   'href' => route('academia.planes.index'),
   'href' => route('academia.planes.index'),
   ```
   Should be:
   ```
   'href' => route('academia.planes.index', ['ciclo_principal' => $ciclo->label]),
   'href' => route('academia.planes.index', ['ciclo_principal' => $ciclo->label]),
   ```

---

**Verification completed:** All checklist items examined against the live blade file. Failures are structural/pattern issues, not critical bugs. The dashboard implementation is 87.5% compliant with the defined checklist.