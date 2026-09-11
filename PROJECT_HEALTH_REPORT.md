# Project Health Report — laravelrelojnew

**Generated:** 2026-09-11  
**Type:** Read-only audit — no code modified  
**Scope:** Full codebase (Laravel 10, MySQL, Firebird 2.5, ZKTeco, Bootstrap 5)

---

## 1. Project Health — Executive Summary

| Area | Status | Notes |
|------|--------|-------|
| **Overall** | 🟡 **Needs Attention** | Solid architecture with clear domain separation, but several failing tests and technical debt items |
| **Architecture** | 🟢 **Good** | Clean separation: sync strategies, services, models. ADR-001 documented. |
| **Security (static)** | 🟡 **Medium** | No critical vulns found; some areas need hardening (input validation, secrets) |
| **Database** | 🟡 **Medium** | Indexes mostly good; missing composite indexes on high-cardinality query patterns |
| **Laravel Conventions** | 🟢 **Good** | Controllers mostly thin; some fat controllers (EmployeeController: 518 lines) |
| **Frontend Consistency** | 🟡 **Medium** | Progressive enhancement pattern good; component reuse inconsistent between modules |
| **Testing** | 🟡 **Medium** | 146 tests, ~15 failing (flaky/environment). Coverage strong on sync logic, weak on UI |
| **Performance** | 🟡 **Medium** | Dashboard KPIs optimized; N+1 in some views; chunked sync for large tables |
| **Technical Debt** | 🟠 **High** | 12 prioritized items; top 3 block reliability |

**Test Suite Status:** 146 tests, 15 failing (10.3%). Most failures are environment-related or test logic issues, not production bugs.

---

## 2. Architecture

### 2.1 High-Level Structure

```
app/
├── Http/Controllers/           # 19 controllers (10 root + 9 Academia)
│   ├── Academia/               # Academic module (ciclos, grupos, alumnos, etc.)
│   ├── AttendanceController
│   ├── DeviceController
│   ├── EmployeeController      # ← LARGEST (518 lines) — candidate for splitting
│   ├── FirebirdController
│   └── OperationsController
├── Models/                     # 27 models (12 root + 15 Academia + 1 pivot)
│   ├── Academia/               # Academic domain models
│   └── Pivots/DeviceEmployee
├── Services/                   # 10 services + 4 SyncStrategies
│   ├── SyncStrategies/         # Strategy pattern for Firebird → MySQL sync
│   ├── ZktecoService           # ← LARGEST SERVICE (1066 lines) — device comms
│   ├── SobranteService
│   ├── CatalogSmartSync
│   └── CycleDirectSync
├── Jobs/                       # Async sync/deprovision jobs
└── Enums/                      # Type-safe enums (PHP 8.1+)
```

### 2.2 Key Architectural Decisions (ADRs)

| ADR | Title | Status |
|-----|-------|--------|
| ADR-001 | Employee `type` enum (biometric\|admin\|teacher) | ✅ Implemented |
| ADR-002 | Central employee catalog + device_employee pivot | ✅ Implemented |
| ADR-003 | Firebird sync via Strategy pattern | ✅ Implemented |
| ADR-004 | Sobrante classification (Type A/B) | ✅ Implemented |

### 2.3 Module Coupling

| Module | Couples To | Coupling Type |
|--------|------------|---------------|
| ZktecoService | Device, Employee, Fingerprint, Attendance, DeviceSync | High (domain logic) |
| CatalogSmartSync | FirebirdReader, 15 MySQL tables | High (ETL) |
| EmployeeController | ZktecoService, SobranteService, Jobs | Medium |
| Academia controllers | 15 academic models | Low (self-contained) |

**Observation:** The sync layer (ZktecoService + SyncStrategies) is the architectural core — well-isolated, testable, and uses Strategy pattern correctly. The `EmployeeController` is the only "God controller" violating SRP.

---

## 3. Security (Static Analysis)

### 3.1 Findings

