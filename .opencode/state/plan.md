# Plan: Jerarquía de Datos y Catálogos del Módulo Academia

## 1. Análisis del Estado Actual

### 1.1 Tablas con Triada de Ciclo (INICIAL, FINAL, PERIODO) — **DEPENDEN DE CICLO**

| Tabla | PK Lógica | Descripción |
|-------|-----------|-------------|
| `ciclos` | (inicial, final, periodo) | Catálogo raíz de ciclos escolares |
| `grupos` | (codigo_grupo, inicial, final, periodo) | Grupos pertenecen a un ciclo |
| `alumnos_grupos` | (numero_alumno, codigo_grupo, inicial, final, periodo) | Inscripciones alumno-grupo en ciclo |
| `horarios_det` | (inicial, final, periodo, codigo_grupo, clave_profesor, clave_asignatura, dia, sesion) | Clases programadas en ciclo |
| `cursos` | (inicial, final, periodo, clave_curso) | Cursos ofertados en ciclo |
| `alumnos_kardex` | (numero_alumno, inicial, final, periodo, clave_asignatura, id_eval) | Calificaciones en ciclo |
| `alumnos_niveles` | (numero_alumno, inicial, final, periodo) | Nivel/turno del alumno por ciclo |

### 1.2 Catálogos Globales — **NO FILTRAN POR CICLO**

| Tabla | PK | Descripción |
|-------|-----|-------------|
| `materias` | (clave_asignatura, id_plan) | Catálogo por plan de estudios |
| `planes` | id_plan | Planes de estudio |
| `niveles` | nivel | Niveles educativos (MS, SU, etc.) |
| `turnos` | turno | Turnos (MA, VE, etc.) |
| `sedes` | id_campus | Sedes/Campus |
| `profesores` | clave_profesor | **Catálogo global** (filtrable vía `horarios_det`) |
| `sesiones_base` | (nivel, turno, sesion) | Sesiones base por nivel/turno |
| `metodos_eval` | id_eval | Métodos de evaluación |
| `contratos` | contrato | Tipos de contrato |

### 1.3 Problemas Identificados

1. **AlumnoController::index()** — No filtra por ciclo; muestra TODOS los alumnos globalmente. El scope `porCiclo` existe en el modelo pero no se usa en el index.
2. **ProfesorController::index()** — Muestra todos los profesores globales; no hay filtro por ciclo (aunque sí en `show`).
3. **PlanController / Materias** — Completamente desacoplados del contexto de ciclo; no hay selector de ciclo en sus vistas.
4. **Vistas inconsistentes** — Dashboard tiene selector prominente; Grupos/Alumnos tienen solo enlace "Cambiar ciclo"; Planes no tienen ninguno.
5. **Sync Firebird** — `CycleDirectSync` ya respeta la jerarquía (fase 1: tablas de ciclo, fase 2: alumnos), pero `CatalogSmartSync` sincroniza catálogos globales sin contexto de ciclo.
6. **API endpoints** — Algunos requieren `ciclo` param, otros no; inconsistencia.
7. **Rutas** — No todas propagan `ciclo_principal` como query param consistente.

---

## 2. Diseño de la Jerarquía Correcta

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CICLO (Contexto Seleccionado)                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ GRUPOS (WHERE inicial,final,periodo)                                │   │
│  │   ├── ALUMNOS (via alumnos_grupos WHERE inicial,final,periodo)      │   │
│  │   │   └── KARDEX (WHERE inicial,final,periodo)                      │   │
│  │   ├── HORARIOS (WHERE inicial,final,periodo)                        │   │
│  │   │   └── ASISTENCIAS                                               │   │
│  │   └── CURSOS (WHERE inicial,final,periodo)                          │   │
│  │       └── CURSOS_DET                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘

