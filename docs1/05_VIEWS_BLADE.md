# 05_VIEWS_BLADE.md — Vistas y Componentes Blade

## Resumen
- Layout principal: layouts/admin.blade.php (302 lineas)
- 40+ vistas Blade organizadas en modulos
- 6 componentes reutilizables
- 3 partials para UI comun

---

## Layout Principal (layouts/admin.blade.php)

### Estructura
- **Sidebar fijo** (app-sidebar): navegacion agrupada, brand, perfil usuario, logout
- **Topbar** (topbar): heading, busqueda global (Ctrl+K), notificaciones, theme toggle, fecha, sidebar toggle
- **Main content** (app-main): alerts globales, yield content
- **Toast stack** (toast-stack): notificaciones toast
- **Notify flyout** (notify-flyout): centro notificaciones
- **Command palette** (cmd-overlay): busqueda global Ctrl+K
- **Confirm root** (data-confirm-root): dialogos confirmacion

### Caracteristicas Implementadas
- **Dark Mode**: script inline en head para FOUC prevention, 3 estados (light/dark/system), localStorage dash-theme
- **Sidebar Collapse**: 2 estados, localStorage dash-sidebar-collapsed, tooltips en modo colapsado
- **Global Search**: Ctrl+K abre command palette
- **Notifications**: badge en campana, flyout con tabs (all/unread), persistencia read state
- **Toasts**: 4 tipos (success/error/warning/info), progress bar, auto-dismiss, actions
- **Confirm Dialogs**: require-type para destructivas, ESC para cerrar, scrim click para cancelar
- **Global Alerts**: sessionStorage dismiss keys

### Variables Inyectadas (window.__dash)
`javascript
{
  notifications: [...],  // array de notificaciones para badge
  search: { pages, devices, employees },  // datos command palette
  alerts: [...]  // alertas globales
}
`

---

## Vistas Dashboard

### dashboard.blade.php (293 lineas)
**Secciones:**
1. **Banner contexto activo** - fecha, empleados hoy, checks, primera/ultima checada, link asistencias
2. **KPI Grid** (5 tarjetas) - data-kpis-url para Heartbeat polling
   - Chequeos hoy, Empleados hoy, Entradas, Salidas, Checadores online
   - Cada una: value, label, color, icon, trend (up/down/flat + %), sparkline SVG
3. **Trend Chart** - SVG line chart con gradient fill, selector rango (hoy/7d/30d/12m)
4. **Pipeline** - 6 steps fijos con progress bar colorida
5. **Panel dual**: Donut chart (distribucion type hoy) + Recent attendances table

**Variables esperadas:**
- kpis (array 5), pipeline (array 6), donut (segments+total), trend (array), rango, rangos, recent (Attendance[]), todayInfo

---

## Vistas Dispositivos

### devices/index.blade.php (143 lineas)
- Header con stats + acciones (create, deduplicate - admin)
- KPI Grid (4 tarjetas) con sparklines semanales
- Tabla devices: name, IP, port, status badge, employees_count, attendances_count, acciones
- Acciones: show, sync-attendances (form POST), edit, destroy (admin)

### devices/show.blade.php
- Info dispositivo (local + opcional remoto)
- Empleados enrolados (con pivot data: uid, role, card_no, fingerprints_count)
- Asistencias recientes (15) con employee, state badge, device link
- Syncs recientes (4) con stage, counts, error
- Fingerprints (50) con employee, finger, registered_at
- Sparklines semanales por tabla

### devices/create.blade.php / edit.blade.php
- Formulario: name, ip, port, password, description, serial_number
- Validacion client-side nativa HTML5

---

## Vistas Empleados

### employees/index.blade.php (187 lineas)
- Filtros: busqueda (q), device_id select, boton buscar, limpiar
- Tabla: user_id (ID), nombre, dispositivos (chips), rol (badge), tarjeta, estado, acciones
- **JS Inline**: debounce 300ms en busqueda, fetch HTML reemplaza tbody
- Acciones admin: edit, upload-fingerprints (form), destroy (confirm)

### employees/create.blade.php
- Formulario: device_id (select), name, user_id, password, card_number, role

### employees/edit.blade.php
- Paneles: Biometrico (huellas asignadas, disponibles para asignar) + Acceso (credenciales, enrolamientos, sync)
- Huellas disponibles: dedup por employee|device|finger|template_hash
- Dispositivos disponibles para enrolar/sync

---

## Vistas Asistencias

### attendances/index.blade.php
- Filtros: device_id, state (type real), from, to
- Tabla: fecha/hora, empleado, user_id, state badge, dispositivo
- Export CSV / Print links

### attendances/print.blade.php
- Vista imprimible sin paginacion, mismos filtros

---

## Vistas Academia (20+)