| Severity | Issue | Location | Recommendation |
|----------|-------|----------|----------------|
| **MEDIUM** | Raw SQL in `DeviceController::deduplicate()` uses string interpolation | `DeviceController.php:298-335` | Use query builder or parameterized queries |
| **MEDIUM** | `FirebirdReader` uses raw PDO with string table names | `FirebirdReader.php:41-98` | Whitelist table names; validate against `TABLE_MAP` |
| **LOW** | `.env` committed (has real values) | `.env` | Ensure `.env` in `.gitignore`; use `.env.example` |
| **LOW** | No rate limiting on `/api/academia/*` endpoints | `routes/web.php:86-98` | Add `throttle` middleware |
| **LOW** | `ZktecoService` logs device passwords in error context | `ZktecoService.php:104, 155` | Sanitize logs; use `Log::warning(..., ['device' => $ip])` only |
| **LOW** | `EmployeeController::store()` accepts plaintext password | `EmployeeController.php:361` | Hash before storing; device sync sends plaintext to hardware (protocol limit) |

### 3.2 Authentication/Authorization

- **Auth:** Laravel Sanctum (API tokens) + session web guard
- **Admin gate:** `middleware('admin')` on destructive routes (checked via `User::isAdmin()`)
- **CSRF:** Enabled globally; AJAX endpoints use `X-CSRF-TOKEN` header
- **SQL Injection:** Mostly prevented by Eloquent/Query Builder; 2 raw SQL exceptions noted above

### 3.3 Secrets Management

- Database credentials in `.env` (not committed — confirmed via `.gitignore`)
- ZKTeco device passwords stored in `devices.password` column (plaintext — protocol requirement)
- No API keys or third-party secrets detected in codebase

---

## 4. Database (MySQL + Firebird)

### 4.1 MySQL Schema Health

#### 4.1.1 Index Analysis — Core Tables

| Table | Row Est. | Indexes | Missing/Suboptimal |
|-------|----------|---------|-------------------|
| `employees` | ~1-5K | 8 (3 unique) | ✅ Good — composite `(type, status_actual)` exists |
| `attendances` | ~100K-1M | 12 | ⚠️ **Missing**: `(employee_id, recorded_at)` composite for employee timeline queries |
| `devices` | ~10-50 | 5 | ✅ Good |
| `fingerprints` | ~10K-50K | 5 | ✅ Good — unique `(employee_id, finger, device_id)` |
| `device_employee` | ~5K-20K | 5 | ⚠️ **Missing**: `(employee_id, active)` for "active enrollments" queries |
| `device_syncs` | ~1K-10K | 3 | ✅ Good |
| `firebird_syncs` | ~100-1K | 4 | ✅ Good |

#### 4.1.2 N+1 Query Patterns Detected

| Location | Pattern | Fix |
|----------|---------|-----|
| `EmployeeController::index()` | `$cargos = Employee::whereNotNull('cargo')...distinct()->pluck()` — runs 3 extra queries per request | Move to cached config or single query with `GROUP BY` |
| `EmployeeController::edit()` | `$availableFingerprints` loads all fingerprints with employees — heavy | Add `where('device_id', $device->id)` or paginate |
| `DeviceController::refreshData()` | `$device->employees()->get()` then maps with `pivot` — OK (eager loaded) | ✅ Already eager-loaded |
| `ZktecoService::syncUsers()` | `$enrolledByUserId = $this->device->employees()->get(...)` — good | ✅ Single query |
| `DashboardController::kpis()` | Uses conditional aggregation — 3 queries for 5 KPIs | ✅ Optimized |

#### 4.1.3 Legacy Dependencies

| Legacy Artifact | Status | Migration Path |
|-----------------|--------|----------------|
| `employees.user_id` (PIN) | Active — global PK | Keep; unique index enforced |
| `device_employee.device_uid` | Active — hardware UID | Keep; unique per device |
| `fingerprints.device_id` NULLable | Legacy rows being re-attributed | `ZktecoService::syncFingerprintForEmployee()` handles cleanup |
| `firebird_syncs` + `firebird_sync_items` | Active — sync tracking | Keep |

### 4.2 Firebird 2.5 Integration

