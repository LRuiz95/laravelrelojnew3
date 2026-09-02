# Implementación completada: Tabs "Materias" y "Métodos de Evaluación" en Planes

## Cambios realizados en `rh-dashboard2.php`

### 1. CSS para tabs (línea ~939)
Agregado después de `.btn-toggle`:
- `.tab-nav`, `.tab-btn`, `.tab-panel` con estilos completos
- Tabs accesibles con `role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected`

### 2. Sección Planos con tabs (línea ~3984)
Bloque `<?php if ($f_plan !== '' && !empty($detalle_plan)): ?>` reemplazado por:
- Carga de métodos de evaluación desde `cfgplanes_eval` con filtro de versión
- Dos tabs: "Materias" y "Métodos de Evaluación"
- Tab activa determinada por `$_GET['tab']` (default: 'materias')
- Tab Materias: tabla con todas las materias del plan
- Tab Eval: tabla con métodos de evaluación + filtro de versión (Todas/ v1, v2, ...)
- Estados empty cuando no hay datos

### 3. Redirigir secciones standalone
- `?sec=materias` → `header("Location: ?sec=planes" . ($plan ? "&plan=X" : ""))` + exit
- `?sec=metodos-eval` → `header("Location: ?sec=planes&plan=X&tab=eval")` + exit

### 4. Menú eliminado
- Quitar `['sec' => 'materias', 'label' => 'Materias']` de config > Catálogos
- Quitar `['sec' => 'metodos-eval', 'label' => 'Métodos de evaluación']` de config > Catálogos

### 5. Breadcrumb actualizado
- `materias` → ahora solo muestra "Planes de estudio" → "planes"
- `metodos-eval` → ahora solo muestra "Planes de estudio" → "planes"

### 6. Links de acción en tabla de planes
- Quitar columna con links "Materias" y "Metodos Eval"
- Reemplazar por solo: `<a href="?sec=planes&plan=X" class="emp-link-detalle emp-link-sm">Ver detalle</a>`

## invariantes cumplidos
- `?sec=materias&plan=X` redirige a `?sec=planes&plan=X` ✓
- `?sec=metodos-eval&plan_eval=X` redirige a `?sec=planes&plan=X&tab=eval` ✓
- Queries SQL existentes sin modificar ✓
- Sin cambios en otras secciones del archivo ✓
- PHP sintaxis válida ✓

## Resultado
Cuando se accede a `?sec=planes&plan=X`, el usuario ve:
1. **Tab "Materias"** - tabla completa de asignaturas del plan
2. **Tab "Métodos de Evaluación"** - tabla con criterios + filtro de versión

Las secciones standalone `?sec=materias` y `?sec=metodos-eval` ahora redirigen automáticamente a la sección planes con el plan correspondiente.