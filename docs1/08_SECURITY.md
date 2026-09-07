# 08_SECURITY.md — Hallazgos de Seguridad

## Clasificacion: CRITICAL / HIGH / MEDIUM / LOW

---

## CRITICAL

### SEC-001: Sin Rate Limiting en Login
- **Archivo**: routes/web.php linea 31
- **Problema**: POST /login sin middleware throttle
- **Riesgo**: Brute force ilimitado, enumeracion de emails
- **Impacto**: Compromiso de cuentas admin
- **Solucion**: ->middleware('throttle:5,1') en login.store

### SEC-002: Sin Rate Limiting en Endpoints Sync
- **Archivo**: routes/web.php lineas 119-131
- **Problema**: POST /devices/*/sync-* sin throttle
- **Riesgo**: DoS en cola jobs, saturacion checadores, agotamiento recursos
- **Impacto**: Denegacion de servicio, bloqueo hardware
- **Solucion**: ->middleware('throttle:10,1') en sync endpoints

### SEC-003: Templates Biometricos en Respuestas JSON
- **Archivo**: DeviceController@refreshData lineas 227-238, DeviceController@show
- **Problema**: Fingerprint.template (binario) cargado en relaciones, potencial exposicion
- **Riesgo**: Fuga de datos biométricos sensibles (PII critico)
- **Mitigacion**: Modelo Fingerprint tiene $hidden = ['template'] pero eager load with('employee') no garantiza ocultacion en JSON manual
- **Verificacion**: Revisar que 	emplate nunca llega a JSON responses

---

## HIGH

### SEC-004: Sin Policies/Gates - Autorizacion Hardcodeada
- **Archivo**: app/Http/Middleware/EnsureAdmin.php, routes/web.php
- **Problema**: Solo middleware admin binario, sin granularidad por recurso/accion
- **Riesgo**: Escalada de privilegios horizontal/vertical, no hay least privilege
- **Ejemplo**: Operator podria acceder a destroy si se cambia middleware

### SEC-005: Validacion Inline - Sin Form Requests
- **Archivo**: Todos controladores (DeviceController, EmployeeController, etc.)
- **Problema**: Validacion inline en metodos, no reutilizable, no testeable aisladamente
- **Riesgo**: Inconsistencias, bypass si se llama metodo internamente
- **Violacion**: AGENTS.md requiere Form Requests dedicados

### SEC-006: PIN/Credenciales Dispositivo en Logs
- **Archivo**: ZktecoService multiples metodos
- **Problema**: Log::error('ZKTeco setUser error: '.->getMessage(), ['device' => ->device->ip])
- **Riesgo**: Excepcion podria contener password/PIN en message
- **Verificacion**: Revisar que InvalidParamException no expone credenciales

### SEC-007: Password Dispositivo Default '1234'
- **Archivo**: ZktecoService linea 823, EmployeeController linea 142, 476
- **Problema**: 'password' =>  ?: '1234' - default debil
- **Riesgo**: Dispositivos nuevos con PIN por defecto conocido
- **Solucion**: Requerir password en creacion, no default

---

## MEDIUM

### SEC-008: Roles como String sin Validacion BD
- **Archivo**: database/migrations/2024_01_01_000005_add_role_to_users_table.php, User model
- **Problema**: ole VARCHAR sin CHECK constraint, typos posibles
- **Riesgo**: 'adim', 'operador' bypass autorizacion
- **Solucion**: Enum PHP 8.1+ + migration CHECK constraint

### SEC-009: Email Verification No Obligatorio
- **Archivo**: User model, routes/web.php
- **Problema**: email_verified_at cast existe pero middleware erified no usado
- **Riesgo**: Cuentas sin verificar pueden acceder a todo
- **Solucion**: Agregar erified middleware a rutas criticas

### SEC-010: CSRF en Endpoints AJAX GET
- **Archivo**: routes/web.php lineas 87-99 (api/academia/*)
- **Problema**: Endpoints GET sin CSRF (normal), pero mutan estado via session
- **Riesgo**: CSRF si endpoints cambian a POST sin proteccion
- **Verificacion**: Confirmar todos GET son idempotentes

### SEC-011: Session Regeneration Solo en Login
- **Archivo**: AuthController@store linea 32
- **Problema**: Solo login regenera session, no en elevacion privilegios ni cambio password
- **Riesgo**: Session fixation en escenarios avanzados
- **Solucion**: Regenerar en role change, password change

### SEC-012: Remember Token No Invalidado en Logout
- **Archivo**: AuthController@destroy
- **Problema**: session()->invalidate() pero remember token cookie persiste
- **Riesgo**: Reutilizacion remember token si cookie robada
- **Solucion**: Auth::logout() deberia invalidar remember (verificar Laravel default)

### SEC-013: Exposicion user_id en URLs/Logs
- **Archivo**: Múltiples (routes, controllers, logs)
- **Problema**: user_id (PIN empleado) en URLs, logs, JSON responses
- **Riesgo**: Enumeracion empleados, correlacion datos
- **Mitigacion**: Usar UUIDs internos, no exponer PIN en URLs

---

## LOW

### SEC-014: Two-Factor Auth Ausente
- **Riesgo**: Cuentas admin solo protegidas por password
- **Recomendacion**: Laravel Fortify + 2FA para admins

### SEC-015: Password History/Rotation Ausente
- **Riesgo**: PINs checadores nunca rotan, usuarios reusan passwords
- **Recomendacion**: Politica rotacion PINs dispositivos

### SEC-016: Debug Info en Excepciones
- **Archivo**: ZktecoService catch bloques
- **Problema**: Log::error('...' . ->getMessage()) - stack trace en logs
- **Riesgo**: Info sensible en logs si acceso no autorizado
- **Solucion**: Solo log message, no trace en produccion

### SEC-017: Hardcoded IPs en Tests/Seeders
- **Archivo**: Tests Feature, DatabaseSeeder
- **Problema**: IPs reales (192.168.x.x) en tests
- **Riesgo**: Fuga topologia red interna
- **Solucion**: Usar IPs ficticias (10.0.0.x) en tests

### SEC-018: .env.example Completo
- **Archivo**: .env.example
- **Problema**: Contiene todas keys incluyendo DB_PASSWORD vacio pero estructura real
- **Riesgo**: Filtracion estructura configuracion
- **Solucion**: .env.example minimal, documentacion separada

---

## Resumen por Severidad

| Severidad | Count | Accion Inmediata |
|-----------|-------|------------------|
| CRITICAL | 3 | SI - Bloquean despliegue |
| HIGH | 4 | SI - Esta semana |
| MEDIUM | 6 | Proximo sprint |
| LOW | 5 | Backlog |

---

## Checklist Hardening Pendiente

- [ ] Rate limiting login (throttle:5,1)
- [ ] Rate limiting sync endpoints (throttle:10,1)
- [ ] Form Requests para todos controladores
- [ ] Policies basicas (Device, Employee, Attendance)
- [ ] Enum Role + CHECK constraint BD
- [ ] Email verification middleware
- [ ] PIN default removido, requerido en creacion
- [ ] Auditar JSON responses por template biométrico
- [ ] Session regeneration en role/password change
- [ ] 2FA para admins (Fortify)
- [ ] Log sanitization (no stack traces produccion)