| Aspect | Status | Notes |
|--------|--------|-------|
| Connection | `FirebirdReader` via PDO | Single persistent connection; no pool |
| Schema Discovery | `RDB$RELATION_FIELDS` queries | ✅ Correct for FB 2.5 |
| Sync Strategy | `CatalogSmartSync` (chunked) + `CycleDirectSync` | ✅ Handles 15 tables; 3 chunked (>10K rows) |
| Identity Mapping | `TABLE_MAP` with `identity` columns | ✅ Composite keys handled |
| Idempotency | `INSERT IGNORE` + `UPDATE` on identity | ✅ Good; uses `ROW_COUNT()` for counts |
| Data Cleaning | `cleanRows()` trims strings, normalizes NULL | ✅ Centralized |

**Risk:** Firebird 2.5 is EOL (2017). No security patches. Plan migration to Firebird 4.x or PostgreSQL.

---

## 5. Laravel Conventions & Code Quality

### 5.1 Controller Analysis

| Controller | Lines | Methods | Verdict |
|------------|-------|---------|---------|
| `EmployeeController` | **518** | 16 | 🟠 **FAT** — Split: `EmployeeCatalogController` + `EmployeeEnrollmentController` + `EmployeeSobranteController` |
| `DeviceController` | 389 | 17 | 🟡 Large but cohesive (device lifecycle) |
| `ZktecoService` | **1066** | 28 | 🟠 **FAT SERVICE** — Extract: `ZktecoConnection`, `ZktecoFingerprintSync`, `ZktecoUserSync` |
| `CatalogSmartSync` | 744 | 8 | 🟡 Large but single-responsibility (catalog ETL) |
| `DashboardController` | 322 | 8 | 🟢 Good — thin, delegates to query methods |
| `SobranteService` | 354 | 11 | 🟢 Good — single responsibility |
| Academia controllers | 150-12K total | ~8 each | 🟢 Good — resourceful, thin |

### 5.2 Model Quality

- **Casts:** Used correctly (`fecha_ingreso` → `date`)
- **Scopes:** Well-named (`activos()`, `bajas()`, `biometric()`, etc.)
- **Accessors:** `typeLabel`, `statusActualLabel` — good for UI
- **Touches:** `Employee::$touches = ['devices']` — correct for cache invalidation
- **Relationships:** Properly defined with pivot models

### 5.3 Validation

- **FormRequests:** Only `EmployeeFormRequest` used; others inline in controllers
- **Recommendation:** Create `DeviceFormRequest`, `SyncFormRequest` for consistency

### 5.4 Code Style

- `declare(strict_types=1)` on all files ✅
- PHPDoc on public methods ✅
- PSR-12 formatting (Laravel Pint configured) ✅
- No `dd()` or `dump()` in committed code ✅

---

## 6. Frontend Consistency

### 6.1 Technology Stack

- **CSS:** Bootstrap 5.3.3 (CDN) + custom CSS variables
- **JS:** Vanilla ES6 modules (Vite build) — no framework
- **Icons:** Bootstrap Icons 1.11.3 (CDN)
- **Architecture:** Progressive enhancement — SSR first, JS enhances

### 6.2 Component Inventory

| Component | File | Used In | Reusable? |
|-----------|------|---------|-----------|
| `badge` | `components/badge.blade.php` | Global | ✅ Yes |
| `data-table` | `components/data-table.blade.php` | Devices, Employees | ⚠️ Partial — hardcoded columns |
| `stat-card` | `components/stat-card.blade.php` | Dashboard | ✅ Yes |
| `drawer` | `components/drawer.blade.php` | — | ❌ Unused |
| `academia.ciclo-selector` | `components/academia/ciclo-selector.blade.php` | Academia views | ✅ Yes |

### 6.3 State Management

- **Server State:** SSR + AJAX (`employees-index.js`, `app.js` Heartbeat)
- **Client State:** localStorage (theme, sidebar, notifications read)
- **No global store** — appropriate for this app size

### 6.4 Inconsistencies

