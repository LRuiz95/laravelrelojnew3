# Task Boundary: TASK-ACAD-001 — Jerarquía de Datos y Catálogos Academia

## Task ID
`TASK-ACAD-001`

## Scope
Rediseñar la jerarquía de datos del módulo academia para que:
1. El **Ciclo** sea el contexto principal y obligatorio en todas las vistas operativas
2. **Grupos, Alumnos, Horarios, Cursos, Kardex** filtren por el ciclo seleccionado
3. **Catálogos globales** (Materias, Planes, Niveles, Turnos, Sedes, Profesores) sean accesibles sin filtro de ciclo pero con métricas de uso por ciclo
4. Exista un **selector de ciclo unificado** en todo el módulo academia
5. La **sincronización Firebird** respete y valide esta jerarquía

## Problem Statement
Actualmente la navegación es inconsistente: Dashboard tiene selector prominente con AJAX; Grupos/Alumnos/Horarios/Cursos/Kardex tienen solo enlace "Cambiar ciclo"; Planes/Materias/Profesores no tienen selector. AlumnoController muestra TODOS los alumnos globalmente en lugar de solo los inscritos en el ciclo. ProfesorController muestra todos los profesores sin opción de filtrar por ciclo.

## Root Cause
Falta de arquitectura explícita de la jerarquía ciclo→grupos→(alumnos,horarios,cursos,kardex) y catálogos globales transversales. Los controllers y vistas se desarrollaron de forma incremental sin patrón unificado.

## Affected Modules
- `app/Models/Academia/` — Scopes y relaciones (Alumno, Profesor, Grupo, HorarioDet, Curso, AlumnoKardex)
- `app/Http/Controllers/Academia/` — AlumnoController, ProfesorController, PlanController, GrupoController, CursoController, HorarioController, KardexController, ApiController
- `resources/views/academia/` — Todas las vistas index/show + nuevo layout + componente selector
- `routes/web.php` — Verificar propagación `ciclo_principal`
- `app/Services/SyncStrategies/` — CycleDirectSync, CatalogSmartSync (validación)

## Allowed Files

### Models (Scopes/Relaciones)
- `app/Models/Academia/Alumno.php` — Agregar `scopeInscritosEnCiclo()`, `scopeActivo()` ya existe
- `app/Models/Academia/Profesor.php` — Agregar `scopeConHorariosEnCiclo()`, `scopeTodos()`
- `app/Models/Academia/Grupo.php` — Solo lectura/verificación
- `app/Models/Academia/HorarioDet.php` — Solo lectura/verificación
- `app/Models/Academia/Curso.php` — Solo lectura/verificación
- `app/Models/Academia/AlumnoKardex.php` — Solo lectura/verificación

### Controllers (Lógica de filtrado + pasar $ciclo a vistas)
- `app/Http/Controllers/Academia/AlumnoController.php` — `index()` usa `inscritosEnCiclo`, pasa `$ciclo`
- `app/Http/Controllers/Academia/ProfesorController.php` — `index()` agrega toggle filtro ciclo, pasa `$ciclo`
- `app/Http/Controllers/Academia/PlanController.php` — `index()` agrega badge ciclos, pasa `$ciclo` (solo visual)
- `app/Http/Controllers/Academia/GrupoController.php` — Verificar pasa `$ciclo` ✓
- `app/Http/Controllers/Academia/CursoController.php` — Verificar pasa `$ciclo` ✓
- `app/Http/Controllers/Academia/HorarioController.php` — Verificar pasa `$ciclo` ✓
- `app/Http/Controllers/Academia/KardexController.php` — Verificar pasa `$ciclo` ✓
- `app/Http/Controllers/Academia/ApiController.php` — Estandarizar parámetro `ciclo` requerido

### Views (Nuevo layout + componente + actualizaciones)
- `resources/views/layouts/academia.blade.php` — **NUEVO** layout base con selector
- `resources/views/components/academia/ciclo-selector.blade.php` — **NUEVO** componente reutilizable
- `resources/views/academia/alumnos/index.blade.php` — Usar selector, mostrar solo inscritos, col "Grupo"
- `resources/views/academia/profesores/index.blade.php` — Usar selector, toggle "Con horarios en ciclo"
- `resources/views/academia/planes/index.blade.php` — Usar selector (visual), badge "Usado en X ciclos"
- `resources/views/academia/grupos/index.blade.php` — Usar selector (reemplazar botón)
- `resources/views/academia/cursos/index.blade.php` — Usar selector
- `resources/views/academia/horarios/clase.blade.php` — Usar selector
- `resources/views/academia/horarios/profesor.blade.php` — Usar selector
- `resources/views/academia/horarios/aula.blade.php` — Usar selector
- `resources/views/academia/horarios/base.blade.php` — Usar selector
- `resources/views/academia/horarios/persona.blade.php` — Usar selector
- `resources/views/academia/kardex/index.blade.php` — Usar selector
- `resources/views/academia/kardex/show.blade.php` — Usar selector
- `resources/views/academia/kardex/historial.blade.php` — Usar selector (solo visual, historial trasciende ciclo)
- `resources/views/academia/kardex/print.blade.php` — Usar selector
- `resources/views/academia/dashboard/index.blade.php` — Ya tiene selector ✓ (verificar consistencia)

