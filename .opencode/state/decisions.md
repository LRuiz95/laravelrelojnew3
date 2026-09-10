# Decisiones de Arquitectura — Módulo Academia

## ADR-001: Jerarquía Ciclo → Grupos → (Alumnos, Horarios, Cursos, Kardex)

**Fecha**: 2026-09-09
**Estado**: Aceptada
**Contexto**: El sistema tiene tablas con triada de ciclo (inicial,final,periodo) y catálogos globales. La navegación actual es inconsistente: algunas vistas filtran por ciclo, otras no.

**Decisión**: 
- **Ciclo es el contexto raíz** para todas las operaciones académicas operativas (grupos, inscripciones, horarios, cursos, calificaciones).
- **Catálogos globales** (materias, planes, niveles, turnos, sedes, profesores) son transversales y NO filtran por ciclo, pero muestran métricas de uso por ciclo.
- **Profesores** son catálogo global PERO se ofrece vista filtrada "con horarios en ciclo actual" por defecto.

**Consecuencias**:
- `AlumnoController::index()` cambia de global a filtrado por ciclo (breaking change mitigado con pestaña "Catálogo completo").
- `ProfesorController::index()` agrega toggle de filtro por ciclo.
- `PlanController::index()` permanece global pero agrega badges de uso por ciclo.
- Selector de ciclo unificado en layout academia.

---

## ADR-002: Selector de Ciclo Unificado como Componente Blade

**Fecha**: 2026-09-09
**Estado**: Aceptada
**Contexto**: Cada vista implementa su propio selector o enlace "Cambiar ciclo". El dashboard tiene uno prominente con AJAX; otras vistas solo tienen botón que redirige a ciclos.index.

**Decisión**: Crear componente `components/academia/ciclo-selector.blade.php` reutilizable que:
- Acepta `$ciclo` actual y `$ciclos` colección
- Renderiza `<select name="ciclo_principal">` + botón submit
- Opcionalmente: endpoint AJAX para actualización sin reload (como dashboard)
- Se incluye en layout base `layouts/academia.blade.php`

**Consecuencias**:
- Consistencia visual y UX en todo el módulo academia.
- Propagación automática de `ciclo_principal` en todas las URLs.
- Facilita testing del flujo de cambio de ciclo.

---

## ADR-003: Sync Firebird Respeta Jerarquía (Ya Implementado, Validar)

**Fecha**: 2026-09-09
**Estado**: Validada
**Contexto**: `CycleDirectSync` ya implementa fases: 1) tablas directas por ciclo (CICLOS→GRUPOS→CURSOS→ALUMNOS_GRUPOS→HORARIOS_DET), 2) alumnos por ciclo (ALUMNOS_NIVELES filtrado + ALUMNOS global por IDs). `CatalogSmartSync` maneja catálogos globales sin filtro de ciclo.

**Decisión**: Mantener arquitectura actual de sync. Solo validar que:
- Orden de tablas respeta FK (CICLOS antes que GRUPOS, GRUPOS antes que HORARIOS_DET, etc.)
- Catálogos globales NUNCA filtran por ciclo
- UI de FirebirdController separa visualmente "Catálogos globales" vs "Datos por ciclo"

---

## ADR-004: Nivel de Testing Asignado = Nivel 3 (Integración DB Amplia)

**Fecha**: 2026-09-09
**Estado**: Aceptada
**Justificación**: 
- Cambia comportamiento de queries en controladores críticos (Alumno, Profesor, Plan)
- Afecta integridad referencial (filtrado por ciclo via FK compuestas)
- Involucra sincronización Firebird ↔ MySQL
- Requiere suite relevante: models (scopes), controllers (index/show), sync strategies

**Cobertura mínima**:
- Scopes de modelos: `inscritosEnCiclo`, `conHorariosEnCiclo`, `porCiclo`
- Controllers: index con/sin ciclo, show con ciclo
- Sync: CycleDirectSync orden y filtrado
- Componente CicloSelector: cambio + persistencia

---

## ADR-005: Seguridad — Severidad Esperada MEDIUM

**Fecha**: 2026-09-09
**Estado**: Aceptada
**Análisis**: 
- No toca autenticación, pagos, ni datos financieros directamente.
- Cambia visibilidad de datos académicos (alumnos, profesores, calificaciones) por ciclo.
- Riesgo: Filtro incorrecto podría exponer datos de otro ciclo (information disclosure).
- No requiere HUMAN APPROVAL obligatorio, pero pasa por `security` + `reviewer`.

**Checklist seguridad**:
- [ ] Verificar que scopes usan parameter binding (no raw SQL)
- [ ] Verificar que `CicloActualService::resolve()` valida existencia del ciclo
- [ ] Verificar que API endpoints validan parámetro `ciclo`
- [ ] Verificar que no hay IDOR al cambiar `ciclo_principal` manualmente