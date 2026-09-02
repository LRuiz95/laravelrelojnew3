# Diseño del Dashboard y Frontend (UI/UX)

El diseño es prioridad alta: debe verse profesional, no solo funcional.

## 1. Reglas Generales

* **Sistema de diseño consistente:** mismos colores, tipografía y espaciados en todo el dashboard — no definir estilos nuevos por vista. Centraliza tokens (colores, radios, sombras, tipografía) en `tailwind.config.js` o en CSS variables (`:root { --color-surface: ... }`), no en clases repetidas por archivo.
* **Framework CSS:** respeta Tailwind CSS. No mezcles frameworks distintos en el mismo proyecto (evita agregar Bootstrap, Bulma, etc. "solo para un componente").
* **Responsive:** debe verse bien en tablet y móvil, no solo en escritorio (común que se revise desde el celular de RH). Diseña mobile-first: define primero el layout en pantallas pequeñas y usa `sm:`/`md:`/`lg:` para expandir, no al revés.

## 2. Modo Claro / Oscuro (implementación concreta)

* **Toggle:** un control visible y accesible (ícono sol/luna) en el header/navbar, no escondido en un menú de configuración profundo.
* **Tres estados, no dos:** `light`, `dark`, `system` — el default recomendado es `system` en el primer uso.
* **Sin parpadeo (FOUC):** aplica la clase `dark` al `<html>` con un script inline síncrono en el `<head>`, antes de que cargue el resto del CSS/JS. Si el framework es SPA (Vue/React), esto sigue aplicando igual porque el HTML inicial se sirve antes de hidratar.
* **Contraste:** en modo oscuro no uses negro puro (`#000`) como fondo — usa grises oscuros (`slate-900`/`gray-900` de Tailwind) para reducir fatiga visual, y evita blanco puro para el texto (usa `slate-100`/`gray-100`).
* **Estados de color con significado (verde=a tiempo, amarillo=retardo, rojo=falta, gris=desconectado):** deben mantener suficiente contraste en ambos temas — verifica cada color contra su fondo correspondiente, no asumas que un color que se ve bien en claro automáticamente funciona en oscuro.
* **Gráficas y librerías de terceros:** no heredan `dark:` de Tailwind automáticamente — sus colores (ejes, leyenda, tooltips) deben actualizarse por evento cuando cambia el tema.
* **Imágenes/logos:** si el logo institucional tiene fondo transparente pensado para fondo claro, provee una variante para fondo oscuro (o un contenedor con fondo blanco fijo alrededor del logo) para que no se pierda visualmente.

## 3. Experiencia de Usuario (UX)

* **Prioridad visual:** destaca lo que el usuario necesita ver primero: estado de conexión del checador, asistencias del día, alertas de faltas/retardos.
* **Estados de interfaz:** incluye siempre estados vacíos y de carga (skeletons o spinners) — nunca dejar una vista en blanco mientras se espera data. Un estado vacío debe explicar *por qué* está vacío ("No hay registros para la fecha seleccionada") y, si aplica, ofrecer una acción ("Cambiar filtro de fecha").
* **Manejo de errores:** errores mostrados al usuario final deben ser mensajes claros, nunca trazas técnicas o stack traces de PHP. Traduce excepciones técnicas ("Connection timed out after 5000ms") a lenguaje humano ("No se pudo conectar con el checador de la entrada principal. Intenta de nuevo en unos minutos.").
* **Accesibilidad básica:** contraste de texto mínimo AA (4.5:1 para texto normal), elementos interactivos con estado `:focus` visible (no lo quites con `outline-none` sin reemplazo), botones e íconos con `aria-label` cuando no llevan texto visible, tamaño de tap objetivo mínimo ~44px en vista móvil.

## 4. Flujo de Trabajo UI

* Antes de rediseñar una vista completa, propón primero la estructura en texto (secciones, jerarquía visual), y espera confirmación antes de escribir el código si el cambio es grande.
* Para cambios pequeños y acotados (ajustar un color, corregir un breakpoint roto), procede directo sin pedir aprobación de estructura — reserva ese paso para rediseños de vista completa.