### Routes
- `routes/web.php` — Verificar/ajustar rutas academia para aceptar `ciclo_principal`

### Sync (Validación only — no cambios esperados)
- `app/Services/SyncStrategies/CycleDirectSync.php` — Solo lectura/verificación orden FK
- `app/Services/SyncStrategies/CatalogSmartSync.php` — Solo lectura/verificación catálogos globales
- `app/Http/Controllers/FirebirdController.php` — Solo lectura/verificación UI grupos

## Forbidden Files
- `app/Models/Academia/Ciclo.php` — No modificar (es la raíz, ya correcto)
- `app/Models/Academia/Materia.php` — Catálogo global, no tocar
- `app/Models/Academia/Plan.php` — Catálogo global, no tocar
- `app/Models/Academia/Nivel.php` — Catálogo global, no tocar
- `app/Models/Academia/Turno.php` — Catálogo global, no tocar
- `app/Models/Academia/Sede.php` — Catálogo global, no tocar
- `app/Models/Academia/SesionBase.php` — Catálogo global, no tocar
- `app/Models/Academia/MetodoEval.php` — Catálogo global, no tocar
- `app/Models/Academia/Contrato.php` — Catálogo global, no tocar
- `app/Models/Academia/AlumnoGrupo.php` — Pivot, no tocar
- `app/Models/Academia/CursoDet.php` — No tocar
- `database/migrations/` — NO crear/modificar migraciones (schema ya correcto)
- `app/Services/CicloActualService.php` — Ya funciona correctamente
- `app/Services/FirebirdReader.php` — No tocar
- `app/Jobs/FirebirdSyncJob.php` — No tocar
- `app/Models/FirebirdSync.php` — No tocar
- `app/Models/FirebirdSyncItem.php` — No tocar

## Agents Involved (Orden de Ejecución)

1. **laravel** — Models (scopes), Controllers (lógica filtrado), Routes
2. **frontend** — Componente Blade CicloSelector, Layout academia, Vistas index/show
3. **integration** — Validar CycleDirectSync respeta jerarquía, validar CatalogSmartSync catálogos globales
4. **tester** — Nivel 3: Tests de integración DB (scopes, controllers, sync)
5. **security** — Severity MEDIUM: Verificar no IDOR, parameter binding, validación ciclo
6. **reviewer** — Code review completo

## Expected Outputs

### Deliverables
1. **Componente** `resources/views/components/academia/ciclo-selector.blade.php` funcional
2. **Layout** `resources/views/layouts/academia.blade.php` con selector en header
3. **AlumnoController::index()** retorna solo alumnos inscritos en ciclo (con eager load grupo)
4. **ProfesorController::index()** con toggle "Con horarios en ciclo" / "Todos"
5. **PlanController::index()** con badge "Usado en X ciclos" (count distinct ciclos via horarios_det + cursos_det)
4. **Todas las vistas academia** usan selector unificado y reciben `$ciclo`
5. **API endpoints** consistentes requiriendo `ciclo` param
6. **Tests** en `state/test-results.md` cubriendo scopes, controllers, selector, sync

### Evidencia Requerida (por agente)
- **laravel**: `state/findings.md` con diff de cambios en models/controllers
- **frontend**: `state/findings.md` con lista de vistas modificadas + capturas selector
- **integration**: `state/findings.md` validando orden sync y catálogos
- **tester**: `state/test-results.md` con resultados Nivel 3
- **security**: `state/security-results.md` severity MEDIUM
- **reviewer**: `state/review-results.md` sin observaciones bloqueantes

## Testing Level
**Nivel 3** (DB/auth/permisos/integración entre sistemas) — Ver `.opencode/policies/test-levels.md`

Justificación: Cambia queries con joins complejos (Alumno→AlumnoGrupo→Grupo→Ciclo), afecta visibilidad de datos por ciclo, involucra sync Firebird↔MySQL.

## Security Review
**Requerido: SÍ** — Severity anticipada: **MEDIUM**
- Pasa por `security` + `reviewer` (doble pasada)
- No requiere HUMAN APPROVAL (no CRITICAL)
- Verificar: IDOR al manipular `ciclo_principal`, parameter binding en scopes, validación existencia ciclo

## Human Approval
**NO requerido** — No toca rutas críticas (auth, pagos, schema), severity MEDIUM.

## Rollback Plan
Si hay regresión:
1. Revertir `AlumnoController::index()` a query global + scope `Activo()`
2. Revertir `ProfesorController::index()` a query global sin toggle
3. Revertir vistas a versión anterior (git checkout)
4. Selector de ciclo: deshabilitar layout academia y volver a layouts.admin individual

## Dependencias Entre Pasos
```
1. Models (scopes) 
   ↓
2. Controllers (usan scopes nuevos)
   ↓
3. Componente Selector + Layout (independiente, puede paralelizar)
   ↓
4. Vistas (usan componente + reciben $ciclo de controllers)
   ↓
5. Validación Sync (lectura only)
   ↓
6. Tests integración (requieren 1-4 completados)
   ↓
7. Security + Reviewer (paralelo tras tests)
```

## Estimación
- **Models + Controllers**: 4-6 horas
- **Frontend (componente + layout + 12 vistas)**: 6-8 horas
- **Validación Sync**: 1-2 horas
- **Testing Nivel 3**: 3-4 horas
- **Total**: ~15-20 horas