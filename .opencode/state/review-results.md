# Code Quality Review — `resources/views/academia/dashboard/index.blade.php`

**Reviewer:** opencode/reviewer (review 2)
**Date:** 2026-09-09
**Severity:** LOW
**Verdict:** PASS — minor notes, no blockers.

---

## 1. Blade syntax

Valid throughout. Correct use of `@extends`, `@section`, `@push`, `@if/@elseif/@else/@endif`, `@isset/@endisset`, `@foreach/@endforeach`, `@forelse/@empty/@endforelse`, `@switch/@case/@default/@endswitch`, `@php/@endphp`. No unclosed blocks or mismatches.

## 2. `route()` calls

All 11 route calls use named routes with appropriate parameters:
- `academia.dashboard`, `academia.ciclos.show`, `academia.ciclos.index` — correct.
- `academia.grupos.index`, `academia.alumnos.index`, `academia.profesores.index`, `academia.horarios.clase`, `academia.kardex.index`, `academia.cursos.index`, `academia.planes.index` — correct.
- `dashboard.kpisJson` — correct.
- No typos or missing parameters detected.

## 3. Variable handling with `@isset` / fallbacks

- `$kpis` array accessed with `??` null coalescing throughout (lines 48, 78, 95, 121–128). Safe.
- `$totales` wrapped in `@isset` before rendering sub-captions (lines 63, 71, 79, 88, 102). Correct.
- `$ciclo` properties accessed without null checks — assumed present by controller contract. Acceptable if controller always provides it.
- `$horariosPorDia`, `$porOrigen` — guarded: `array_sum() === 0` check (line 192), `empty()` check (line 234). Correct.
- Module array uses `?? null` for optional totals (lines 121–128). Correct.

## 4. JS syntax

- IIFE wrapper (line 350). No globals leaked.
- `querySelector`/`getElementById` with null guards (line 356). Correct.
- `addEventListener` for `change` and `submit`. Correct.
- `fetch` with proper `.then()`/`.catch()`/`.finally()` chain. No unhandled promises.
- `encodeURIComponent` used for URL params (lines 365, 417). Correct.
- `void valEl.offsetWidth` for reflow trick (line 393). Standard pattern.
- Form submit fallback (line 440) when fetch fails. Correct.
- No syntax errors detected.

## 5. CSS tokens

- Uses `var(--primary)`, `var(--border)`, `var(--text-tertiary)`, `var(--cat-*)` — existing design tokens.
- Color categories: `purple`, `blue`, `green`, `orange`, `pink`, `teal`, `amber`, `lavender` — consistent with component library.
- No hardcoded hex colors except `rgba(0,0,0,.08)` and `rgba(0,0,0,.22)` for shadows — acceptable.
- Font: `'JetBrains Mono'` used elsewhere in the project — consistent.

## 6. Dead code

- Line 18: `<div class="vr d-none d-md-block" ...>` — visual divider, used in flex layout. Not dead.
- `<template id="kpi-skeleton">` (line 109) — referenced by JS for loading state. Not dead.
- Chart loading spinner (line 226) — toggled by JS. Not dead.
- No unreachable code or unused variables.

## 7. Responsive design

- Module grid: 4→3→2→1 columns via `@media` (lines 316–318). Correct.
- Header card: flex wrap + column on mobile (lines 334–338). Correct.
- KPI value font size override on small screens (lines 341–343). Correct.
- Table wrapped in `.table-responsive` (line 271). Correct.
- Chart SVG uses `preserveAspectRatio="none"` for fluid width. Acceptable for a sparkline.

## 8. Accessibility

- `aria-labelledby` on sections (lines 7, 113). Correct.
- `aria-live="polite"` + `aria-busy` on KPI region (line 59). Correct — JS toggles busy state during fetch.
- `sr-only` table (`.visually-hidden`, line 220) with `<caption>`, `<thead>`, `scope="col"`. Correct.
- `aria-label` on SVG chart (line 200) with data summary. Correct.
- `aria-label` on module cards (line 135). Correct.
- `aria-label` on tooltip button (line 82). Correct.
- `role="status"` on warning alert (line 49). Correct.
- `role="img"` on SVG (line 199). Correct.
- `:focus-visible` style on `.card-link` (line 324). Correct.
- Form `<label for>` association (line 22–23). Correct.

## 9. Minor notes (non-blocking)

- **Line 406:** `document.querySelector('[data-mod-ciclo="' + label + '"]')` — `label` comes from `Object.keys(map)` which is a fixed set of hardcoded strings. Safe. If map keys ever included user input, this would be a selector injection risk, but here it's not.
- **Line 422:** POST to `/academia/set-ciclo` uses a hardcoded path. If the route changes, this breaks silently. Consider using a `data-` attribute on the page to externalize the URL (same pattern as `data-kpis-url`).
- **Line 381:** The `map` object keys must match the `.kpi-label`/`h6` text content exactly. If any label changes in the Blade components, this JS mapping breaks silently. Low risk since both are in the same file.
- No `@csrf` directive in the form, but it's a GET form so none is needed. Correct.

---

**Summary:** Blade syntax valid, routes correct, variables guarded, JS clean, CSS uses tokens, responsive complete, accessibility solid. Three minor maintainability notes (non-blocking). No dead code.
