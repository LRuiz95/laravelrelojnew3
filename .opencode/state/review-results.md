# Review Results — TASK-2026-0911

**Fecha:** 2026-09-11  
**Reviewer:** @reviewer (nemotron-3-ultra-free)  
**Pasadas:** 2 (modelo único — segundo modelo no disponible; hallazgos de severidad CRITICAL/MEDIUM requieren HUMAN REVIEW si no hay consenso)

---

## Resumen ejecutivo

| Área | Estado | Severidad máxima |
|------|--------|------------------|
| Correctness | ❌ **BLOQUEANTE** | CRITICAL — Falta relación `sede` en modelo Employee |
| Backward Compatibility | ⚠️ **RIESGO** | MEDIUM — Cambio de comportamiento en `CicloActualService::resolve()` |
| Performance | ⚠️ **PREOCUPANTE** | MEDIUM — Queries `distinct` sin índices en `cargo`, `departamento` |
| Maintainability | ⚠️ **ACEPTABLE** | LOW — Duplicación lógica Blade/JS en `employees-index.js` |
| Regression | ⚠️ **PRESENTE** | MEDIUM — Test `EmployeeIndexTest` falla (500) |
| Consistencia | ✅ **OK** | — Patrones alineados con el proyecto |

**Veredicto:** **NO APROBADO** — Requiere fix crítico (relación `sede` faltante) y mitigaciones de performance/regresión antes de DONE.

---

## 1. Correctness ❌ CRITICAL

### Hallazgo CRITICAL-001: Relación `sede` faltante en `Employee` model
- **Archivos afectados:** `app/Models/Employee.php`, `app/Http/Controllers/EmployeeController.php` (línea 79), `resources/views/employees/index.blade.php` (línea 220), `resources/js/employees-index.js` (línea 110)
- **Descripción:** `EmployeeController::search()` hace `->with(['devices', 'sede'])` y la vista/JS acceden a `$employee->sede->descripcion`, pero **el modelo `Employee` no define la relación `sede()`**.
- **Evidencia:** Test `EmployeeIndexTest::test_employees_returns_200` falla con HTTP 500.
- **Impacto:** Ruta `/employees` completamente rota (500) para cualquier usuario.
- **Fix requerido:** Agregar en `Employee.php`:
  ```php
  public function sede(): BelongsTo
  {
      return $this->belongsTo(\App\Models\Academia\Sede::class, 'id_campus', 'id_campus');
  }
  ```
- **Nota:** La tabla `sedes` existe (`App\Models\Academia\Sede`, tabla `sedes`, PK `id_campus`). Otros modelos academia (`Profesor`, `Grupo`, `HorarioDet`, `Alumno`, `Curso`) ya usan esta relación correctamente.

### Hallazgo MEDIUM-001: `CicloController::index()` usa `resolve()` que ahora lanza excepción
- **Archivo:** `app/Http/Controllers/Academia/CicloController.php` línea 42
- **Descripción:** El `try-catch` captura `NoCiclosConfiguradosException`, pero `resolve()` ahora llama a `getCurrent()` que **lanza la excepción** si no hay ciclos (línea 75 de `CicloActualService`). El catch funciona, pero el flujo es: `resolve()` → `getCurrent()` → `getDefaultCiclo()` → `throw`.
- **Verificación:** El test `test_academia_ciclos_shows_empty_state_when_no_ciclos` pasa, pero el test crea ciclos en `setUp()` — no prueba el caso real de BD vacía.
- **Recomendación:** Añadir test explícito con BD vacía (sin ciclos) para validar el empty state end-to-end.

---

## 2. Backward Compatibility ⚠️ MEDIUM

### Hallazgo MEDIUM-002: Cambio semántico en `CicloActualService::resolve()`
- **Archivo:** `app/Services/CicloActualService.php` líneas 47-50
- **Antes (implícito):** `resolve()` tenía su propia lógica de prioridad (probablemente sesión → default).
- **Ahora:** `resolve()` es wrapper de `getCurrent()` con prioridad **URL param → sesión → default** y **guarda en sesión si viene por URL**.
- **Consumidores afectados (26 llamadas en 11 controladores Academia):**
  - `AlumnoController` (4), `GrupoController` (3), `DashboardController` (1), `KardexController` (4), `CicloController` (1), `PlanController` (1), `HorarioController` (5), `CursoController` (4), `ProfesorController` (3), `ApiController`, `CicloController`
