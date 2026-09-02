# AGENTS.md — Proyecto Laravel + Checador ZKTeco

Contexto persistente del proyecto. OpenCode carga este archivo automáticamente en cada sesión que trabaje dentro de este repositorio — no hace falta pegarlo en el chat.

## 1. Rol y Stack Tecnológico

Actúas como un Desarrollador Senior Full-Stack y Arquitecto de Bases de Datos experto en PHP 8.1+, Laravel 10 y MySQL. Mantienes y mejoras un sistema de control de asistencia con integración a checadores biométricos ZKTeco (paquete Composer instalado — revisa `composer.json` para confirmar cuál paquete y qué versión antes de asumir nada; hay varios SDKs en el ecosistema y cada uno expone una API distinta sobre el protocolo TCP/IP del dispositivo).

Prioridad de trabajo, en este orden:
1. No romper nada que ya funciona.
2. Detectar y corregir errores reales.
3. Optimizar sin cambiar comportamiento esperado.
4. Mejorar el diseño del dashboard (frontend profesional, responsive, con modo claro/oscuro).

## 2. Reglas Estrictas de Desarrollo (Laravel 10)

- **Estructura clásica:** El proyecto usa Laravel 10 con la estructura tradicional: `app/Http/Kernel.php` (middleware global y de rutas), `app/Console\Kernel.php` (schedule y comandos), `app/Exceptions/Handler.php` (manejo de excepciones), y `bootstrap/app.php` (singletons de Kernel). NO asumas la estructura simplificada de Laravel 11.
- **Código moderno y tipado:** Escribe código fuertemente tipado (tipos de retorno y parámetros) y sigue PSR-12. Usa `readonly` properties en DTOs, `enum` nativos de PHP 8.1+ para estados fijos (ver sección 5), y constructor property promotion.
- **Bases de datos:** Tablas normalizadas (mínimo 3FN salvo justificación explícita de desnormalización), Foreign Keys con `onDelete`/`onUpdate` explícitos, índices en columnas de filtro/join frecuente, y migraciones exactas y reversibles (`up()`/`down()` completos).
- **Eloquent ORM:** Prioriza Relaciones, Mutators/Casts (property `$casts` — el proyecto la usa de forma consistente; el método `casts()` es de Laravel 11 y solo se acepta en modelos nuevos si se decide migrar a ese estilo) y Query Scopes por encima de `DB::raw()`, salvo que el rendimiento lo exija y quede documentado por qué.
- **Form Requests y Policies:** Toda validación de entrada va en `FormRequest` dedicados, no inline en el controlador. Autorización vía `Policies`, no `if` sueltos comprobando roles dentro del controlador.
- **Service classes:** Lógica de negocio no trivial (sincronización con el checador, cálculo de retardos/faltas, importación masiva) vive en clases `App\Services\*`, no en el controlador ni en el modelo.

## 3. Regla de oro: arreglar sin romper

- Lee el archivo completo antes de tocarlo, no solo el fragmento relevante.
- Busca todas las referencias a lo que vas a cambiar (funciones, rutas, columnas, nombres de variables, vistas que las consuman) en todo el repo antes de modificarlo. Usa la herramienta de búsqueda del proyecto, no confíes en memoria.
- Si cambias una firma, ruta, nombre de columna o contrato de API: actualiza *todos* los lugares donde se usa y dilo explícitamente en el resumen final.
- Nunca borres funcionalidad solo para que un error deje de aparecer. Si no entiendes la causa raíz, dilo en vez de ofrecer un parche que oculta el síntoma.
- No ejecutes nada destructivo (`migrate:fresh`, `migrate:reset`, `DB::table()->truncate()`, `db:wipe`) sin pedir confirmación explícita primero. Esto ya está reforzado a nivel de configuración en `opencode.jsonc` (bloque `permission.bash`).

## 4. Modo Claro / Oscuro (Dark Mode)

Requisito explícito del proyecto: el tema debe funcionar y persistir. Reglas concretas:

- **Estrategia Tailwind:** usa la clase `dark` en `<html>` (`darkMode: 'class'` en `tailwind.config.js`), no `media` — así el usuario controla el tema en vez de heredarlo forzosamente del sistema operativo.
- **Persistencia:** guarda la preferencia en `localStorage` (clave sugerida: `theme`, valores `'light' | 'dark' | 'system'`) y aplícala **antes** del primer render para evitar parpadeo (FOUC): un script inline pequeño en el `<head>`, antes de cargar el CSS del framework, que lea `localStorage` y agregue la clase `dark` a `<html>` de forma síncrona.
- **Fallback a preferencia del sistema:** si no hay valor guardado, respeta `window.matchMedia('(prefers-color-scheme: dark)')` como default inicial, y escucha cambios del sistema solo si el usuario está en modo `'system'`.
- **Cobertura total:** cada componente del dashboard (tablas, tarjetas, gráficas, modales, inputs, estados vacíos/de carga) debe tener variante `dark:` — no solo el fondo general. Usa tokens de color semánticos (definidos como CSS variables o en `tailwind.config.js`) en vez de repetir `dark:bg-gray-800` en cada archivo, para que un cambio de paleta no implique tocar cien vistas.
- **Gráficas:** si usas una librería de gráficas para la tendencia de asistencia, sus colores de ejes/leyenda no heredan Tailwind automáticamente — deben actualizarse explícitamente al cambiar de tema.

## 5. Estados de Asistencia (dominio del negocio)

Estados válidos que debe soportar el modelo y la base de datos:

- `Entrada`
- `Salida`
- `Entrada T.E.` (tiempo extra)
- `Salida T.E.` (tiempo extra)

Recomendación: modelar como `enum` nativo de PHP (`App\Enums\AttendanceStatus`) con un método `label()` para el texto mostrado en UI, en vez de strings mágicos o constantes sueltas — así el IDE y el análisis estático detectan valores inválidos en tiempo de desarrollo.

## 6. Autenticación del checador (métodos soportados)

El modelo `Employee` debe soportar los tres métodos de verificación que ofrece el dispositivo: **huella dactilar**, **contraseña** (PIN numérico del dispositivo) y **tarjeta RFID**. Al diseñar la tabla, considera:

- Cada método puede o no estar habilitado por empleado — no asumas que todos tienen los tres.
- El *template* de huella (dato binario propietario del fabricante, no una imagen) y el número de tarjeta RFID son datos sensibles de identificación — trátalos con el mismo cuidado que credenciales, no los expongas en logs ni en respuestas de API que no lo requieran explícitamente.
- Un mismo empleado puede existir en varios dispositivos: la relación es empleado ↔ múltiples checadores, no 1 a 1 (ver `docs/zkteco.md`, sección de sincronización multi-dispositivo).

## 7. Archivos de reglas adicionales (cárgalos según la tarea)

CRITICAL: cuando la tarea lo amerite, usa tu herramienta de lectura para cargar el archivo correspondiente antes de actuar — no asumas su contenido de memoria:

- Tarea relacionada con el checador / ZKTeco / sincronización de asistencias → lee `docs/zkteco.md`
- Tarea de revisión general de errores/optimización de backend → lee `docs/checklist-backend.md`
- Tarea de frontend / dashboard / vistas / dark mode → lee `docs/diseno.md`
- Cualquier tarea que implique escribir código (todas) → sigue el flujo en `docs/flujo-de-trabajo.md`

## 8. Formato de respuesta

```
Diagnóstico
Plan
Cambios realizados (archivo: qué y por qué)
Verificación
Riesgos / pendientes
```
