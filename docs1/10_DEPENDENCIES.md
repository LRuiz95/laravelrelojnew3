# 10_DEPENDENCIES.md - Dependencias del Proyecto

## Composer (PHP)

### Produccion (require)
| Paquete | Version | Proposito | Estado |
|---------|---------|-----------|--------|
| php | ^8.1 | Runtime | Requerido |
| coding-libs/zkteco-php | ^0.0.35 | SDK ZKTeco TCP/IP | Critico - Verificar version exacta |
| guzzlehttp/guzzle | ^7.2 | Cliente HTTP | Usado por SDK/Firebird |
| laravel/framework | ^10.0 | Framework core | LTS hasta 2025 |
| laravel/sanctum | ^3.2 | API tokens | Solo /api/user |
| laravel/tinker | ^2.8 | REPL | Dev only |

### Desarrollo (require-dev)
| Paquete | Version | Proposito |
|---------|---------|-----------|
| fakerphp/faker | ^1.9.1 | Factories |
| laravel/pint | ^1.0 | Code style (PSR-12) |
| laravel/sail | ^1.18 | Docker dev environment |
| mockery/mockery | ^1.4.4 | Mocking tests |
| nunomaduro/collision | ^7.0 | Error handling tests |
| phpunit/phpunit | ^10.0 | Testing framework |
| spatie/laravel-ignition | ^2.0 | Error pages dev |

### Configuracion Composer
- optimize-autoloader: true
- preferred-install: dist
- sort-packages: true
- allow-plugins: pestphp/pest-plugin (no usado actualmente)

---

## NPM (Frontend)

### DevDependencies
| Paquete | Version | Proposito |
|---------|---------|-----------|
| axios | ^1.1.2 | HTTP client (no usado en app.js) |
| laravel-vite-plugin | ^0.7.2 | Vite integration Laravel |
| vite | ^4.0.0 | Build tool |

### Scripts
dev: vite
build: vite build

### Vite Config (vite.config.js)
- Laravel plugin
- Input: resources/css/app.css, resources/js/app.js
- Output: public/build/

---

## Servicios Externos

### ZKTeco Checadores
- Protocolo: TCP/IP (UDP para discovery)
- Puerto: 4370 (configurable por dispositivo)
- SDK: coding-libs/zkteco-php ^0.0.35
- Autenticacion: Password por dispositivo (encrypted en BD)
- Timeout: 15s base, adaptativo hasta 60s
- Reintentos: 3 (corto), 5 (largo) con backoff 800ms

### Firebird (Legado)
- Driver: PDO_Firebird
- Config: config/database.php - connections.firebird
- DSN: firebird:host=X;dbname=Y;charset=UTF8
- Uso: FirebirdReader, FirebirdSyncJob, SyncStrategies

---

## Base de Datos

### MySQL/MariaDB
- Produccion: rh_reloj
- Testing: rh_reloj_testing (migrate:fresh cada test)
- Version: MySQL 8.0+ (window functions para deduplicate)
- Timezone: UTC (servidor), dispositivos reportan local

### Tablas Principales (Core)
- users, password_reset_tokens, failed_jobs, personal_access_tokens
- devices, employees, attendances, fingerprints
- device_syncs, device_sync_items, device_employee (pivot)
- jobs

### Tablas Academia (12+)
- ciclos, grupos, alumnos, profesores, horarios_det
- cursos, cursos_det, planes, niveles, materias
- metodos_eval, sedes, turnos, contratos, sesiones_base
- alumnos_grupos, alumnos_kardex

### Tablas Firebird Sync
- firebird_syncs, firebird_sync_items

---

## Extensiones PHP Requeridas
- pdo_mysql
- pdo_firebird (para Firebird legacy)
- openssl
- mbstring
- tokenizer
- xml
- ctype
- json
- bcmath (recomendado)
- redis (opcional, para cache/queue)

---

## Versionamento y Actualizaciones

### Politica
- NO actualizar automaticamente dependencias sin auditoria
- Laravel 10 LTS hasta febrero 2025 (bug fixes) / agosto 2025 (security)
- PHP 8.1 EOL diciembre 2025
- coding-libs/zkteco-php: verificar changelog antes de actualizar

### Compatibilidad Conocida
- coding-libs/zkteco-php ^0.0.35 funciona con PHP 8.1+
- Laravel 10 requiere PHP ^8.1
- Vite 4 requiere Node 14.18+ / 16+

---

## Auditoría de Seguridad Dependencias

composer audit
npm audit
composer outdated --direct

### Riesgos Identificados
1. coding-libs/zkteco-php ^0.0.35: Version pre-1.0, API inestable
2. guzzlehttp/guzzle ^7.2: Version antigua (actual 7.8+)
3. vite ^4.0.0: Version antigua (actual 5.x)
4. laravel-vite-plugin ^0.7.2: Version antigua (actual 1.x)

### Recomendaciones
- Monitorear coding-libs/zkteco-php para version 1.0 estable
- Planificar actualizacion Vite 5 + laravel-vite-plugin 1.x (breaking changes)
- Mantener guzzle 7.x compatible con SDK ZKTeco