- **Riesgo:** Controladores que esperaban que `resolve()` **no** modificara la sesión ahora la modificarán si hay `?ciclo_principal=` en la URL. Esto cambia el estado global del usuario silenciosamente.
- **Mitigación:** Documentar el cambio en `decisions.md` y verificar que ningún controlador académico genere URLs con `ciclo_principal` sin intención de persistir la selección.

### Hallazgo MEDIUM-003: `CicloActualService::current()` ahora retorna `null` en vez de lanzar
- **Archivo:** `app/Services/CicloActualService.php` líneas 85-92
- **Consumidor:** `resources/views/layouts/admin.blade.php` línea 221 (header global)
- **Comportamiento:** Correcto para el header (debe degradar graciosamente), pero cualquier otro uso de `current()` que esperara excepción recibirá `null`.
- **Verificación:** Solo el header usa `current()` — riesgo bajo.

---

## 3. Performance ⚠️ MEDIUM

### Hallazgo MEDIUM-004: Queries `distinct` sin índices en `cargo` y `departamento`
- **Archivo:** `app/Http/Controllers/EmployeeController.php` líneas 57-58
- **Queries:**
  ```php
  $cargos = Employee::whereNotNull('cargo')->where('cargo', '!=', '')->distinct()->pluck('cargo')->sort()->values();
  $departamentos = Employee::whereNotNull('departamento')->where('departamento', '!=', '')->distinct()->pluck('departamento')->sort()->values();
  ```
- **Estado índices (migración `2026_09_05_010214`):**
  - ✅ `id_campus` → `idx_employees_campus`
  - ✅ `status_actual` → `employees_status_actual_index`
  - ✅ `(type, status_actual)` → `idx_employees_type_status`
  - ❌ **`cargo` — SIN ÍNDICE**
  - ❌ **`departamento` — SIN ÍNDICE**
- **Impacto:** En tabla `employees` con >5k registros, cada carga de `/employees` hará **2 full table scans** para poblar los filtros. Sin `LIMIT`, devuelven TODOS los valores distintos.
- **Recomendación urgente:**
  1. Añadir índices en migración nueva: `$table->index('cargo'); $table->index('departamento');`
  2. Añadir `->limit(100)` (o valor razonable) a las queries `distinct` para evitar memoria excesiva si hay cardinalidad alta.
  3. Considerar cachear estas listas (TTL 5-10 min) ya que cambian poco.

### Hallazgo LOW-001: Query `sedes` con JOIN innecesario en `index()`
- **Archivo:** `app/Http/Controllers/EmployeeController.php` líneas 59-63
- **Query actual:** JOIN a tabla `campus` para traer `descripcion`.
- **Observación:** La tabla `campus` parece ser `sedes` (modelo `Academia\Sede`, tabla `sedes`). El JOIN funciona pero es confuso.
- **Recomendación:** Verificar si `campus` y `sedes` son la misma tabla o tablas distintas. Si son la misma, usar el nombre consistente.

---

## 4. Maintainability ⚠️ LOW

### Hallazgo LOW-002: Duplicación de lógica de renderizado de filas (Blade ↔ JS)
- **Archivos:** `resources/views/employees/index.blade.php` (líneas 190-392) vs `resources/js/employees-index.js` (función `buildRow`, líneas 74-216)
- **Descripción:** La fila de la tabla se renderiza **dos veces**: una en Blade (SSR initial) y otra en JS (AJAX). Cualquier cambio en columnas, badges, iconos, tooltips, acciones → **dos lugares para tocar**.
- **Riesgo:** Divergencia silenciosa (ej. badge nuevo en Blade no aparece en AJAX, o viceversa).
- **Mitigación recomendada:**
  - Opción A: Extraer la fila a un **partial Blade** (`_employee-row.blade.php`) y renderizarlo vía `render()` en el controlador AJAX (devuelve HTML, no JSON). El JS solo hace `tbody.innerHTML = response.html`.
  - Opción B: Mantener JSON pero mover `buildRow` a un **componente Vue/Alpine** o template JS compartido (más trabajo).
  - Dado el scope actual, **Opción A es preferible** y coherente con patrón Laravel "HTML over the wire".

### Hallazgo LOW-003: `employees-index.js` usa `window.location.origin + '/devices/'` hardcoded
- **Archivo:** `resources/js/employees-index.js` línea 135
- **Código:** `href="' + window.location.origin + '/devices/' + d.id + '"`
- **Problema:** No usa `route('devices.show', d.id)` — frágil si cambian rutas o hay subdirectorio.
- **Fix:** Pasar `devices.show` route base via `data-` attribute en la tabla o inyectar en JS desde Blade.

