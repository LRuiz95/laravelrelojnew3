# Auditoría visual y UX del proyecto

## 1. Alcance y contexto

Proyecto actual: Laravel 10 + Bootstrap 5.3.3 + Vite + Blade + componentes Blade reutilizables.

### Stack identificado
- Framework: Laravel 10
- Frontend: Blade + Bootstrap 5.3.3 + Bootstrap Icons
- Build: Vite
- Estilos globales: `resources/css/app.css`
- Layout principal: `resources/views/layouts/admin.blade.php`
- Componentes Blade reutilizables: `resources/views/components/*`
- Scripts del frontend: `resources/js/app.js` y fragmentos inline en vistas

## 2. Estructura actual detectada

### Layouts principales
- `resources/views/layouts/admin.blade.php`
  - Sidebar
  - Topbar
  - Global alerts
  - Command palette
  - Toast stack
  - Theme toggle
  - Ciclo selector

### Componentes reutilizables ya existentes
- `resources/views/components/data-table.blade.php`
- `resources/views/components/filter-bar.blade.php`
- `resources/views/components/stat-card.blade.php`
- `resources/views/components/drawer.blade.php`
- `resources/views/components/academia/ciclo-selector.blade.php`

### CSS global
- `resources/css/app.css`
  - Tiene un sistema de tokens de diseño moderno
  - Ya incluye Dark Mode, responsive, tablas, cards, botones, inputs, modales, notificaciones, etc.

### Patrones de vistas detectados
- Vistas de Academia con tablas tradicionales y formularios únicos
- Vistas de empleados con filtros y tablas más complejas
- Vistas de dispositivos/asistencias con dashboards y estados numerosos
- Existencia de componentes de KPI, cards, empty states, toasts y notificaciones

## 3. Hallazgos de auditoría visual

### 3.1. Fortalezas
- Ya existe un layout central muy completo.
- Hay un sistema de tokens CSS y dark mode implementado.
- Se han iniciado componentes como `x-data-table`, `x-filter-bar` y `x-stat-card`.
- La aplicación ya dispone de una base sólida para un Design System.

### 3.2. Problemas actuales
1. Identidad visual inconsistente
   - La base CSS ya estaba enfocada en azul/teal, aunque el requerimiento de identidad pide naranja + blanco.
   - Las vistas no terminan de sentirse como parte de un mismo sistema cuando se compara la paleta aplicada.

2. Tablas y filtros con nivel de consistencia variable
   - Hay tablas manuales en varias vistas.
   - El componente `x-data-table` ayuda, pero aún hay vistas que usan tablas directas o combinan patrones distintos.

3. Vistas de administración con mucho ruido visual
   - Existen múltiples tarjetas/estado y bloques pequeños que pueden sobrecargar la lectura.
   - Hay oportunidades claras de reducir elementos innecesarios sin tocar funcionalidad.

4. Botones y formularios con base decente, pero no homogéneos en todos los contextos
   - La base global existe, pero tareas de UI de varias vistas aún pueden quedar con variantes locales.

5. Dark Mode funcional, pero no totalmente homogéneo en todos los elementos visuales
   - Algunas áreas visuales y estilos heredados aún necesitan revisión para lograr coherencia total.

6. Responsive parcialmente resuelto
   - La arquitectura respeta responsive en varios puntos, pero la experiencia en tablet/móvil puede seguir mejorándose con el mismo sistema visual global.

## 4. Matriz de vistas y priorización

| Vista | Estado actual | Problemas | Prioridad | Cambio recomendado |
|---|---|---|---|---|
| Layout principal (`admin.blade.php`) | Bueno | Identidad visual aún no desplazada a naranja, sidebar/ topbar funcionales | Alta | Homologar tokens globales y refinamiento visual |
| Dashboard | Bueno | Puede saturarse visualmente con demasiada información | Media | Reorganizar KPI + detalle + acciones |
| Academia / índices | Regular | Tablas y filtros con patrones distintos | Alta | Completar homogeneización con `x-data-table` y `x-filter-bar` |
| Empleados | Regular | Filtros y tablas funcionales, pero estilo aún heterogéneo | Alta | Normalizar cards, filtros y acciones |
| Dispositivos | Bueno | Gran cantidad de información y varios bloques | Media | Mejorar jerarquía y reducir ruido |
| Formularios CRUD | Regular | Base sólida, pero aún con variantes visuales | Media | Homologar spacing, secciones y errores |
| Modales y alertas | Bueno | Necesita coherencia visual global | Media | Refinar tamaños y estilos |
| Empty states y errores | Regular | Existen algunos, pero no siempre con contexto y acción | Media | Estándar estados vacíos y fallidos |

## 5. Recomendaciones clave

### A. Design System global
- Reforzar `resources/css/app.css` como fuente única de tokens visuales.
- Definir naranja como principal foco visual, con blanco/neutros como base.
- Adoptar una escala de spacing, radios, sombras y tipografía uniforme.

### B. Componentes reutilizables
- Continuar con `x-data-table` y `x-filter-bar` como estándar.
- Homologar badges, alerts, buttons, page headers, empty states y cards.

### C. Navegación y jerarquía
- Mantener el sidebar y topbar como base, pero reforzar identidad y legibilidad.
- Agrupar acciones más importantes y reducir duplicados visuales.

### D. Responsive y Dark Mode
- Mantener ambos temas como prioridad no opcional.
- Revisar que todas las tarjetas, tablas, inputs, modales y controles respondan bien en 480/390/360px.

### E. UX
- Mejorar el entendimiento de la página mediante mejor jerarquía, agrupación y estados visuales expresivos.
- Evitar saturación visual y exceso de elementos decorativos.

## 6. Prioridades de implementación

### Nivel 1 — Crítico
- identidad visual global
- legibilidad
- responsive
- tablas y formularios
- acciones principales

### Nivel 2 — Importante
- consistencia visual
- componentes reutilizables
- spacing y tipografía
- Dark Mode

### Nivel 3 — Pulido
- microinteracciones
- refinamiento de detalles
- homogeneización de pequeños elementos

## 7. Resultado esperado

Se busca una aplicación con:
- apariencia profesional
- diseño moderno y limpio
- identidad naranja + blanco
- dark mode coherente
- tablas profesionales
- formularios más claros
- navegación intuitiva
- components homologados
- experiencia consistente en escritorio, tablet y móvil
