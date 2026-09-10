# Frontend findings — Rediseño dashboard academia

> **Rol:** frontend
> **Fecha:** 2026-09-09
> **Task:** Implementar rediseño del dashboard de academia
> **Archivo:** `resources/views/academia/dashboard/index.blade.php`

---

## Resumen de cambios

Reescritura completa del Blade del dashboard de academia (211 → 456 líneas). Se implementó el diseño producido por el agente de diseño (findings.md §4.1-4.5).

## Cambios realizados

### 1. Header con selector in-page (líneas 6-45)
- Card `border-left:4px solid var(--primary)` con ciclo activo, badge `Activo/Inactivo`, fechas en JetBrains Mono.
- `<form method="GET" action="{{ route('academia.dashboard') }}">` con `<select name="ciclo_principal">` que lista todos los ciclos via `$ciclos`.
- Funciona sin JS (SSR): submit → `?ciclo_principal=LABEL` → recarga con datos del ciclo.
- Botones "Ver detalle" y "Todos los ciclos".
- Hint contextual explicando "en este ciclo" vs "de ... en total".

### 2. Banner empty (líneas 47-56)
- `alert-warning` si `kpis['grupos']==0 && kpis['horarios']==0`.
- Link a "Crear grupo" con filtro por ciclo.

### 3. KPIs dual — 6 cards (líneas 58-110)
- 6 `<x-stat-card>`: Grupos (purple), Alumnos (blue), Profesores (green), Horarios (orange), Kardex (pink), Cursos (teal).
- Captions `de X en total` envueltos en `@isset($totales[...])` — ocultos si el backend no envía `$totales`.
- `kpis['profesores_ciclo'] ?? kpis['profesores']` — fallback si el controller no calcula el distinct por ciclo.
- `kpis['kardex'] ?? 0` — fallback si no existe.
- Tooltip en profesores con `data-bs-toggle="tooltip"`.
- `aria-live="polite"` + `aria-busy` en `#kpi-region` para accesibilidad.
- Skeleton template `<template id="kpi-skeleton">` para loading state.

### 4. Gallery 8 módulos (líneas 112-155)
- 8 cards: Grupos, Alumnos, Profesores, Horarios, Kardex, Cursos, Materias, Planes.
- Cada card: icon `kpi-icon` con color cat, valor ciclo grande (22px mono), caption "en este ciclo" + "de X en total", link filtrado por `?ciclo_principal=`.
- Grid responsive: 4 cols → 3 cols (1100px) → 2 cols (768px) → 1 col (480px).
- `aria-label` descriptivo en cada link.

### 5. Gráficos con estados (líneas 157-262)
- **Horarios por día**: SVG existente preservado, empty overlay "Sin horarios en este ciclo" + CTA si `array_sum === 0`.
- Tabla sr-only para accesibilidad.
- `aria-label` con datos por día.
- `chart-loading` spinner overlay hidden por JS.
- **Tipo de horario**: cards HD/CA con empty state "Sin datos de origen".

### 6. Tabla ciclos (líneas 264-309)
- Preservada tal cual, con `@forelse` para empty state y `scope="col"` en `<th>`.

### 7. CSS inline en `@push('styles')` (líneas 312-345)
- `.module-grid` responsive (1100/768/480px).
- `.card-link` hover: `border-color var(--primary)`, `translateY(-1px)`, `box-shadow`, `focus-visible`.
- `.skeleton` pulse animation.
- `.kpi-value.kpi-flash` animation.
- Header stacking en mobile (576px).
- KPI value override (22px) en small screens.

### 8. JS enhancement en `@push('scripts')` (líneas 347-455)
- Intercepta `change` del select para AJAX (fetch).
- Loading state: `aria-busy=true`, opacity .6, spinner en charts.
- Actualiza KPIs, módulos, header label sin reload.
- `history.replaceState` para URL compartible.
- Sync session vía `POST /academia/set-ciclo` en background (non-blocking).
- Toast success/error.
- Fallback a SSR (`form.submit()`) si fetch falla o endpoint 404.
- Degradado graceful: sin JS, el form submit normal funciona.

## Compatibilidad con backend actual

| Variable | Controller actual | Uso en Blade | Compatibilidad |
|---|---|---|---|
| `$ciclo` | Si | Header, links, ciclo_label | OK |
| `$kpis['grupos']` | Si | KPI, gallery | OK |
| `$kpis['alumnos']` | Si | KPI, gallery | OK |
| `$kpis['profesores']` | Si (global) | Fallback `?? $kpis['profesores']` | OK |
| `$kpis['profesores_ciclo']` | No | `?? $kpis['profesores']` | Fallback seguro |
| `$kpis['horarios']` | Si | KPI, gallery, chart | OK |
| `$kpis['kardex']` | No | `?? 0` | Muestra 0 |
| `$kpis['cursos']` | Si | KPI, gallery | OK |
| `$kpis['materias_ciclo']` | No | `?? null` | Gallery muestra '—' |
| `$totales` | No | `@isset($totales[...])` | Se ocultan captions |
| `$horariosPorDia` | Si | SVG chart | OK |
| `$porOrigen` | Si | Distribution cards | OK |
| `$ciclos` | Si | Selector, tabla | OK |

## Notas para backend (no implementadas, solo documentadas)

1. **`$totales`**: El controller debería agregar `$totales` con counts globales para que aparezcan los captions "de X en total".
2. **`$kpis['profesores_ciclo']`**: Usar `HorarioDet::porCiclo(...)->activo()->distinct('clave_profesor')->count('clave_profesor')` en vez del global.
3. **`$kpis['kardex']`**: Agregar `AlumnoKardex::porCiclo(...)->count()`.
4. **Endpoint AJAX** `GET /api/academia/dashboard-kpis?ciclo=LABEL`: El JS lo llama pero actualmente 404. Necesita un método en `ApiController` o `DashboardController` que retorne JSON con `ciclo, kpis, totales, horariosPorDia, porOrigen`.
5. Sin el endpoint AJAX, el JS hará fallback a SSR reload (funcional pero menos fluido).

## Checklist

- [x] Use el sistema de tokens y componentes Bootstrap 5 ya existentes (`x-stat-card`, `kpi-grid`, `card-link`, `section-heading`, tokens CSS).
- [x] Verifique estado vacío (banner, empty chart, empty origins, @forelse ciclos), de carga (skeleton, spinner, aria-busy), y de error (catch fetch → toast + SSR fallback).
- [x] El cambio se limita a `resources/views/academia/dashboard/index.blade.php`.
- [x] No introduje un framework CSS/JS nuevo — vanilla JS + Bootstrap 5.
- [x] No modifique lógica de servidor (Controllers, Services).
- [x] No aprobé mi propio código.
- [x] No creé archivos temporales/scratch.
- [x] Escribí el resultado en `.opencode/state/findings.md` con el formato de evidencia.

## Generated (temporal): ninguno
