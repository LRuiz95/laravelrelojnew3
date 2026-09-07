# 06_JS_AJAX.md - Flujos JavaScript/AJAX

## Resumen
- Framework: Vanilla JS modular (resources/js/app.js - 681 lineas)
- Build: Vite 4, sin bundler de componentes (no Vue/React)
- CSS: Bootstrap 5 + CSS Variables (no Tailwind)
- Patron: Modulos auto-ejecutables en IIFE, expuestos en window

## Modulos JS (Orden de Inicializacion)

### 1. Theme (lineas 10-66)
Estados: light, dark, system
Persistencia: localStorage dash-theme
FOUC prevention: script inline en head aplica data-theme antes de paint
System listener: matchMedia change solo si preference = system
API: Theme.toggle(), Theme.apply(), Theme.init(), Theme.bind()

### 2. Heartbeat (lineas 69-165)
Polling 20s (interval)
Endpoints: /kpis/json (KPIs) + window.__dash.notifications (badge)
InFlight guard para evitar solapamiento
Empareja KPIs por label (no indice) - robusto a reorden
Dispatcha themechange event
API: Heartbeat.start(), Heartbeat.stop()

### 3. Sidebar (lineas 172-232)
2 estados: expanded, collapsed (localStorage dash-sidebar-collapsed)
Tablet (641-1024px): default collapsed
Desktop (1024px+): default expanded
Mobile (640px): off-canvas overlay (sidebar-mobile-open)
Ctrl+B toggle
Tooltips en modo collapsed via CSS ::after
API: Sidebar.init(), Sidebar.toggle(), Sidebar.setCollapsed()

### 4. Toast (lineas 235-302)
Stack fixed bottom-right (max 3)
Tipos: success, error, warning, info (iconos bi-*)
Duraciones: success/info 5s, warning 8s, error persistente
Progress bar animado (CSS animation)
Pause on hover, click to dismiss
Actions: boton opcional con callback
Escape HTML automatico
API: Toast.show({type, title, message, action, duration})
window.dashToast = Toast.show

### 5. Confirm (lineas 336-419)
Modal dialog con scrim
Tipos: danger (rojo, require-type), info (azul)
Require-type: input debe coincidir exactamente (ej. ELIMINAR)
Keyboard: ESC cancela, Enter confirma (si no require-type)
Click scrim cancela (si no require-type)
Focus management: input o boton OK
API: Confirm.ask(opts) -> Promise<bool>, Confirm.confirmAll(scope)

### 6. Notifications (lineas 424-520)
Flyout right (z-index 1500)
Tabs: all, unread
Persistencia read: localStorage dash-notif-read + server read flag
Click item: navega a url o marca leido
Mark all read: localStorage + UI update
Badge count en campana (data-notif-badge)
ESC cierra flyout

### 7. CommandPalette (lineas 523-620)
Ctrl+K abre overlay centrado
Fuzzy search: pages, devices, employees
Navegacion: ArrowUp/Down, Enter selecciona, Esc cierra
Datos: window.__dash.search {pages, devices, employees}
Grupos con headers: SECCIONES, DISPOSITIVOS, EMPLEADOS
API: CommandPalette.open(), CommandPalette.close()

### 8. Global Alerts (lineas 623-633)
data-global-alert key -> sessionStorage dismiss persistente

### 9. Forms data-sync (lineas 636-671)
Intercepta form[data-sync] submit
Fetch POST con CSRF + X-Requested-With
Toast response (success, error)
Button loading state (spinner + disabled)
1.2s restore button

---

## Flujos AJAX Completos

### Flujo 1: Dashboard KPI Refresh (Heartbeat)
Blade dashboard.blade.php -> data-kpis-url Heartbeat.tick() cada 20s -> fetch(/kpis/json) -> DashboardController@kpisJson -> kpis() private method (5 queries) -> JSON -> Heartbeat.refreshKPIs -> Empareja por label (Map) -> Actualiza .kpi-value + .kpi-trend + kpi-flash animation -> Toast success silencioso

