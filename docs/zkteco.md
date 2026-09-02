# Integración con checador ZKTeco

## 1. Antes de tocar este código

* Confirma en `composer.json` qué paquete/SDK se usa exactamente y su versión.
* Ubica el servicio/job/comando artisan actual que maneja la conexión — no crees uno nuevo si ya existe uno.
* Identifica si la comunicación es directa por socket TCP (SDK tipo ZKLib) o vía un middleware/agente intermedio (algunos entornos usan un servicio local que expone HTTP porque el checador vive en la red LAN de la oficina y el servidor Laravel está en otra red). Esto cambia por completo la estrategia de manejo de errores.

## 2. Puntos Críticos de Conexión y Red

* **Conexión de red:** El dispositivo se comunica por TCP/IP (puerto 4370 por defecto, aunque es configurable en el propio equipo). Todo código que hable con el checador debe manejar:
  * Timeout de conexión explícito (no dejar el default del SDK sin verificar cuánto es).
  * Dispositivo apagado, desconectado de red, o con la IP cambiada (no debe tumbar el resto de la app — encapsula la llamada en try/catch y captura la excepción específica del SDK, no un `catch (\Throwable)` genérico que oculte el tipo de fallo real).
  * Reintentos razonables antes de fallar (con backoff exponencial, nunca en loop infinito). Ejemplo de política: 3 intentos, esperas de 2s/5s/10s, luego marcar el dispositivo como `offline` en BD y notificar.
  * Actualizar un campo de estado (`last_seen_at`, `status: online|offline`) en la tabla de dispositivos cada vez que se intenta conectar, para que el dashboard pueda mostrar el indicador de conexión en tiempo real sin tener que hacer ping activo desde el frontend.

* **Múltiples dispositivos, una sola institución:** Cada registro de asistencia debe identificarse con el dispositivo de origen (`device_id` FK) para evitar cruzar datos entre sucursales o edificios. Considera también:
  * Un mismo empleado puede estar dado de alta en varios checadores (para poder checar en cualquier entrada del campus). La sincronización de altas/bajas de empleados debe propagarse a **todos** los dispositivos relevantes, no solo al primero.
  * Si un checador tiene su propio reloj interno desalineado, los timestamps que reporta pueden no coincidir con el reloj del servidor — considera sincronizar la hora del dispositivo (algunos SDKs exponen `setTime()`) o al menos registrar el offset detectado.

## 3. Manejo de Datos (Base de Datos y Sincronización)

* **Sincronización incremental:** Evita traer y reprocesar todo el historial del dispositivo cada vez — identifica y trae solo los registros nuevos. Estrategias comunes:
  * Guardar el último `record_id`/índice de asistencia sincronizado por dispositivo (`devices.last_synced_record_id`) y pedir al SDK solo los posteriores, si el paquete lo soporta.
  * Si el SDK no soporta paginación/filtro nativo, traer todo pero comparar contra un índice único ya existente en BD antes de insertar (ver punto de duplicados) — es menos eficiente pero más portable entre paquetes.
  * Considera limpiar el buffer del dispositivo (`clearAttendanceLog()` si el SDK lo expone) solo después de confirmar que los registros ya se persistieron correctamente en BD — nunca antes, o se pierden datos ante un fallo a mitad de sincronización.

* **Duplicados al importar asistencias:** Verifica que exista una restricción única real (`employee_id` + `timestamp` + `device_id`, con índice único compuesto en la migración) antes de insertar. Si no existe, propón la migración explícitamente. Usa `insertOrIgnore()` o `upsert()` de Eloquent en el batch de importación en vez de un `foreach` con `find()` + `save()`, mucho más lento y propenso a condiciones de carrera si dos sincronizaciones corren en paralelo.

* **Zona horaria:** Confirma cómo se está normalizando la zona horaria antes de asumir que las fechas del checador ya vienen correctas para la BD. El dispositivo normalmente reporta hora local sin información de zona horaria — si el servidor MySQL guarda en UTC hay que convertir explícitamente al insertar, y volver a convertir al mostrar en el dashboard según la zona del usuario/institución.

## 4. Sincronización de credenciales biométricas (huella, contraseña, RFID)

* **Huella dactilar:** el dato que se sube/descarga del dispositivo es un *template* biométrico binario (no una imagen), propietario del algoritmo del fabricante. Verifica si el SDK instalado expone métodos para leerlo y en qué formato lo entrega (base64, binario crudo, etc.) antes de diseñar la columna de BD — probablemente necesites `BLOB`/`LONGBLOB` o texto base64, y **no** debe exponerse en respuestas JSON públicas de la API salvo endpoints administrativos explícitos y protegidos.
* **Renderizado gráfico de huellas:** algunos SDKs permiten reconstruir una imagen aproximada del template para visualización (no es la huella real reversible, es una representación). Antes de prometer esta funcionalidad en el frontend, confirma explícitamente si el SDK/versión instalada lo soporta — no todos lo hacen, y algunos requieren un algoritmo de reconstrucción propietario que no viene incluido.
* **Contraseña (PIN del dispositivo):** es un PIN numérico corto configurado directamente en el equipo, no la contraseña de la cuenta del sistema web — no los confundas ni los compartas en el modelo de usuario de Laravel.
* **Tarjeta RFID:** el número de tarjeta debe ser único por institución (índice único en BD) para evitar que dos empleados terminen compartiendo credencial por error de captura.
* **Subida masiva (bulk sync) hacia los dispositivos:** al subir empleados/huellas/tarjetas/contraseñas del sistema web hacia los equipos físicos, procesa en lotes con control de progreso y tolerancia a fallos parciales (si un dispositivo se cae a la mitad de la carga de 200 empleados, debe quedar claro cuáles sí se subieron y cuáles no, para reintentar solo los pendientes — no repetir todo el lote).

## 5. Al reportar un hallazgo

* Indica explícitamente: qué archivo, qué método, qué escenario de falla no está cubierto, y el riesgo concreto.
* Si el hallazgo depende de una capacidad específica del SDK (por ejemplo, si soporta o no extracción de template), dilo explícitamente como "verificar en la documentación del paquete X versión Y" en vez de asumir que sí o que no.
