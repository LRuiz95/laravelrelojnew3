# 09_TESTS.md — Registro de Pruebas

## Configuracion Testing

### phpunit.xml
- BD: MySQL (rh_reloj_testing)
- migrate:fresh en cada proceso
- Queue: sync
- Cache/Session: array
- Mail: array

### Test Suites
- Unit: tests/Unit
- Feature: tests/Feature

---

## Tests Existentes (5 archivos)

### tests/Feature/DashboardRenderTest.php (116 lineas)
**Patron**: seedRenderData() siembra datos minimos para ejercitar eager loads
**Tests**:
- test_pages_render_for_authenticated_user: dashboard, devices, employees, attendances
- test_dashboard_trend_ranges_render: 4 rangos (hoy, 7d, 30d, 12m) + invalido
- test_kpis_json_endpoint_returns_five_cards: /kpis/json estructura + labels exactos
- test_guest_is_redirected: / -> redirect /login
- test_authenticated_user_from_login_redirects_to_root: /login -> redirect /

### tests/Feature/ZktecoSyncTest.php (512 lineas)
**Cobertura**: Sync logic, duplicados, conexion, indices, jobs
**Tests**:
- test_users_sync_without_duplicates: syncUsers idempotente
- test_same_user_id_from_different_devices_is_not_duplicated: user_id global unico
- test_offline_device_raises_a_connection_exception: ZktecoConnectionException
- test_employee_can_be_created_in_database: CRUD basico + pivot
- test_employee_sync_creates_one_full_task_per_selected_device_without_duplicates: syncToDevices
- test_catalog_pin_is_globally_unique: user_id unique constraint
- test_card_number_can_repeat_across_devices_but_not_within_one: pivot unique [device_id, card_number]
- test_partial_sync_data_is_not_marked_as_failed_when_records_were_saved: Job::failed logic
- test_full_sync_uses_the_service_stage_flow_for_users_assistances_and_fingerprints: runAllSync stages
- test_full_sync_progress_is_capped_and_not_over_100_percent: SyncProgressUpdated progress calc
- test_device_serial_number_cannot_be_repeated: serial_number unique
- test_fingerprint_is_unique_per_device_employee_and_finger: unique [device_id, employee_id, finger]
- test_syncing_fingerprints_reattributes_legacy_null_origin_rows: re-atribucion legacy NULL
- test_centralization_command_is_idempotent_on_an_already_migrated_database: migrate:employees-to-central
- test_attendance_labels_derive_from_punch_type: stateLabel/shortStateLabel/stateColorClass desde type
- test_successful_connection_marks_device_online: boot() -> online
- test_failed_ping_marks_device_offline_and_raises: boot() -> offline + exception

### tests/Feature/AttendanceFilterTest.php (124 lineas)
**Regresion**: AttendanceController::index() ignoraba filtros y usaba state en vez de type
**Tests**:
- test_state_filter_matches_punch_type: filtro state=0 -> type=0 (Entrada)
- test_device_filter: filtro device_id
- test_date_range_filter: from/to range
- test_combined_filters: state + date range
- test_no_filters_shows_unique_list: sin filtros usa uniqueAttendances()

### tests/Feature/ExampleTest.php
- test_the_application_returns_a_successful_response: GET / -> 200

### tests/Unit/ExampleTest.php
- test_true_is_true: true === true

---

## Ejecucion de Tests

`ash
# Todos los tests
php artisan test

# Solo Feature
php artisan test --testsuite=Feature

# Solo Unit
php artisan test --testsuite=Unit

# Con coverage
php artisan test --coverage
`

---

## Resultados Esperados (Pre-Auditoria)

| Test Suite | Tests | Assertions | Tiempo Estimado |
|------------|-------|------------|-----------------|
| Feature | ~25 | ~80 | 30-60s |
| Unit | 1 | 1 | <1s |
| **Total** | **~26** | **~81** | **~60s** |

---

## Tests Pendientes de Agregar (Post-Fixes)

### Para BUG-001, 002 (Rate Limiting)
- test_login_rate_limiting: 6 requests -> 429
- test_sync_endpoints_rate_limiting: 11 requests -> 429

### Para BUG-004 (Indice Unico Attendances)
- test_attendance_unique_employee_recorded_device: sync duplicado -> 1 registro

### Para BUG-005 (Fingerprint device_id)
- test_fingerprint_legacy_device_id_null_handled: legacy rows no duplican

### Para BUG-006 (Device Store Async)
- test_device_store_returns_immediately: response < 1s con IP invalida
- test_device_connection_job_updates_status: job async actualiza status

### Para BUG-007 (Employee Update Async)
- test_employee_update_only_catalog: update() no llama ZktecoService
- test_employee_sync_credentials_job_dispatched: boton sync dispara job

### Para BUG-008 (Employee Destroy Soft)
- test_employee_destroy_marks_status_b: destroy -> status_actual=B
- test_employee_deprovision_jobs_dispatched: jobs en cola por dispositivo

### Para BUG-010 (Employee Search API)
- test_employee_search_api_returns_json: /api/employees/search?q=... -> JSON
- test_employee_search_loading_state: spinner durante fetch

### Para BUG-011 (Charts Theme)
- test_charts_update_on_theme_change: themechange -> chart.update() llamado

### Para BUG-013 (DeviceEmployee casts)
- test_device_employee_uses_casts_property: model:show muestra casts property

### Para BUG-015 (Role Enum)
- test_user_role_enum_validation: INSERT role invalido -> constraint violation

---

## Comandos Utiles

`ash
# Ver tests disponibles
php artisan test --list-tests

# Ejecutar test especifico
php artisan test --filter=test_users_sync_without_duplicates

# Ejecutar con verbose
php artisan test -v

# Parar en primer fallo
php artisan test --stop-on-failure

# Parallel (si configurado)
php artisan test --parallel
`

---

## Notas de Testing

1. **MySQL Requerido**: Tests usan rh_reloj_testing, NO SQLite
2. **RefreshDatabase**: Cada test usa migrate:fresh (BD limpia)
3. **Seed Obligatorio**: DashboardRenderTest::seedRenderData() patron requerido
4. **Mockery**: ZKTeco mockeado en ZktecoSyncTest
5. **Queue Fake**: Queue::fake() para testear jobs despachados