### Estructura Modular
`
academia/
├── dashboard/index.blade.php
├── ciclos/ (index, create, edit, show)
├── grupos/ (index, show, asistencia)
├── alumnos/ (index, show, kardex, historial)
├── profesores/ (index, show, horario)
├── horarios/ (clase, profesor, aula, base, persona)
├── kardex/ (index, show, historial, print)
├── cursos/ (index, create, edit, show)
├── planes/ (index, create, edit, show)
`

### Patrones Comunes
- Tablas con filtros AJAX (api/academia/*)
- Modales para CRUD (create/edit en drawer/modal)
- Kardex: tabla notas + print PDF
- Horarios: 5 vistas especializadas

---

## Componentes Reutilizables (resources/views/components/)

| Componente | Props | Uso |
|------------|-------|-----|
| stat-card | icon, label, value, color, slot (trend, spark) | KPI cards dashboard/devices |
| drawer | title, slot (header, body, footer) | Modales laterales |
| data-table | headers, rows, actions, sortable | Tablas genericas |
| badge | variant, text | Badges estado/rol |
| sparkline | points, color, width, height | Mini charts en KPIs |
| donut | segments, total, emptyText | Graficas dona |
| empty-state | icon, title, desc, cta, ctaLink | Estados vacios |

---

## Partials (resources/views/partials/)

| Partial | Descripcion |
|---------|-------------|
| sparkline.blade.php | SVG path generado inline (points array) |
| donut.blade.php | SVG donut chart con legend |
| empty-state.blade.php | Ilustracion + mensaje + CTA opcional |

---

## Variables Controller Vista (Mapping Critico)

### DashboardController@index
`
kpis -> dashboard.blade.php (KPI grid)
pipeline -> dashboard.blade.php (pipeline steps)
donut -> dashboard.blade.php (donut chart)
trend -> dashboard.blade.php (trend chart)
rango/rangos -> dashboard.blade.php (selector)
recent -> dashboard.blade.php (recent table)
todayInfo -> dashboard.blade.php (banner)
`

### DeviceController@show
`
device -> devices/show.blade.php
info -> devices/show.blade.php
employees -> devices/show.blade.php (con pivot)
attendances -> devices/show.blade.php (con employee)
recentSyncs -> devices/show.blade.php
fingerprints -> devices/show.blade.php
spark -> devices/show.blade.php
`

### DeviceController@refreshData (JSON)
`
counts: employees, attendances, fingerprints, status, status_label
employees: [{id, user_id, uid, name, role, role_label, card_no, fingerprints_count, edit_url, upload_url, destroy_url, sync_fingerprint_url}]
attendances: [{recorded_at, employee_name, user_id, state, state_label, state_color}]
recent_syncs: [{operation_label, status, stage, created, updated, error, finished_at}]
fingerprints: [{user_id, employee_name, finger, registered_at}]
`

### EmployeeController@edit
`
employee (con devices, fingerprints, syncs)
availableFingerprints (dedup, sorted)
availableDevices (no enrolados)
syncDevices (todos)
totalDevices (count)
`

---

## Referencias Rutas en Blade (Verificadas)

| Vista | Rutas Usadas | Status |
|-------|--------------|--------|
| layouts/admin | dashboard, devices.index, employees.index, fingerprints.index, attendances.index, academia.dashboard, operations.queue, operations.notifications, devices.create, employees.create, attendances.export, logout | OK |
| dashboard | attendances.index, devices.index | OK |
| devices.index | devices.show, devices.create, devices.edit, devices.destroy, devices.sync-attendances, devices.deduplicate | OK |
| devices.show | devices.edit, employees.edit, devices.employees.upload-fingerprints, devices.employees.remove, devices.sync-fingerprints | OK |
| employees.index | employees.create, employees.edit, employees.upload-fingerprints, employees.destroy, devices.show | OK |
| employees.edit | employees.update, employees.upload-fingerprints, employees.assign-fingerprint, employees.copy-fingerprint, employees.delete-fingerprint, employees.update-card, employees.enroll-device, employees.sync-devices, devices.show | OK |
| attendances.index | attendances.export, attendances.print, devices.show | OK |

---

## Problemas Detectados

1. **Vistas Firebird faltantes**: routes/web.php lineas 103-104 referencian irebird.index y irebird.sync que NO existen
2. **JS Inline en employees.index**: fetch HTML reemplaza tbody - frágil, deberia ser API JSON
3. **Sin estados de carga** en vistas que consumen AJAX (employees.index, academia selects)
4. **Duplicacion sparkline logic**: devices/index y devices/show usan misma logica privada
4. **Command Palette data**: window.__dash.search requiere composer AdminLayoutComposer - verificar cobertura
5. **Notificaciones**: window.__dash.notifications array - verificar composer inyecta datos reales