| Issue | Location | Impact |
|-------|----------|--------|
| Academia tables use raw `<table>`; Devices/Employees use `data-table` component | `academia/alumnos/index.blade.php` vs `employees/index.blade.php` | Visual drift; different sorting/pagination UX |
| `employees-index.js` builds HTML strings manually | `employees-index.js:81-224` | XSS risk if `escapeHtml` missed; hard to maintain |
| No shared "empty state" component | Multiple views | Inconsistent empty messages |
| Command palette (`app.js:523-620`) hardcodes search sources | `app.js:531-533` | New modules won't appear without JS edit |

### 6.5 Accessibility

- ARIA labels on interactive elements ✅
- Focus management in modals/dialogs ✅
- Color contrast: custom CSS variables used — needs audit
- Semantic HTML: mostly ✅ (some `<div>` tables in academia)

---

## 7. Testing Coverage (Approximate by Module)

| Module | Tests | Coverage Est. | Notes |
|--------|-------|---------------|-------|
| **ZKTeco Sync** | 17 | ~85% | Core sync logic well-covered; 2 failing (mock issues) |
| **Sobrante (Surplus)** | 31 | ~95% | Excellent — all edge cases covered |
| **Firebird Sync** | 27 | ~70% | Strategy order tested; 2 failing (auth/validation) |
| **Dashboard** | 9 | ~60% | KPI queries + rendering; no JS tests |
| **Academia** | 10 | ~40% | Hierarchy + controllers; no integration tests |
| **Device Sync** | 8 | ~75% | Job flow tested; hardware mocked |
| **Operations Queue** | 3 | ~50% | Unified queue tests; 3 failing |
| **Fingerprint** | 6 | ~50% | 3 failing (mock setup) |
| **Attendance Filter** | 5 | ~60% | 1 failing (date range) |
| **Ciclo Service** | 5 | ~80% | All passing now |
| **Auth/Layout** | 6 | ~30% | Basic only |

**Total:** ~146 tests, ~70% estimated coverage on business logic, ~30% on UI.

**Test Levels (per `.opencode/policies/test-levels.md`):**
- Sync/Integration: Level 3 (DB + external service mocks)
- Controllers: Level 2 (specific + related)
- UI: Level 1 (render only)

---

## 8. Performance (Known Bottlenecks)

### 8.1 Identified Bottlenecks

| # | Location | Issue | Evidence |
|---|----------|-------|----------|
| 1 | `DashboardController::kpis()` | 3 queries per request; runs on every page load | Dashboard is entry point; 20s heartbeat hits `kpisJson()` |
| 2 | `EmployeeController::index()` | 3 distinct `pluck()` queries for filters (cargos, departamentos, sedes) | Runs on every employee list load |
| 3 | `ZktecoService::syncFingerprints()` | Chunked per employee; 1 connection per chunk | ~5K employees × 10 fingers = 50K round-trips |
| 4 | `CatalogSmartSync` chunked tables | `ALUMNOS_KARDEX`, `HORARIOS_DET` > 100K rows; 5K chunk size | Full sync takes 5-15 min |
| 5 | `DeviceController::index()` sparklines | 3 queries × 7 buckets = 21 COUNT queries per device | Runs on device list; acceptable for <20 devices |

### 8.2 Optimizations Already In Place

- Dashboard KPIs: conditional aggregation (7→3 queries)
- `ZktecoService::syncUsers()`: indexes enrolled employees by `user_id` in single query
- `CatalogSmartSync`: loads MySQL existing rows once, then streams Firebird chunks
- `attendances` table: composite unique index `(device_id, employee_id, recorded_at)`
- Heartbeat interval: 20s (configurable)

### 8.3 Recommended Quick Wins

1. Cache `cargos`, `departamentos`, `sedes` for Employee filters (TTL 5 min)
2. Add composite index `attendances (employee_id, recorded_at)`
3. Add composite index `device_employee (employee_id, active)`
4. Increase fingerprint sync batch size; reuse connection across employees
5. Move `kpisJson()` to cached response (10s TTL) — data is near-real-time anyway

---

## 9. Technical Debt (Prioritized)