CATÁLOGOS GLOBALES (Accesibles SIN filtro de ciclo, pero CON referencia a ciclos):
├── materias (por plan)          → Se usan en: horarios_det, cursos_det, kardex
├── planes                       → Se usan en: materias, alumnos.plan
├── niveles, turnos, sedes       → Se usan en: grupos, horarios_det, cursos, alumnos
├── ciclos (catálogo)            → Selector de contexto principal
├── profesores (catálogo global) → FILTRABLE vía horarios_det.inicial,final,periodo
└── sesiones_base, metodos_eval, contratos → Apoyo transversal
```

### 2.1 Reglas de Navegación y Filtrado

| Vista/Controlador | Comportamiento Requerido |
|-------------------|-------------------------|
| **Dashboard** | Selector de ciclo prominente ✓ (ya implementado) |
| **Grupos** | Filtrados por ciclo seleccionado ✓ (ya implementado) |
| **Alumnos** | **DEBE** filtrar por ciclo (via `alumnos_grupos`) — mostrar solo inscritos en ese ciclo |
| **Horarios** | Filtrados por ciclo ✓ (ya implementado) |
| **Cursos** | Filtrados por ciclo ✓ (ya implementado) |
| **Kardex** | Filtrado por ciclo ✓ (ya implementado) |
| **Profesores** | **DEBE** tener toggle: "Todos (catálogo)" vs "Con horarios en ciclo actual" |
| **Planes/Materias** | **Catálogo global** — sin filtro de ciclo, pero con badge "Usado en X ciclos" |
| **Niveles/Turnos/Sedes** | Catálogos globales — sin filtro de ciclo |

### 2.2 Selector de Ciclo Unificado

- **Componente Blade reutilizable**: `components/academia/ciclo-selector.blade.php`
- **Propagación automática**: Todas las rutas academia deben aceptar `?ciclo_principal=`
- **Persistencia**: Sesión + URL (como ya hace `CicloActualService`)
- **Ubicación**: Header fijo en layout academia o card header en cada vista

---

## 3. Plan de Implementación

### Fase 1: Modelos — Scopes y Relaciones (Base)

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `app/Models/Academia/Alumno.php` | Agregar `scopeInscritosEnCiclo()` que usa `alumnos_grupos` + eager load grupo | ALTA |
| `app/Models/Academia/Profesor.php` | Agregar `scopeConHorariosEnCiclo($I,$F,$P)` y `scopeTodos()` | ALTA |
| `app/Models/Academia/Grupo.php` | Verificar `alumnos()` y `horarios()` ya filtran por ciclo ✓ | MEDIA |
| `app/Models/Academia/HorarioDet.php` | Verificar `scopePorCiclo` ✓ | MEDIA |
| `app/Models/Academia/Curso.php` | Verificar `scopePorCiclo` ✓ | MEDIA |
| `app/Models/Academia/AlumnoKardex.php` | Verificar `scopePorCiclo` ✓ | MEDIA |

### Fase 2: Controllers — Filtrado Consistente por Ciclo

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `app/Http/Controllers/Academia/AlumnoController.php` | `index()`: usar `Alumno::inscritosEnCiclo($ciclo)` en lugar de query global; agregar `$ciclo` a vista | ALTA |
| `app/Http/Controllers/Academia/ProfesorController.php` | `index()`: agregar filtro "Con horarios en ciclo" (checkbox); por defecto mostrar solo los del ciclo | ALTA |
| `app/Http/Controllers/Academia/PlanController.php` | `index()`: agregar badge "Usado en X ciclos" (count distinct ciclos via `horarios_det` + `cursos_det`); NO filtrar por ciclo | MEDIA |
| `app/Http/Controllers/Academia/CursoController.php` | Verificar que `create/edit` reciben `$ciclo` del service ✓ | MEDIA |
| `app/Http/Controllers/Academia/KardexController.php` | Verificar filtro por ciclo ✓ | MEDIA |
| `app/Http/Controllers/Academia/ApiController.php` | Estandarizar: todos los endpoints que devuelven datos de ciclo requieren `ciclo` param | MEDIA |

### Fase 3: Vistas — Selector de Ciclo Unificado y UI Jerárquica

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `resources/views/components/academia/ciclo-selector.blade.php` | **NUEVO** componente reutilizable con selector + badge ciclo actual | ALTA |
| `resources/views/layouts/academia.blade.php` | **NUEVO** layout base para academia con selector en header | ALTA |
| `resources/views/academia/alumnos/index.blade.php` | Usar componente selector; mostrar solo alumnos del ciclo; agregar columna "Grupo" | ALTA |
| `resources/views/academia/profesores/index.blade.php` | Usar componente selector; agregar toggle "Solo con horarios en ciclo" | ALTA |
| `resources/views/academia/planes/index.blade.php` | Usar componente selector (solo visual); agregar columna "Ciclos donde se usa" | MEDIA |
| `resources/views/academia/grupos/index.blade.php` | Usar componente selector (reemplazar botón "Cambiar ciclo") | MEDIA |
| `resources/views/academia/cursos/index.blade.php` | Usar componente selector | MEDIA |
| `resources/views/academia/horarios/clase.blade.php` | Usar componente selector | MEDIA |
| `resources/views/academia/kardex/index.blade.php` | Usar componente selector | MEDIA |
| `resources/views/academia/dashboard/index.blade.php` | Ya tiene selector prominente ✓ | BAJA |

### Fase 4: Rutas — Propagación Consistente de `ciclo_principal`

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `routes/web.php` | Verificar que todas las rutas `academia.*` acepten query param `ciclo_principal` (ya funciona via `CicloActualService`) | MEDIA |
| `routes/web.php` | Agrupar rutas academia bajo middleware que inyecte ciclo por defecto | BAJA |

### Fase 5: Sync Firebird — Validación de Jerarquía

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `app/Services/SyncStrategies/CycleDirectSync.php` | Verificar orden: CICLOS → GRUPOS → (CURSOS, ALUMNOS_GRUPOS, HORARIOS_DET) → CURSOS_DET ✓ | MEDIA |
| `app/Services/SyncStrategies/CatalogSmartSync.php` | Verificar que catálogos globales NO filtran por ciclo ✓ | MEDIA |
| `app/Http/Controllers/FirebirdController.php` | UI: separar visualmente "Catálogos globales" vs "Datos por ciclo" en grupos ✓ (ya en `getCatalogGroups()`) | BAJA |

### Fase 6: Testing y Validación

| Test | Descripción | Nivel |
|------|-------------|-------|
| `AlumnoController@index` | Verifica que solo retorna alumnos inscritos en el ciclo seleccionado | 3 (integración DB) |
| `ProfesorController@index` | Verifica toggle "con horarios en ciclo" vs "todos" | 3 |
| `PlanController@index` | Verifica que NO filtra por ciclo pero muestra conteo de ciclos | 2 |
| `CicloSelector` | Verifica que cambia ciclo y persiste en sesión + URL | 2 |
| `SyncCycle` | Verifica que sync de ciclo respeta orden FK y filtra por ciclo | 4 (crítico) |

---

## 4. Orden de Implementación

```
1. Crear componente CicloSelector + Layout academia
2. Actualizar AlumnoController + vista (filtrado por ciclo)
3. Actualizar ProfesorController + vista (toggle ciclo)
4. Actualizar PlanController + vista (badges ciclos)
5. Actualizar vistas restantes (Grupos, Cursos, Horarios, Kardex) para usar selector unificado
6. Verificar/actualizar ApiController consistencia
7. Tests de integración (Nivel 3)
8. Validación manual E2E
```

---

## 5. Riesgos y Mitigaciones

| Riesgo | Impacto | Probabilidad | Mitigación |
|--------|---------|--------------|------------|
| **Romper vistas existentes** que esperan alumnos globales | ALTO | MEDIA | Feature flag temporal; mantener scope `Activo()` global como fallback |
| **Performance** en `Alumno::inscritosEnCiclo()` con join complejo | MEDIO | BAJA | Índice compuesto en `alumnos_grupos (inicial,final,periodo,estatus)` ya existe |
| **Profesores sin horarios** desaparecen de lista por defecto | MEDIO | ALTA | Default: "Con horarios en ciclo"; checkbox "Mostrar todos" siempre visible |
| **Planes/Materias** confunden a usuarios al no filtrar por ciclo | BAJO | MEDIA | UI clara: badge "Catálogo global" + tooltip explicativo |
| **Sync Firebird** orden incorrecto causa FK errors | CRÍTICO | BAJA | `CycleDirectSync` ya valida orden; agregar test de integración |

---

## 6. Decisiones Pendientes (Requieren Confirmación)

1. **Alumno index**: ¿Mostrar **solo** inscritos en ciclo, o agregar pestaña "Todos los alumnos"?
   - *Recomendación*: Default = inscritos en ciclo; pestaña "Catálogo completo" opcional.

2. **Profesor index**: ¿Default "con horarios en ciclo" o "todos"?
   - *Recomendación*: Default "con horarios en ciclo" (más útil para gestión académica).

3. **Selector de ciclo**: ¿En layout global (header fijo) o por vista (card header)?
   - *Recomendación*: Layout global para academia (persistente al navegar entre módulos).

4. **Kardex histórico**: ¿El controlador `KardexController::historial()` debe mostrar TODOS los ciclos o solo el seleccionado?
   - *Recomendación*: `historial()` = todos los ciclos (trasciende ciclo); `show()` = ciclo actual.

---

## 7. Task Boundary para Implementación

Ver `.opencode/state/current-task.md` (generado junto a este plan).