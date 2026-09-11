# Plan de Acción - Problemas Reportados

## Resumen del Problema

Tres problemas identificados:
1. **academia/ciclos**: Página vacía en `/academia/ciclos` - no muestra contenido
2. **Ciclo de trabajo no sincronizado**: En algunas vistas no se respeta el ciclo seleccionado en el header
3. **employees**: Mejorar diseño de `/employees`

---

## Hallazgos Técnicos Detallados

### Problema 1: `/academia/ciclos` página vacía

**Causa Raíz**: 
- `CicloController::index()` llama a `$this->cicloService->resolve($request)` que lanza `NoCiclosConfiguradosException` si no hay ciclos activos
- La vista `academia.ciclos.index` no maneja el caso vacío - muestra tabla vacía sin mensaje
- Existe `empty-ciclos.blade.php` pero **nunca se usa** en ningún controlador

**Flujo actual**:
```
CicloController::index() 
  → CicloActualService::resolve() 
    → Si no hay ciclos activos: throw NoCiclosConfiguradosException (500 error)
    → Si hay ciclos: retorna $ciclo actual + $ciclos paginados
  → Vista academia.ciclos.index (tabla vacía si $ciclos está vacío)
```

**Archivos involucrados**:
- `app/Http/Controllers/Academia/CicloController.php` (línea 26-43)
- `app/Services/CicloActualService.php` (línea 58-72 - `getDefaultCiclo()`)
- `resources/views/academia/ciclos/index.blade.php`
- `resources/views/academia/empty-ciclos.blade.php` (no usado)

---

### Problema 2: Ciclo de trabajo no se sincroniza en algunas vistas

**Causa Raíz**: 
El sistema tiene **dos mecanismos** para obtener el ciclo actual que no son idénticos:

1. **Header (layout)**: `CicloActualService::current($request)` - prioridad: URL param → sesión → default activo
2. **Controladores Academia**: `CicloActualService::resolve($request)` - prioridad: URL param → sesión → default activo **(guarda en sesión si viene por URL)**

**Diferencia crítica**: `resolve()` **guarda en sesión** cuando el ciclo viene por URL (`ciclo_principal`), mientras que `current()` **no lo hace**. Esto causa que:
- Usuario selecciona ciclo en header → AJAX a `/academia/set-ciclo` → guarda en sesión
- Usuario navega a otra vista Academia → controlador usa `resolve()` → lee de sesión ✓
- Usuario recarga página → `resolve()` lee sesión ✓
- **PERO**: Si el usuario va directo a URL con `?ciclo_principal=X` → `resolve()` guarda en sesión, pero `current()` en header no lo refleja hasta recargar

**Vistas afectadas**: 
- Todas las vistas Academia usan `resolve()` correctamente
- El **header** usa `current()` - inconsistencia menor
- **Módulo Employees** no usa ciclo académico (dominio distinto) - no es bug, es diseño

**Archivos involucrados**:
- `app/Services/CicloActualService.php` (línea 22-43 `resolve()` vs 82-102 `current()`)
- `resources/views/layouts/admin.blade.php` (línea 220-266 header dropdown + línea 342-368 JS `setCiclo()`)
- Todos los controladores Academia (usan `resolve()` consistentemente)

---

### Problema 3: Diseño `/employees` - variables faltantes y UX

**Hallazgos**:
1. **Variables no pasadas**: `EmployeeController::index()` no pasa `$cargos`, `$departamentos`, `$sedes` que la vista `employees.index` espera (líneas 19-53)
2. **JavaScript inline masivo**: 273 líneas de JS en la vista (líneas 374-650) - difícil de mantener
3. **Filtros rotos**: Los selects de cargo/departamento/sede siempre están vacíos
4. **Duplicación lógica**: La vista renderiza tabla en Blade Y tiene JS que re-renderiza via AJAX (`fetchEmployees`)
5. **Inconsistencia visual**: Usa clases custom (`cat-blue`, `badge-with-dot`, `ref-chip`) no documentadas

**Archivos involucrados**:
- `app/Http/Controllers/EmployeeController.php` (línea 24-62 `index()`)
- `resources/views/employees/index.blade.php` (36494 bytes - muy grande)

---

## Plan de Cambios por Archivo

### Grupo A: Fix `/academia/ciclos` vacío

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `app/Http/Controllers/Academia/CicloController.php` | En `index()`: catch `NoCiclosConfiguradosException` y redirigir a `empty-ciclos` o crear vista vacía manejada | Fix |
| `resources/views/academia/ciclos/index.blade.php` | Agregar `@if($ciclos->isEmpty())` mostrar estado vacío con CTA a crear ciclo | Fix |
| `resources/views/academia/empty-ciclos.blade.php` | Mantener como fallback (ya existe, bien diseñada) | - |

### Grupo B: Sincronización ciclo de trabajo

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `app/Services/CicloActualService.php` | Unificar lógica: hacer que `current()` use la misma lógica que `resolve()` (incluyendo guardar en sesión si URL param) O documentar diferencia intencional | Refactor |
| `resources/views/layouts/admin.blade.php` | En header: usar `resolve()` en lugar de `current()` para consistencia, o asegurar que `current()` se comporte igual | Fix |

### Grupo C: Mejora `/employees`

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `app/Http/Controllers/EmployeeController.php` | En `index()`: agregar query distinct para `$cargos`, `$departamentos`, `$sedes` y pasarlos a la vista | Feature |
| `resources/views/employees/index.blade.php` | Extraer JS a archivo separado `resources/js/employees-index.js`, cargar via Vite | Refactor |
| `resources/views/employees/index.blade.php` | Simplificar: quitar duplicación Blade+JS, usar solo SSR + enhancement progresivo | Refactor |
| `resources/views/employees/index.blade.php` | Mejorar UX: agrupar columnas, mejorar responsive, chips de filtro más claros | UX |

---

## Nivel de Testing Recomendado

**Nivel 3** (`.opencode/policies/test-levels.md`)

**Justificación**:
- Cambios en controladores que afectan flujo de datos (ciclo actual)
- Cambios en servicio compartido (`CicloActualService`) usado por 10+ controladores
- Modificación de vista crítica (`employees.index`) con lógica JS compleja
- Requiere: tests de integración para controladores Academia, test de servicio `CicloActualService`, test de renderizado `employees.index`

---

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| `CicloActualService` cambio rompe controladores Academia | Media | Alto | Test suite existente `AcademiaHierarchyTest` + tests manuales en 3+ vistas |
| Variables `$cargos`/`$departamentos`/`$sedes` consultas lentas | Baja | Medio | Usar `distinct()` + índices, limitar resultados |
| JS extraído rompe funcionalidad AJAX empleados | Media | Alto | Test manual exhaustivo: búsqueda, paginación, filtros, acciones |
| Excepción `NoCiclosConfiguradosException` no catch en otros controladores | Baja | Medio | Verificar que todos usan `resolve()` que ya la maneja internamente |

---

## Decisiones de Arquitectura (registrar en decisions.md)

1. **CicloActualService**: Unificar `resolve()` y `current()` en un solo método público `getCurrent()` con comportamiento consistente (URL → sesión → default, guardando en sesión si URL param)
2. **Employees index**: Migrar a patrón SSR + progressive enhancement (como `academia.dashboard`), eliminar duplicación Blade/JS
3. **Empty states**: Usar patrón consistente `empty-ciclos.blade.php` para todos los índices Academia vacíos