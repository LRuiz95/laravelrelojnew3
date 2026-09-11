# Hallazgos — TASK-2026-0911 Grupo C (employees)

## Agent: @frontend

## Archivos modificados

| Archivo | Cambios | Líneas antes → después |
|---------|---------|----------------------|
| `app/Http/Controllers/EmployeeController.php` | index(): agregó queries distinct para `$cargos`, `$departamentos`, `$sedes` y los pasa a la vista. search(): agregó soporte para filtros `cargo`, `departamento`, `id_campus`, `sin_huella`, `sin_device`; agregó eager loading de `sede` y `fingerprints`; retorna campos completos para renderizado JS (cargo, departamento, sede_label, contrato, nivel, is_baja, status_actual, fingerprints_count, has_card, last_sync, URLs de acción). | 466 → 518 |
| `resources/views/employees/index.blade.php` | Refactor completo: eliminó 273 líneas de JS inline; implementó patrón SSR + progressive enhancement; agregó chips de filtro activos con badges; agregó IDs para JS (`employees-table`, `employees-pagination`, `employees-counter`); agregó `data-is-admin` al tbody; empty states inline con iconos y CTAs. | 652 → 429 |
| `resources/js/employees-index.js` | **Nuevo**: IIFE con debounce search, AJAX filtros, AJAX paginación, loading/error states, renderizado de filas desde JSON, paginación con elipsis, counter update. | — |
| `resources/js/app.js` | Agregó lazy loading del módulo employees via `import()` dinámico cuando detecta `#employees-table`. | 681 → 685 |

## Cambios de UX

1. **Filtros ahora funcionales**: Los selects de cargo/departamento/sede se poblan con datos reales (distinct) del controller. Antes siempre estaban vacíos.

2. **Chips de filtro activos**: Cuando hay filtros activos, se muestran badges con iconos y botón × para quitar cada filtro individualmente. Colores: cat-blue (búsqueda), cat-purple (puesto), cat-green (sede), cat-amber (sin huellas/enrolar).

3. **Empty state mejorado**: Cuando no hay resultados, se muestra un estado vacío inline con icono, título, descripción y CTA para limpiar filtros o crear empleado.

4. **Progressive enhancement**: La tabla se renderiza desde Blade (SSR). El JS solo maneja interacciones AJAX. Si JS no carga, el form funciona con SSR normal.

5. **Responsive**: Se mantiene el patrón `.table-cards` existente que convierte la tabla en tarjetas en móviles.

## Arquitectura del JS extraído

- **Patrón**: IIFE auto-contenida, sin dependencias de globals (excepto `window.dashConfirmAll`)
- **Carga**: Lazy via `import()` dinámico desde `app.js` cuando detecta `#employees-table`
- **Estados manejados**: loading (spinner), error (retry button), empty (filtered/unfiltered), success (table rows + pagination)
- **Eventos**: debounce search (300ms), filter change → fetch, form submit → AJAX, pagination click → fetch, delegated click handlers

## Decisiones tomadas

1. **Form action = `route('employees.index')`** (no `search`): Para que el SSR funcione cuando JS no está disponible. El JS intercepta el submit y hace AJAX al endpoint `/employees/search`.

2. **Lazy loading via `import()`**: En lugar de entry point adicional en Vite, se importa dinámicamente desde `app.js`. Solo se carga cuando la vista tiene `#employees-table`.

3. **Empty state inline**: En lugar de usar `partials.empty-state` (que no encontré en el proyecto), se implementó inline con el mismo estilo visual. Esto evita dependencias y mantiene consistencia.

4. **Controller search() enriquecido**: Se agregaron los mismos filtros que `index()` al endpoint JSON para que el AJAX funcione igual que el SSR.

## Checklist

- [x] Usé el sistema de tokens y componentes Bootstrap 5 ya existentes (cat-blue, cat-green, badge-with-dot, ref-chip, etc.)
- [x] Verifiqué estado vacío, de carga y de error — no solo el happy path
- [x] El cambio se limita a `Allowed files` del Task Boundary activo
- [x] No introduje un framework CSS/JS nuevo ni un sistema visual paralelo
- [x] No modifiqué lógica de servidor más allá de lo necesario (solo EmployeeController para pasar variables y enriquecer search)
- [x] No aprobé mi propio código
- [x] No creé archivos temporales/scratch

## Generated (temporal): ninguno