### Flujo 2: Device Show Refresh (Post-Sync)
Blade devices/show.blade.php -> JS escucha SyncProgressUpdated -> DeviceController@refreshData -> loadCount + queries -> JSON payload completo -> vanilla JS actualiza DOM

### Flujo 3: Sync Actions (Forms data-sync)
Blade devices/index.blade.php -> form[data-sync] submit -> JS intercepta, fetch POST -> DeviceController@syncUsers|syncAttendances -> queueSync() -> DeviceSync::create -> SyncDeviceJob::dispatch -> JSON -> Toast success -> Button restore 1.2s

### Flujo 4: Employee Search (Debounced)
Blade employees/index.blade.php -> input#employeeSearch @input (debounce 300ms) -> select#employeeDevice @change -> fetchEmployees() -> URLSearchParams q + device_id -> fetch(route(employees.index) + params, Accept: text/html) -> EmployeeController@index -> view employees.index -> DOMParser parse HTML -> replace tbody.innerHTML -> PROBLEMA: HTML completo, no JSON - fragil

### Flujo 5: Academia Selects Dependientes
Blade academia/* -> select@change -> fetch /api/academia/* -> AcademiaApiController -> JSON {success, data: []} -> JS popula selects

### Flujo 6: Command Palette Search
Ctrl+K -> CommandPalette.open() -> input @input -> buildResults(query) -> Filtra window.__dash.search -> Render cmd-body -> Enter/Click -> window.location.href

### Flujo 7: Theme Toggle
Blade layouts/admin.blade.php -> button[data-theme-toggle] @click -> Theme.toggle() -> cycles light->dark->system -> localStorage dash-theme -> document.documentElement.setAttribute(data-theme) -> Dispatch themechange -> Charts/Donuts: NO actualizados automaticamente (PENDIENTE)

### Flujo 8: Notifications
Blade layouts/admin.blade.php -> button[data-notifications-toggle] @click -> Notifications.openFlyout() -> render() desde window.__dash.notifications -> localStorage dash-notif-read -> Click item -> navigate o mark read -> Mark all read -> localStorage + UI update

### Flujo 9: Confirm Dialogs
Blade form[data-confirm] -> submit -> Confirm.ask({title, message, danger, requireType}) -> Modal render -> Promise -> Resolve true -> form.submit() -> Resolve false -> cancel

---

## Endpoints AJAX (Resumen)

| Endpoint | Controller | Metodo | Response | Consumidor |
|----------|------------|--------|----------|------------|
| /kpis/json | DashboardController | kpisJson | JSON 5 KPIs | Heartbeat |
| /devices/{device}/refresh-data | DeviceController | refreshData | JSON full device data | Post-sync JS |
| /devices/{device}/progress | DeviceController | progress | JSON sync progress | Polling UI |
| /devices/{device}/sync-status | DeviceController | syncStatus | JSON sync status | Polling UI |
| /api/academia/* (10) | AcademiaApiController | varios | JSON {success, data} | Selects dependientes |
| /employees (fetch HTML) | EmployeeController | index | HTML fragment | employees.index search |
| /sync-queue/data | OperationsController | queueData | JSON queue table | operations.queue |

---

## Problemas Detectados

1. Employee Search: Fetch HTML en vez de JSON - fragil, rompe si cambia estructura vista
2. Charts/Donuts no reaccionan a themechange: Heartbeat dispara event pero charts SVG no se actualizan
3. Employee search no tiene loading state: Debounce pero sin spinner/skeleton
4. Command Palette data: Depende de AdminLayoutComposer - verificar que inyecta search data
5. Notificaciones: window.__dash.notifications - verificar composer inyecta datos reales
6. Sin debounce en academia selects: Cambios disparan request inmediato
7. Sin error boundary: Fetch failures solo console.warn, sin UI feedback
8. CSRF en AJAX: Forms data-sync incluyen CSRF, pero academia selects NO (usan session)
9. Polling Heartbeat 20s: Quizas muy frecuente para KPIs que cambian poco