---

## 5. Regression ⚠️ MEDIUM

### Hallazgo MEDIUM-005: Test `EmployeeIndexTest` falla (500)
- **Archivo:** `tests/Feature/EmployeeIndexTest.php` línea 51
- **Causa raíz:** Hallazgo CRITICAL-001 (relación `sede` faltante).
- **Estado:** 1 test fallando, 7 pasando. El test actual solo verifica status 200 — no cubre filtros, paginación, AJAX search.
- **Cobertura esperada (Task Boundary):** "Tests pasando: EmployeeIndex (3+)" — actualmente **0/3+** funcionales.

### Hallazgo LOW-004: Tests `CicloControllerTest` no cubren caso real BD vacía
- **Archivo:** `tests/Feature/CicloControllerTest.php` líneas 39-54
- **Problema:** `setUp()` crea 2 ciclos. El test `test_academia_ciclos_shows_empty_state_when_no_ciclos` **no limpia la BD** — solo verifica que no sea 500. No valida que se renderice `empty-ciclos.blade.php`.
- **Recomendación:** Añadir test con `RefreshDatabase` y sin ciclos creados, verificar `assertSee('No hay ciclos registrados')`.

---

## 6. Consistencia ✅ OK

| Patrón | Estado | Comentario |
|--------|--------|------------|
| Service wrapper (`resolve` → `getCurrent`) | ✅ | Limpio, documentado en PHPDoc |
| Progressive enhancement (SSR + JS) | ✅ | Coherente con `resources/js/app.js` pattern |
| Filter chips + active badges | ✅ | Coherente con UI del proyecto |
| Exception handling en controller | ✅ | `try-catch` + empty state view |
| Distinct queries para filtros | ✅ | Patrón usado en otros controladores |
| Test naming `test_<feature>_<expectation>` | ✅ | Consistencia PHPUnit |

---

## Recomendaciones priorizadas

### 🔴 CRITICAL — Bloquea DONE (fix obligatorio)
1. **Agregar relación `sede()` en `Employee.php`** (ver fix en CRITICAL-001).
2. **Ejecutar test suite completa** tras fix y confirmar `EmployeeIndexTest` pasa.

### 🟠 MEDIUM — Debería resolverse antes de DONE
3. **Añadir índices en `cargo` y `departamento`** (migración nueva) + `->limit(100)` en queries distinct.
4. **Documentar cambio semántico de `resolve()`** en `.opencode/state/decisions.md` y validar que no rompe controladores academia.
5. **Añadir test real de BD vacía** para `CicloController::index()` empty state.
6. **Expandir `EmployeeIndexTest`**: al menos 3 tests (carga inicial, filtro búsqueda, paginación AJAX).

### 🟡 LOW — Deuda técnica (puede ir a backlog)
7. **Refactor fila de tabla a partial Blade** + endpoint AJAX que devuelva HTML (elimina duplicación Blade/JS).
8. **Fix `window.location.origin` hardcoded** en `employees-index.js`.
9. **Verificar tabla `campus` vs `sedes`** — unificar nomenclatura.

---

## Evidencia de doble pasada (consenso)

> **Nota:** Segunda pasada con modelo distinto no disponible en este entorno. Hallazgos de severidad **CRITICAL** y **MEDIUM** requieren **HUMAN REVIEW** por política de consenso (`.opencode/policies/consensus.md`) antes de auto-aprobar.

| Hallazgo | Pasada 1 | Pasada 2 | Consenso |
|----------|----------|----------|----------|
| CRITICAL-001 (relación sede) | CRITICAL | — | **REQUIERE HUMAN REVIEW** |
| MEDIUM-002 (resolve() semántica) | MEDIUM | — | **REQUIERE HUMAN REVIEW** |
| MEDIUM-004 (índices faltantes) | MEDIUM | — | **REQUIERE HUMAN REVIEW** |
| MEDIUM-005 (test fallando) | MEDIUM | — | **REQUIERE HUMAN REVIEW** |

---

## Archivos generados/modificados en esta review
- Ninguno (review es de solo lectura)

---

**Firma:** @reviewer — `nemotron-3-ultra-free`  
**Próximo paso:** `team-lead` debe escalar a humano para consenso en hallazgos CRITICAL/MEDIUM, o autorizar fixes y re-ejecutar review.