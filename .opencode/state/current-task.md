TASK-2026-0911

Scope:
  Tres problemas relacionados pero independientes:
  1. Fix /academia/ciclos: página vacía cuando no hay ciclos o excepción no manejada
  2. Fix sincronización "ciclo de trabajo": unificar CicloActualService.resolve() vs current() para consistencia en header y controladores
  3. Mejora /employees: pasar variables faltantes (cargos, departamentos, sedes), extraer JS inline, mejorar UX tabla/filtros
  
  NO incluye: cambios en Firebird sync, ZKTeco, migraciones de BD, autenticación, pagos.

Test level: 3

Allowed files:
  - app/Http/Controllers/Academia/CicloController.php
  - app/Services/CicloActualService.php
  - app/Http/Controllers/EmployeeController.php
  - resources/views/academia/ciclos/index.blade.php
  - resources/views/academia/empty-ciclos.blade.php
  - resources/views/layouts/admin.blade.php
  - resources/views/employees/index.blade.php
  - resources/js/employees-index.js (nuevo)
  - tests/Feature/CicloControllerTest.php (nuevo o existente)
  - tests/Feature/CicloActualServiceTest.php (nuevo)
  - tests/Feature/EmployeeIndexTest.php (nuevo)

Forbidden files:
  - database/migrations/*
  - config/auth.php
  - config/database.php
  - app/Models/Employee.php (salvo accessors simples)
  - app/Models/Academia/Ciclo.php
  - resources/views/layouts/*.blade.php (excepto admin.blade.php header)
  - app/Http/Controllers/Academia/*Controller.php (excepto CicloController.php)
  - .env

Agents involved:
  - laravel → CicloController fix, CicloActualService unificación, EmployeeController variables + tests
  - frontend → employees/index.blade.php refactor, extraer JS a employees-index.js, mejoras UX
  - tester → tests nivel 3: CicloController (empty state, excepción), CicloActualService (resolve vs current), EmployeeIndex (filtros, paginación, AJAX)
  - security → revisar CicloActualService (manejo sesión, no auth bypass), EmployeeController (exposición datos empleados)
  - reviewer → revisar consistencia arquitectura, naming, patrones

Expected outputs:
  - CicloController::index() maneja excepción y muestra empty state correctamente
  - CicloActualService tiene un solo método público getCurrent() con comportamiento consistente (URL→sesión→default, guarda sesión si URL param)
  - Header usa getCurrent() y refleja selección inmediatamente
  - EmployeeController::index() pasa cargos/departamentos/sedes (distinct, limitados)
  - employees/index.blade.php: JS extraído a assets, sin duplicación Blade/JS, filtros funcionales
  - Tests pasando: CicloController (2), CicloActualService (3), EmployeeIndex (3+)

Requires security: sí (severidad LOW-MEDIUM)
  - CicloActualService maneja sesión de usuario - revisar que no haya session fixation o bypass
  - EmployeeController expone datos de empleados - revisar autorización (ya tiene middleware auth/admin)

Requires human approval: no
  - No toca auth, pagos, migraciones, ni rutas sensibles per se
  - Si security encuentra MEDIUM/CRITICAL → escalar