## 5. Referencia visual objetivo (estilo "admin dashboard" tipo Star Admin / Tresto)

El dashboard debe apuntar a este nivel de acabado visual — layout de dos zonas (sidebar fijo + contenido), denso en información pero ordenado, con look "SaaS profesional", no "panel de admin genérico de Bootstrap por defecto".

### 5.1 Layout general
* **Sidebar fijo a la izquierda**, ancho ~250-280px en estado expandido, colapsable a solo íconos (~72-80px) en pantallas medianas (`md:`) y oculto tras un botón hamburguesa en móvil (`<lg:`).
* Header propio dentro del sidebar: logo + nombre del sistema arriba, ícono para colapsar/expandir junto al logo (el mismo botón alterna entre expandido/retraído; no dupliques el control en otro lugar).
* Navegación agrupada por secciones con etiquetas de categoría en mayúsculas pequeñas y color tenue (ej. "DASHBOARD", "COMPONENTES", "LOGIN & ERROR" en las referencias) — no una lista plana de 20 links sin jerarquía.
* Item activo del menú: fondo resaltado sutil + borde/acento de color a la izquierda, no solo texto en negrita.
* **Topbar** separado del sidebar: buscador centrado o a la izquierda, e íconos de acción a la derecha (notificaciones con badge, mensajes, selector de idioma/tema, avatar de usuario con menú). Mantén máximo 4-5 íconos — no satures el topbar.
* Contenido principal con padding generoso (`p-6`/`p-8`) y máximo ancho consistente; en pantallas grandes evita que las tarjetas se estiren infinitamente — usa `max-w-` o un grid con columnas fijas.
* El área de contenido debe ajustar su `margin-left`/ancho disponible de forma animada cuando el sidebar cambia de expandido a retraído (transición, no salto brusco) — no dejar un hueco vacío ni que el contenido se recorte.

### 5.1.1 Sidebar retráctil (comportamiento obligatorio, no solo visual)

El sidebar tiene **dos estados** y debe transicionar entre ellos con una animación suave (`transition-all duration-200/300`, no instantáneo ni brusco):

**Estado expandido (default en desktop):**
* Ícono + texto de cada ítem de menú, uno junto al otro.
* Etiquetas de categoría visibles ("DASHBOARD", "COMPONENTES", etc.).
* Logo completo (ícono + nombre del sistema) en el header del sidebar.
* Ancho ~250-280px.

**Estado retraído/colapsado:**
* Solo íconos, centrados, sin texto visible en línea — el ancho se reduce a ~72-80px (lo justo para el ícono + padding, no un colapso a 0 que oculte todo).
* Las etiquetas de categoría se ocultan (no tiene sentido un título de sección sin los items legibles debajo).
* El logo se reduce a solo el ícono/isotipo (sin el nombre completo del sistema).
* **Tooltip al hacer hover sobre cada ícono:** al pasar el mouse sobre un ítem del menú retraído, debe aparecer un tooltip flotante a la derecha del ícono mostrando el texto de ese ítem (ej. "Analytics", "Empleados", "Dispositivos") — aparece con un pequeño delay (~200-300ms) para evitar parpadeo si el usuario solo está pasando el cursor de paso, y desaparece al quitar el mouse o al hacer click.
  * El tooltip va **fuera** del flujo del sidebar (position absolute/fixed), con z-index alto, para que no quede recortado por el `overflow: hidden` del contenedor retraído.
  * Fondo oscuro sólido (o el color de superficie del tema), texto claro, esquinas redondeadas, sombra sutil — mismo estilo en toda la app, no un `title` HTML nativo del navegador (ese no se puede estilizar y tarda distinto en cada navegador).
  * Si el ítem tiene submenú (ej. "Dashboard" con hijos como "CRM", "Analytics", "HRM" en la referencia), el hover en modo retraído debe mostrar un mini-panel flotante con la lista completa de subitems, no solo el nombre del padre — replicando el comportamiento de flyout de menús tipo Star Admin/Tresto cuando el sidebar está colapsado.

