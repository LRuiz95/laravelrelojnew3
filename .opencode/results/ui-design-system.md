# Design System global — UI/UX

## 1. Principios

- La interfaz debe sentirse parte del mismo sistema.
- Naranja como color principal de acento, blanco como base de contenido.
- El sistema debe funcionar de manera consistente en Light Mode y Dark Mode.
- El diseño debe priorizar claridad, navegación, lectura y acción.
- Los componentes deben reutilizarse antes de crear variantes aisladas.

## 2. Paleta base

### Color principal (naranja)
- `--primary`: `#ea580c`
- `--primary-hover`: `#f97316`
- `--primary-active`: `#c2410c`
- `--primary-soft`: `rgba(234, 88, 12, .16)`
- `--primary-ring`: `rgba(234, 88, 12, .22)`

### Superficies y texto
- `--bg`: fondo base
- `--surface`: superficie principal
- `--surface-elevated`: superficie con elevación
- `--surface-critical`: fondos de errores/alertas
- `--border`: bordes suaves
- `--border-strong`: bordes más visibles
- `--text`: texto principal
- `--text-secondary`: texto secundario
- `--text-tertiary`: texto terciario
- `--text-inverse`: texto sobre color primario

### Semánticos
- éxito: verde
- advertencia: ámbar/naranja cálido
- error: rojo
- info: azul neutral
- in-progress: morado suave

## 3. Dark Mode

### Directrices
- Fondo oscuro real.
- Superficies diferenciadas por capas.
- Texto claro con contrastes controlados.
- Naranja como foco visual principal.
- Bordes discretos, no agresivos.

### Regla de uso
- El naranja no debe cubrir toda la pantalla.
- Debe usarse para acciones, estados activos, enlaces y elementos de acción primarios.

## 4. Tipografía

### Jerarquía recomendada
- H1: 23px / 800
- H2: 18px / 700
- H3: 16px / 700
- Page title: 23px / 800
- Section title: 16px / 700
- Body: 14px / 400
- Small: 12px / 500
- Muted: 12px / 400

### Fuentes
- `DM Sans` para interfaz
- `JetBrains Mono` para métricas, datos y códigos cortos

## 5. Espaciado

Escala recomendada:
- 4, 8, 12, 16, 20, 24, 32, 40, 48

### Regla
- Usar el mismo espaciado para cards, formularios, secciones, tablas y compuestos visuales.

## 6. Botones

### Sistema recomendado
- Primary
- Secondary
- Outline
- Ghost
- Danger
- Success
- Icon

### Reglas
- Todas las acciones principales usan el naranja.
- Las acciones secundarias se mantienen en superficies limpias.
- Las acciones peligrosas usan rojo y deben estar claramente diferenciadas.
- Mantener altura y radio consistentes.

## 7. Cards y paneles

### Estándar
- Bordes suaves
- Radio moderado
- Fondo de superficie
- Sin demasiada sombra
- Buen espaciado interno

### Aplicación
- Se usan para agrupar información y secciones.
- Evitar cards redundantes y bloques visuales sin contexto.

## 8. Tablas

### Estándar
- Encabezado compacto y consistente
- Filas uniformes
- Hover claro
- Acciones compactas
- Soporte para mobile: columnas ocultables o modo tarjeta
- Paginación con estilo homogéneo

### Reglas
- Todas las tablas deben seguir el mismo estilo visual.
- Las acciones de fila deben priorizar lo importante y usar compactación cuando hay muchas.

## 9. Formularios

### Estándar
- Labels claros
- Secciones agrupadas por propósito
- Mensajes de error visibles y bien ubicados
- Inputs con borde suave y focus visible
- Estados disabled y loading con feedback claro

### Reglas
- Evitar formularios gigantes sin organización.
- Usar agrupaciones lógicas y descripciones breves.

## 10. Modales y alerts

### Modales
- Encabezado claro
- Título consistente
- Footer con acciones alineadas
- Tamaños definidos por el tipo de contenido

### Alerts
- Deben ser claros y homogéneos
- Alinear con el tipo semántico (info, success, warning, danger)

## 11. Badges y estados

### Uso recomendado
- Badge para etiquetas cortas y semánticas
- Estado visual claro y consistente
- No mezclar demasiados colores sin necesidad

## 12. Iconografía

### Estándar
- Bootstrap Icons ya está integrado y se usa en el proyecto.
- Preferir un solo estilo visual para toda la aplicación.

## 13. Responsive

### Breakpoints clave
- Desktop: 1280+, 1440+, 1600+
- Tablet: 768, 1024
- Mobile: 360, 390, 480

### Regla
- Todas las vistas deben leerse bien sin requerir scroll horizontal excesivo.
- En móvil, priorizar estructura vertical y acciones con mayor claridad.

## 14. Estados de pantalla

### Deben existir estandares para
- vacío
- carga
- error
- éxito
- búsqueda sin resultados
- permisos insuficientes

### Reglas
- No dejar un `No hay datos` sin contexto.
- Cada estado debe ofrecer una guía o una acción útil.

## 15. Implementación sugerida

1. Mantener `resources/css/app.css` como fuente única del Design System.
2. Reutilizar `x-data-table`, `x-filter-bar`, `x-stat-card` para homogeneizar vistas.
3. Aplicar tokens globales a botones, inputs, cards, tablas, alerts y layout.
4. Revisar cada bloque por cada vista en ciclos pequeños.
5. Validar Light Mode, Dark Mode, tablet y móvil.

## 16. Checklist visual final

- [ ] Light Mode
- [ ] Dark Mode
- [ ] Desktop
- [ ] Tablet
- [ ] Mobile
- [ ] Sidebar y topbar
- [ ] Dashboard
- [ ] Tablas
- [ ] Formularios
- [ ] Modales
- [ ] Botones
- [ ] Alertas
- [ ] Badges
- [ ] Paginación
- [ ] Empty states
- [ ] Error states
- [ ] Consistencia visual