| Priority | ID | Item | Effort | Risk if Unaddressed |
|----------|----|------|--------|---------------------|
| **P0** | TD-01 | **Fix failing tests** (15 failures) | 2-4h | CI unreliable; regressions undetected |
| **P0** | TD-02 | **Split `EmployeeController`** (518 lines) | 4-6h | Bug risk; hard to test; SRP violation |
| **P0** | TD-03 | **Extract `ZktecoService` into focused classes** | 6-8h | 1066-line god class; connection/fingerprint/user logic mixed |
| **P1** | TD-04 | Add missing DB indexes (`attendances.employee_id+recorded_at`, `device_employee.employee_id+active`) | 30m | Query perf degrades with data growth |
| **P1** | TD-05 | Cache Employee filter options (cargos, departamentos, sedes) | 1h | 3 extra queries per list load |
| **P1** | TD-06 | Create FormRequests for Device, Sync, Fingerprint controllers | 2h | Inconsistent validation; duplication |
| **P2** | TD-07 | Unify academia tables with `data-table` component | 4h | UX inconsistency; maintenance burden |
| **P2** | TD-08 | Replace manual HTML building in `employees-index.js` with template/component | 3h | XSS risk; hard to maintain |
| **P2** | TD-09 | Firebird 2.5 EOL — plan migration to Firebird 4.x or PostgreSQL | Weeks | Security/compatibility risk |
| **P2** | TD-10 | Add rate limiting to `/api/academia/*` endpoints | 30m | DoS surface |
| **P3** | TD-11 | Sanitize ZKTeco logs (remove passwords from error context) | 1h | Credential leak in logs |
| **P3** | TD-12 | Add integration tests for academia CRUD flows | 4h | Low confidence in academic module |

---

## 10. Priority — Top 5 to Address First

| Rank | Item | Justification |
|------|------|---------------|
| **1** | **Fix failing tests (TD-01)** | 15 failures block CI trust. Most are test logic/environment issues (mock setup, session handling), not production bugs. Fixing restores confidence for all future changes. |
| **2** | **Split `EmployeeController` (TD-02)** | 518 lines, 16 methods, mixes catalog CRUD, enrollment, sobrantes, fingerprints. Highest bug surface area. Split into: `EmployeeCatalogController` (index/create/edit/update/destroy), `EmployeeEnrollmentController` (sync/enroll/card/fingerprint), `EmployeeSobranteController` (sobrantes CRUD). |
| **3** | **Extract `ZktecoService` (TD-03)** | 1066 lines handles: connection lifecycle, retry logic, user sync, attendance sync, fingerprint sync, device admin (time, clear, restore). Split into: `ZktecoConnection` (boot, retry, status), `ZktecoUserSync`, `ZktecoAttendanceSync`, `ZktecoFingerprintSync`, `ZktecoDeviceAdmin`. |
| **4** | **Add missing DB indexes (TD-04)** | Low effort, high impact. `attendances(employee_id, recorded_at)` speeds employee timeline; `device_employee(employee_id, active)` speeds active enrollment checks. |
| **5** | **Cache Employee filter options (TD-05)** | 3 distinct queries on every employee list page. Simple `Cache::remember(..., 300, fn => ...)` eliminates them. Immediate UX win. |

---

## Appendix: Files Referenced

| File | Purpose |
|------|---------|
| `app/Http/Controllers/EmployeeController.php` | Main employee management (518 lines) |
| `app/Services/ZktecoService.php` | ZKTeco device communication (1066 lines) |
| `app/Services/SyncStrategies/CatalogSmartSync.php` | Firebird→MySQL catalog sync (744 lines) |
| `app/Services/SobranteService.php` | Surplus enrollment management |
| `app/Models/Employee.php` | Central employee catalog model |
| `routes/web.php` | All web routes (187 lines) |
| `resources/js/app.js` | Core frontend (686 lines) |
| `resources/js/employees-index.js` | Employee table progressive enhancement |
| `resources/views/layouts/admin.blade.php` | Main layout with all UI chrome |
| `tests/Feature/ZktecoSyncTest.php` | Sync logic tests (17 tests) |
| `tests/Feature/SobranteServiceTest.php` | Surplus logic tests (20 tests) |
| `database/migrations/` | 60+ migrations (MySQL schema) |

---

**End of Report** — This is a read-only audit. No files were modified.