**Persistencia y comportamiento:**
* Guarda la preferencia expandido/retraído en `localStorage` (clave sugerida: `sidebar_collapsed`), igual que el tema claro/oscuro — para que no vuelva a su estado por defecto cada vez que el usuario navega o recarga.
* En breakpoint `md` (tablet), el default automático es retraído; en `lg`+ (desktop), el default automático es expandido — salvo que el usuario ya haya guardado una preferencia explícita, la cual siempre gana sobre el default por breakpoint.
* En móvil (`<md`), el sidebar no debe tener un tercer estado "mini" — se comporta como off-canvas: oculto completamente y se despliega expandido por encima del contenido (overlay) al tocar el botón hamburguesa, con fondo semitransparente detrás para cerrarlo al tocar fuera.
* El botón para expandir/retraer debe tener su propio tooltip corto ("Expandir menú" / "Contraer menú") y un ícono que rote o cambie (flecha ‹ / › o ☰) para indicar la acción disponible, no quedarse con un ícono ambiguo fijo.

### 5.2 Tarjetas KPI (las 4 tarjetas superiores)
Patrón exacto a replicar (ver imágenes de referencia, tarjetas "8,958 User online", "Bounce Rate 32.53%", etc.):
* Número grande y bold como protagonista, con la etiqueta descriptiva debajo en gris/tenue, más pequeña.
* Badge de variación porcentual junto al número (verde con flecha arriba = mejora, rojo con flecha abajo = caída) — nunca solo el número sin indicar si es bueno o malo.
* Opcional pero deseable: mini-gráfica de fondo (sparkline) dentro de la misma tarjeta, sutil, que no compita visualmente con el número.
* 4 tarjetas en fila en desktop (`grid-cols-4`), 2 en tablet (`grid-cols-2`), 1 en móvil (`grid-cols-1`) — nunca comprimir las 4 en una fila en pantalla chica.
* Para este proyecto, las 4 tarjetas KPI del dashboard de asistencia deben ser: **Asistencias hoy**, **Retardos**, **Faltas**, **Dispositivos activos** — con su variación vs. el día/semana anterior cuando el dato lo permita.

### 5.3 Gráficas
* **Gráfica de línea "Overview"/"Revenue"** con dos series superpuestas (ej. entradas vs. salidas, o asistencias vs. retardos), cada serie con su propio color y relleno de gradiente suave hacia abajo (no relleno sólido plano).
* Selector de rango integrado en la tarjeta de la gráfica (pastillas "Month / Day / Year" o similar) — no un `<select>` HTML genérico ahí.
* **Gráfica de dona/pie** para proporciones simples (ej. "Monthly Overview": Sent/Open/Not Open en la referencia) — para este proyecto: proporción de Entrada/Salida/Entrada T.E./Salida T.E. del día, o a-tiempo/retardo/falta.
* Grid de fondo sutil en gráficas de línea/área (líneas horizontales tenues), sin bordes duros alrededor del gráfico.
* Tarjeta de "carga en tiempo real" tipo sparkline continuo (ver "CPU Load" en la referencia) — aplicable aquí como **estado de latencia/actividad de sincronización con los checadores**.

### 5.4 Tabla de datos (ver imagen "Sales Transactions Detail")
Patrón a replicar para listados de asistencias, empleados, dispositivos:
* Header de tabla con: selector de categoría, selector de fecha, botón de exportar destacado (color sólido, esquina superior derecha).
* Fila de acciones sobre la tabla: opciones de exportación múltiple (PDF/XLS/CSV/imprimir) + filtros + búsqueda, todo en una barra horizontal compacta.
* Columnas con encabezado ordenable (ícono de flechas junto al texto del header).
* Columna de **Estado** como badge de color sólido y texto corto (`Delivered`→verde, `Processing`→amarillo, `Cancelled`→rojo). Para este proyecto: `A tiempo` / `Retardo` / `Falta` / `Justificado` con la misma lógica de badges.
* Columna de tendencia/variación con mini ícono de flecha + color (sube/baja) cuando aplique a un valor numérico.
* Columna de **Acciones** a la derecha con íconos pequeños (editar, ver, eliminar, más opciones "...") en vez de botones de texto largo — agrupados y con hover visible.
* Paginación al pie: seleccionable "Rows per page", conteo "Showing X-Y of Z records", y controles Prev/Next + números de página.

