# 07_MIDDLEWARE_AUTH.md — Middleware, Autenticación y Autorización

## Resumen
- Auth: Laravel default (session + email/password)
- Roles: User.role = 'admin' | 'operator' (string, no enum)
- Middleware: auth, admin, guest, throttle
- Gates/Policies: NO implementados
- Rate limiting: NO en rutas criticas

---

## Middleware Registrados (app/Http/Kernel.php)

### Global Middleware ()
1. TrustProxies - Proxy headers
2. HandleCors - CORS
3. PreventRequestsDuringMaintenance - Modo mantenimiento
4. ValidatePostSize - Tamaño POST max
5. TrimStrings - Trim input strings
6. ConvertEmptyStringsToNull - Empty strings a null

### Middleware Groups ()
**web:**
1. EncryptCookies
2. AddQueuedCookiesToResponse
3. StartSession
4. ShareErrorsFromSession
5. VerifyCsrfToken
6. SubstituteBindings

**api:**
1. ThrottleRequests:api (60 req/min)
2. SubstituteBindings

### Middleware Aliases ()
| Alias | Clase | Descripcion |
|-------|-------|-------------|
| auth | Authenticate | Redirige a /login si no autenticado |
| admin | EnsureAdmin | 403 si user.role !== 'admin' |
| guest | RedirectIfAuthenticated | Redirige a / si ya autenticado |
| throttle | ThrottleRequests | Rate limiting parametrizable |
| auth.basic | AuthenticateWithBasicAuth | Basic auth |
| auth.session | AuthenticateSession | Session confirmation |
| cache.headers | SetCacheHeaders | Cache headers |
| can | Authorize | Gate/Policy authorization |
| password.confirm | RequirePassword | Password confirmation |
| signed | ValidateSignature | Signed URLs |
| verified | EnsureEmailIsVerified | Email verified |

---

## Autenticación (AuthController)

### Login Flow
1. GET /login -> AuthController@create -> auth.login view
2. POST /login -> AuthController@store
   - Validacion: email required, password required
   - Auth::attempt(, )
   - Fallo: back con error 'email' -> 'Las credenciales no son validas.'
   - Exito: session()->regenerate() -> redirect intended dashboard
3. POST /logout -> AuthController@destroy
   - Auth::logout()
   - session()->invalidate()
   - session()->regenerateToken()
   - redirect /login

### User Model
- Fillable: name, email, password, role
- Hidden: password, remember_token
- Casts: email_verified_at datetime
- isAdmin(): role === 'admin'

---

## Autorización Actual

### Middleware EnsureAdmin (app/Http/Middleware/EnsureAdmin.php)
`php
abort_unless(->user()?->isAdmin(), 403, 'No tienes permisos para realizar esta accion.');
`
- Solo verifica user.role === 'admin'
- No hay granularidad (no distingue read/write/delete)
- No usa Gates ni Policies

### Rutas Protegidas por Admin
Todas las rutas de escritura (POST, PUT, DELETE) en:
- devices.* (create, store, edit, update, destroy, sync-*, deduplicate, set-time, clear-attendance, restore)
- employees.* (create, store, edit, update, destroy, upload-fingerprints, assign-fingerprint, copy-fingerprint, delete-fingerprint, update-card, enroll-device, sync-devices)
- operations.* (queue, queueData, cancel, retry, delete)
- academia.* (ciclos, cursos, planes - CRUD completo)

### Rutas Solo Auth (sin admin)
- GET /dashboard, /kpis/json
- GET /devices, /devices/{device}, /devices/{device}/sync-status, /refresh-data, /progress
- GET /employees, /employees/{employee}/edit
- GET /fingerprints
- GET /attendances, /attendances/export, /attendances/print
- GET /notifications
- Academia: GET index, show, kardex, horario (solo lectura)

---

## Problemas Detectados

### CRITICO
1. **Sin Rate Limiting en Login**
   - POST /login sin throttle
   - Vulnerable a brute force
   - Solucion: agregar 	hrottle:5,1 a login.store

2. **Sin Rate Limiting en Sync Endpoints**
   - POST /devices/*/sync-* sin throttle
   - Podrian saturar cola/checadores
   - Solucion: 	hrottle:10,1 en sync endpoints

3. **Sin Policies/Gates**
   - Autorizacion hardcodeada en middleware admin
   - No se puede distinguir permisos por recurso/accion
   - No hay can:view,device ni can:delete,employee

### ALTO
4. **CSRF en AJAX Endpoints**
   - api/academia/* no tienen VerifyCsrfToken (estan en web middleware group pero son GET)
   - Forms data-sync incluyen CSRF manualmente
   - Endpoints POST de sync usan session CSRF (OK)

5. **Password del Dispositivo en BD**
   - Device.password encrypted cast (OK)
   - DeviceEmployee.password encrypted cast en pivot (OK)
   - Pero ZktecoService::setUser usa password del dispositivo o '1234' default
   - No hay rotacion ni expiracion de PINs

6. **Session Regeneration Solo en Login**
   - Login: session()->regenerate() (OK)
   - No hay regeneracion periodica ni en elevacion de privilegios

### MEDIO
7. **Roles como String (no Enum)**
   - User.role = 'admin' | 'operator'
   - No validacion a nivel BD (CHECK constraint)
   - Typos posibles: 'adim', 'operador'

8. **No Email Verification**
   - User tiene email_verified_at cast pero no middleware verified
   - Rutas no protegidas por verified

9. **Remember Token**
   - Login usa remember parameter
   - No hay invalidacion de remember tokens en logout (solo session)

### BAJO
10. **Two-Factor Auth Ausente**
    - No 2FA para admins
    - Recomendado para acceso a checadores

---

## Referencias en Controladores

### Middleware Aplicado (routes/web.php)
`php
// Auth global
Route::middleware('auth')->group(...)

// Guest solo login
Route::middleware('guest')->group(login routes)

// Admin en rutas escritura
->middleware('admin')  // devices.create, store, edit, update, destroy
->middleware('admin')  // employees.create, store, edit, update, destroy
->middleware('admin')  // operations.*

// Admin en grupo
Route::middleware('admin')->group(sync endpoints)
`

### Verificaciones Inline (EmployeeController)
`php
// update(): propaga a TODOS dispositivos sin verificar permisos por dispositivo
foreach ( as ) {
     = new ZktecoService();
     = ->setUser(...) && ;
}

// destroy(): elimina de TODOS sin confirmar por dispositivo
foreach ( as ) {
    (new ZktecoService())->removeUser(...);
}
`

---

## Recomendaciones

### Inmediato (CRITICO)
1. Agregar 	hrottle:5,1 a login.store
2. Agregar 	hrottle:10,1 a endpoints sync POST
3. Crear Policies basicas: DevicePolicy, EmployeePolicy, AttendancePolicy

### Corto Plazo (ALTO)
4. Migrar roles a Enum PHP 8.1+ (Admin, Operator)
5. Agregar CHECK constraint en users.role
6. Implementar email verification para usuarios nuevos

### Mediano Plazo (MEDIO)
7. Crear Form Requests para validacion centralizada
8. Implementar Policies granulares por recurso/accion
9. Auditar exposicion de datos sensibles en JSON responses

### Largo Plazo (BAJO)
10. Evaluar 2FA para admins (Laravel Fortify)
11. Session regeneration periodica
12. PIN rotation policy para checadores