### 5.5 Modales y feedback (ver imágenes de modal de perfil, alertas y toasts)
* **Modal de edición de perfil/registro:** overlay oscuro semitransparente sobre el fondo, tarjeta centrada con esquinas redondeadas, foto/avatar circular arriba con botón de cambiar imagen superpuesto, campos de formulario apilados verticalmente con label arriba del input, botones de acción al final alineados (primario sólido "Guardar cambios" + secundario outline "Cancelar").
* **Toasts de notificación:** esquina de la pantalla (no bloquean interacción), con ícono + color según tipo — éxito (verde, ✓), información (azul, ℹ), advertencia (amarillo, ⚠), error (rojo). Auto-desaparecen tras unos segundos salvo que requieran acción.
* **Alertas inline** (banner de advertencia dentro del contenido, no flotante): fondo de color tenue del tono correspondiente + ícono + texto + acción en línea si aplica (ej. "Renovar ahora").
* **Diálogos de confirmación** para acciones destructivas o irreversibles (eliminar empleado, forzar resincronización completa de un dispositivo): título de pregunta directa, texto aclaratorio corto, botón de confirmar en color de énfasis y botón de cancelar neutro — nunca un solo botón "OK".
* **Estados de progreso:** barra de progreso con porcentaje explícito para procesos largos (ej. sincronización masiva de empleados a un dispositivo) y un estado tipo spinner + texto ("Procesando solicitud...") para operaciones cortas sin progreso medible.

### 5.6 Paleta de referencia (modo oscuro, tono "admin SaaS")
* Fondo base: azul-negro muy oscuro, no negro puro (tipo `#0f1420`–`#151a28`).
* Superficies de tarjetas: un tono ligeramente más claro que el fondo, para dar profundidad por capas (`#1a2033`–`#1e2436` aprox.), con bordes sutiles casi imperceptibles en vez de sombras fuertes.
* Acentos de marca: un color primario vivo (azul/violeta) reservado para elementos interactivos activos, botones primarios y el ítem de menú seleccionado — no lo uses decorativamente en todas partes o pierde jerarquía.
* Colores de estado consistentes en toda la app: verde=éxito/positivo, rojo=error/negativo, amarillo=advertencia/pendiente, azul=informativo — la misma paleta debe usarse en badges, toasts, alertas y gráficas.
* Tarjetas "hero" ocasionales (ej. "Revenue Overview"/"Sales Overview" en la referencia) pueden usar gradientes de marca llamativos para destacar métricas más importantes de la página — úsalas con moderación, 1-2 por vista como máximo, no en cada tarjeta.

## 6. Patrones para Dashboards de Asistencia/RH

* **Tabla principal** con filtros por fecha, empleado, dispositivo y estado (a tiempo/retardo/falta). Para tablas grandes, pagina en servidor (no cargues miles de registros al cliente) y considera `debounce` en el input de búsqueda para no disparar una request por cada tecla.
* **Tarjetas resumen arriba:** asistencias hoy, retardos, faltas, dispositivos activos. Cada tarjeta debe tener su propio estado de carga independiente (no bloquear toda la página si solo una tarjeta tarda en responder).
* **Indicador visual claro de conexión del checador:** verde/gris/rojo con texto explícito ("En línea" / "Desconectado hace 12 min"), no solo un punto de color sin contexto — apóyate en el campo `last_seen_at` descrito en `docs/zkteco.md`.
* **Gráfica de tendencia semanal/mensual de asistencia:** simple, con leyenda clara, y que respete el tema activo (ver sección 2).
* **Notificaciones de faltas/retardos:** si el proyecto ya tiene o va a tener notificaciones (email, dashboard, push), deben ser accionables — un enlace directo al empleado/registro en cuestión, no solo un texto informativo suelto.