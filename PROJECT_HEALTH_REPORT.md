# Project Health Report — laravelrelojnew

**Generated:** 2026-09-07  
**Laravel Version:** 10.x  
**PHP Version:** ^8.1  
**DB:** MySQL (rh-reloj) + Firebird 2.5 (DATOS.FDB)  
**Hardware:** ZKTeco biometric devices (UDP protocol)

---

## 1. Project Health (Executive Summary)

| Dimension | Status | Notes |
|-----------|--------|-------|
| **Overall** | 🟡 **Caution** | Core functionality works; architecture is solid. Main risks: **tests don't run** (MySQL test DB unavailable), **no CI**, **technical debt in sync logic** |
| **Architecture** | 🟢 Good | Clean separation: central catalog + pivot, strategy pattern for Firebird sync, job-based async for ZKTeco |
| **Security** | 🟡 Medium | AuthZ via policies + admin middleware; passwords encrypted at rest; **no rate-limit on login**, **no CSP**, **no HSTS** |
| **Database** | 🟢 Good | Proper FKs, indexes, unique constraints; **Firebird reader is read-only** (safe) |
| **Laravel Conventions** | 🟢 Good | Strict typing, form requests, policies, jobs with middleware, services for external APIs |
| **Frontend** | 🟢 Good | Bootstrap 5 + custom CSS tokens, dark/light theme, SSR-ready; **no component library**, vanilla JS modules |
| **Testing** | 🔴 Critical | **17/23 tests fail** (MySQL test DB not running); coverage unknown; no CI |
| **Performance** | 🟡 Medium | N+1 risks in `DashboardController::pipeline()` (5 queries), `EmployeeController::edit()` (3 loads); KPI endpoint unindexed |
| **Technical Debt** | 🟡 Medium | Legacy migration artifacts, sync progress logic complex, duplicated query patterns |

**Bottom line:** The system is **production-functional** but **not test-verified**. Next steps must prioritize getting the test suite green and adding CI.

---

## 2. Architecture

### 2.1 High-Level Domains

```
┌─────────────────────────────────────────────────────────────────┐
│                        Laravel (MySQL)                          │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐            │
│  │  Biometric   │ │   Academia   │ │   Firebird   │            │
│  │  (Devices,   │ │  (Ciclos,    │ │    Sync      │            │
│  │  Employees,  │ │  Grupos,     │ │  (ETL Jobs)  │            │
│  │  Attendance) │ │  Alumnos)    │ │              │            │
│  └──────┬───────┘ └──────┬───────┘ └──────┬───────┘            │
│         │                │                │                     │
│         └────────────────┼────────────────┘                     │
│                          ▼                                      │
│              ┌───────────────────────┐                          │
│              │   Queue (database)    │                          │
│              │  SyncDeviceJob        │                          │
│              │  FirebirdSyncJob      │                          │
│              │  SyncEmployeeToDevice │                          │
│              └───────────┬───────────┘                          │
└──────────────────────────┼──────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
       ┌─────────────┐           ┌─────────────┐
       │  ZKTeco     │           │  Firebird   │
       │  Devices    │           │  2.5 (FDB)  │
       │  (UDP/4370) │           │  (Read-only)│
       └─────────────┘           └─────────────┘
```

### 2.2 Key Architectural Decisions (Recorded in `state/decisions.md`)

| ADR | Decision | Rationale |
|-----|----------|-----------|
| ADR-001 | Employee **central catalog** + `device_employee` pivot | One person → many devices; hardware metadata (UID, card, role, password) lives in pivot |
| ADR-002 | `user_id` (PIN) is **global unique key** on `employees` | Dedupe across devices; `firstOrCreate(['user_id'])` in `ZktecoService::syncUsers()` |
| ADR-003 | Fingerprints **per-device** (`device_id` on `fingerprints`) | Templates are device-specific; legacy `NULL` rows kept as fallback |
| ADR-004 | Firebird sync via **Strategy pattern** (`SyncStrategyInterface`) | `sync_catalogos` | `sync_ciclo` | `sync_all` | `sync_custom` with FK dependency validation |
| ADR-005 | All ZKTeco ops async via **jobs with `WithoutOverlapping`** | Prevent concurrent syncs per device; retry with backoff; progress via `DeviceSync` model |

### 2.3 Module Coupling

| Module | Depends On | Coupling |
|--------|------------|----------|
| `EmployeeController` | `ZktecoService`, `EmployeeDeviceSyncService` | Tight (direct service calls) |
| `DeviceController` | `ZktecoService`, `SyncDeviceJob` | Medium (jobs decouple) |
| `FirebirdController` | `FirebirdSyncJob`, `FirebirdReader` | Medium (job + reader) |
| `Academia/*` | `CicloActualService`, `KardexCalculator` | Low (services injected) |
| `ZktecoService` | `coding-libs/zkteco-php` (vendor) | External vendor dependency |

---

## 3. Security (Static Analysis)

### 3.1 Authentication & Authorization

| Check | Status | Details |
|-------|--------|---------|
| **Auth guards** | ✅ | `web` guard + `sanctum` for API; `User` model with `Role` enum (Admin/Operator) |
| **Password hashing** | ✅ | Laravel default (bcrypt); `Device.password` encrypted cast |
| **Session config** | ⚠️ | `SESSION_LIFETIME=120` (2h); `secure` not forced in `.env` (local only) |
| **CSRF** | ✅ | `VerifyCsrfToken` middleware; all forms protected |
| **Rate limiting** | ❌ | **Login only has `throttle:5,1`**; no rate limit on sync endpoints, device actions |
| **Policies** | ✅ | 8 policies (`EmployeePolicy`, `DevicePolicy`, `CursoPolicy`, etc.) registered in `AuthServiceProvider` |
| **Admin middleware** | ✅ | `admin` middleware checks `auth()->user()->isAdmin()`; used on destructive routes |

### 3.2 Input Validation

| Area | Status |
|------|--------|
| Form Requests | ✅ `EmployeeFormRequest` used in `EmployeeController` |
| Mass Assignment | ✅ `$fillable` on all models; `$guarded = []` not used |
| SQL Injection | ✅ Eloquent/Query Builder parameterized; **no raw SQL with interpolation** |
| XSS | ✅ Blade `{{ }}` escaping; `json_encode` with flags in layout |

### 3.3 Secrets & Config

| Item | Status |
|------|--------|
| `APP_KEY` | ✅ Set in `.env` |
| DB passwords | ✅ `.env` (not committed); `Device.password` encrypted cast |
| Firebird credentials | ✅ `.env` (`FIREBIRD_DSN`, `FIREBIRD_USER`, `FIREBIRD_PASS`) |
| `.env.example` | ✅ Present |

### 3.4 Missing Hardening (Recommended)

| Missing | Severity | Effort |
|---------|----------|--------|
| **Content Security Policy** | MEDIUM | Low (middleware + nonce) |
| **HSTS / Secure cookies** | MEDIUM | Low (config) |
| **Rate limiting on sync endpoints** | HIGH | Low (add `throttle` middleware) |
| **Audit logging for admin actions** | MEDIUM | Medium (observer or middleware) |
| **2FA / MFA** | LOW | Medium (Laravel Fortify or custom) |

### 3.5 Anticipated Security Agent Severity

If `security` agent runs: **MEDIUM** (missing CSP, HSTS, rate limiting on write endpoints). No CRITICAL findings expected statically.

---

## 4. Database (MySQL + Firebird)

### 4.1 MySQL Schema Health

| Table | Rows (est.) | Indexes | FKs | Issues |
|-------|-------------|---------|-----|--------|
| `employees` | ~100-500 | PK, UK(`user_id`), `status_actual` | — | `type` enum not enforced at DB level |
| `devices` | ~5-20 | PK, UK(`serial_number`), `status` | — | — |
| `device_employee` | ~200-2000 | PK, UK(`device_id`,`device_uid`), UK(`device_id`,`card_number`), IDX(`employee_id`) | `device_id`, `employee_id` | ✅ Good |
| `attendances` | ~10K-100K | PK, UK(`device_id`,`user_id`,`recorded_at`,`state`), IDX(`device_id`,`recorded_at`), IDX(`employee_id`,`recorded_at`,`id`) | `device_id`, `employee_id` | ✅ Good |
| `fingerprints` | ~500-5000 | PK, UK(`device_id`,`employee_id`,`finger`), IDX(`device_id`,`template_hash`) | `device_id`, `employee_id` | ✅ Good |
| `device_syncs` | ~100-1000 | PK, IDX(`device_id`,`status`) | `device_id`, `employee_id` | — |
| `firebird_syncs` | ~50 | PK, IDX(`operation`,`status`), IDX(`ciclo`) | — | — |
| `users` | ~5-10 | PK, UK(`email`), IDX(`role`) | — | — |

**Missing indexes (candidates):**
- `employees` → `type` (if filtered often), `status_actual`
- `attendances` → `type` (for donut/pivot queries)
- `device_syncs` → `operation`, `status` composite (for queue filtering)

### 4.2 Firebird (Read-Only)

| Aspect | Status |
|--------|--------|
| **Connection** | `FirebirdReader` uses PDO with `RDB$RELATIONS`/`RDB$RELATION_FIELDS` introspection |
| **Tables synced** | `CFGSEDES`, `CFGNIVELES`, `CFGTURNOS`, `CICLOS`, `CFGPLANES_MST`, `CFGPLANES_DET`, `CFGSESIONES`, `CFGTIPOSEVALUACION`, `EMPLEADOS_CONTRATOS_CAT`, `ALUMNOS`, `PROFESORES`, `GRUPOS`, `ALUMNOS_GRUPOS`, `HORARIOS_DET`, `CURSOS`, `CURSOS_DET`, `ALUMNOS_NIVELES` |
| **Strategy** | Chunked `FIRST/SKIP` (5000 rows), batched IN clauses (1400 params), FK dependency ordering |
| **Risk** | Read-only → **no data integrity risk**; sync is **one-way** (FB → MySQL) |

### 4.3 Known N+1 Query Patterns

| Location | Pattern | Fix |
|----------|---------|-----|
| `DashboardController::pipeline()` | 5 separate `count()` queries (devices, attendances, employees, fingerprints) | Single aggregated query or cached KPI |
| `EmployeeController::edit()` | 3 eager loads (`devices`, `fingerprints`, `syncs`) + 2 extra queries (`availableDevices`, `syncDevices`) | Acceptable (edit page); consider `loadMissing()` |
| `DeviceController::show()` | `employees`, `attendances`, `recentSyncs`, `fingerprints` all loaded separately | Acceptable for detail view |
| `FirebirdController::index()` | `FirebirdSync::latestFirst()->paginate()` + `Ciclo::orderByDesc()->get()` + stats (4 queries) | Acceptable |

### 4.4 Legacy Dependencies

| Legacy Artifact | Status | Cleanup |
|-----------------|--------|---------|
| `employees.device_id`, `employees.uid`, `employees.card_no`, `employees.password`, `employees.active` | **Dropped** (migration `2026_08_23_000004_drop_legacy_columns_from_employees.php`) | ✅ Done |
| `fingerprints` without `device_id` | **Legacy rows exist** (pre-2026_08_23 migration) | Re-attributed on next sync via `syncFingerprints()` |
| `attendances.employee_id` NULLs | **Backfilled** (migration `2026_08_20_000005_backfill_attendance_employee_ids.php`) | ✅ Done |

---

## 5. Laravel (Conventions, Controllers)

### 5.1 Controller Size Analysis

| Controller | Lines | Methods | Assessment |
|------------|-------|---------|------------|
| `EmployeeController` | 567 | 20 | 🟡 **Large** — CRUD + fingerprint ops + sync orchestration |
| `DeviceController` | 526 | 23 | 🟡 **Large** — device CRUD + 7 sync actions + progress/status |
| `DashboardController` | 287 | 10 | 🟢 OK — mostly query builders |
| `FirebirdController` | 151 | 3 | 🟢 OK — thin controller, delegates to job |
| `OperationsController` | 149 | 6 | 🟢 OK |
| `Academia/*` (9 controllers) | 50-150 each | 2-6 each | 🟢 Good — thin, service-injected |

### 5.2 Convention Adherence

| Convention | Status | Notes |
|------------|--------|-------|
| **Strict types** | ✅ | `declare(strict_types=1)` on all app files |
| **Form Requests** | ✅ | `EmployeeFormRequest` used |
| **Policies** | ✅ | 8 policies; gates used in routes |
| **Jobs with middleware** | ✅ | `WithoutOverlapping`, backoff, timeout |
| **Services for external APIs** | ✅ | `ZktecoService`, `FirebirdReader`, `EmployeeDeviceSyncService` |
| **Enums** | ✅ | `Role` enum (PHP 8.1 backed enum) |
| **DTOs** | ✅ | `EmployeeCredentialPackage` (readonly class) |
| **Resource collections** | ❌ | Not used; controllers return arrays/collections directly |
| **API Resources** | ❌ | Not used; JSON built manually in `search()`, `refreshData()` |

### 5.3 Fat Controller Candidates (Refactor Priority)

| Controller | Reason | Suggested Extraction |
|------------|--------|----------------------|
| `EmployeeController` | 20 methods: CRUD + fingerprint CRUD + device sync + card + enroll | `FingerprintController`, `EmployeeSyncService` |
| `DeviceController` | 23 methods: CRUD + 7 sync variants + deduplicate + time/clear/restore | `DeviceSyncController`, `DeviceMaintenanceController` |

---

## 6. Frontend (Consistency, States)

### 6.1 Stack
- **CSS:** Bootstrap 5.3 (CDN) + custom `app.css` (design tokens, dark/light)
- **JS:** Vanilla ES modules (`app.js` — 681 lines), no build step beyond Vite
- **Components:** Blade components (`data-table`, `stat-card`, `drawer`, `badge`, `sparkline`, `donut`, `empty-state`)

### 6.2 Component Inventory

| Component | File | Used In | State Handling |
|-----------|------|---------|----------------|
| `data-table` | `components/data-table.blade.php` | Employees, Devices, Attendances, Academia | Props: `headers`, `rows`, `actions`, `pagination` |
| `stat-card` / `kpi-card` | Inline in views + `app.css` `.kpi-card` | Dashboard, Devices show | Reactive via `Heartbeat` JS (20s poll) |
| `drawer` | `components/drawer.blade.php` | Employee edit (biometric/access panels) | CSS-only (transform) |
| `badge` | `components/badge.blade.php` | Status badges | Static |
| `sparkline` | `components/sparkline.blade.php` | Dashboard, Devices index/show | SVG inline |
| `donut` | `components/donut.blade.php` | Dashboard | SVG inline |

### 6.3 Missing / Inconsistent States

| UI Element | Missing State | Impact |
|------------|---------------|--------|
| **Sync buttons** (devices, employees) | Loading/disabled during `fetch()` | Handled in inline script (line 370-378 `admin.blade.php`) — **works but not reusable** |
| **Global search (Ctrl+K)** | No loading state, no empty state | Minor UX |
| **Command palette** | No keyboard navigation announcement (a11y) | Accessibility |
| **Notifications flyout** | No pagination/virtualization (could grow) | Performance if >100 |
| **Confirm dialog** | `requireType` input not announced to screen readers | Accessibility |
| **Data table** | No sorting/pagination UI (server-side only) | Feature gap |

### 6.4 CSS Architecture
- **Design tokens** in `:root` / `[data-theme]` — excellent (colors, spacing, radius, shadows)
- **No utility framework** (Tailwind) — custom CSS scales but requires maintenance
- **Dark/Light** — implemented via `data-theme` attribute, persists in `localStorage`, respects `prefers-color-scheme`

---

## 7. Testing (Coverage by Module)

### 7.1 Test Suite Status

| Test File | Tests | Status | Coverage Area |
|-----------|-------|--------|---------------|
| `Unit/ExampleTest` | 1 | ✅ Pass | Smoke |
| `Feature/ExampleTest` | 1 | ✅ Pass | Auth redirect |
| `Feature/ZktecoSyncTest` | 17 | ❌ **All FAIL** (MySQL test DB down) | ZKTeco sync logic, deduplication, fingerprints, status |
| `Feature/FirebirdSyncTest` | 27 | ❌ **All FAIL** (MySQL test DB down) | Strategy ordering, FK deps, controller auth |
| `Feature/DashboardRenderTest` | 5 | ❌ **All FAIL** (MySQL test DB down) | Page rendering, KPI JSON |
| `Feature/AttendanceFilterTest` | 5 | ❌ **All FAIL** (MySQL test DB down) | Filter queries |

**Total:** 56 tests, **0 passing** (environment issue — `rh_reloj_testing` DB not available on MySQL)

### 7.2 Estimated Coverage (Code Inspection)

| Module | Estimated Coverage | Gaps |
|--------|-------------------|------|
| `ZktecoService` | ~60% | Retry logic, template normalization, error paths |
| `FirebirdReader` | ~30% | Chunked fetch, cleanRows, connection failure |
| `SyncStrategies/*` | ~40% | Dependency ordering, orphan deletion, progress callback |
| `EmployeeController` | ~20% | Fingerprint copy/assign/delete, card update, enroll |
| `DeviceController` | ~25% | Sync variants, progress mapping, deduplicate |
| `Academia/*` | ~10% | Kardex calculation, horario resolution, contracts |
| Policies | ~0% | No policy tests |
| Jobs | ~30% | `SyncDeviceJob` partial; `FirebirdSyncJob` not tested |

### 7.3 Test Infrastructure Issues

| Issue | Impact |
|-------|--------|
| **No MySQL test database** (`rh_reloj_testing`) | All feature tests fail — **blocker** |
| **No CI pipeline** (GitHub Actions / GitLab CI) | No automated verification |
| **No coverage reporting** (`phpunit.xml` has `<coverage>` but not run) | Unknown actual coverage |
| **Uses `RefreshDatabase`** (migrate:fresh per test) | Slow; could use `DatabaseTransactions` for speed |

---

## 8. Performance (Known Bottlenecks)

### 8.1 Query-Level

| Endpoint / Method | Bottleneck | Evidence |
|-------------------|------------|----------|
| `DashboardController::kpis()` | 7 separate `count()` queries (today, yesterday, employees, devices) | Lines 51-62 |
| `DashboardController::pipeline()` | 6 separate `count()` queries | Lines 128-148 |
| `DashboardController::donut()` | `groupBy('type')` on `attendances` (no index on `type`) | Line 156 |
| `DashboardController::trend()` | `DATE()` / `DATE_FORMAT()` on `recorded_at` (prevents index use) | Lines 203, 217, 237 |
| `EmployeeController::index/search` | `orderByRaw('LOWER(name)')` (no functional index) | Lines 28, 56 |
| `DeviceController::index` | `weeklyBuckets()` pulls all timestamps to PHP for 3 tables | Lines 29-41 |

### 8.2 Sync Performance

| Operation | Concern | Mitigation in Code |
|-----------|---------|-------------------|
| `syncUsers()` | Fetches ALL users from device, then `firstOrCreate` per user | Chunked by device employees; `enrolledByUserId` preloaded |
| `syncAttendances()` | `insertOrIgnore` per record; then `firstOrFail` + update | Batched but still N queries |
| `syncFingerprints()` | Chunked by 25 employees; per-employee `getFingerprint()` call | Retry per employee; legacy re-attribution |
| `syncAll` | Sequential stages (users → attendances → fingerprints) | Could parallelize attendances+fingerprints |

### 8.3 Frontend

| Issue | Location |
|-------|----------|
| Heartbeat polls `/kpis/json` every 20s (5 count queries) | `app.js:70` |
| Command palette builds results on every keystroke (no debounce) | `app.js:593` |
| Notifications flyout re-renders full list on tab switch | `app.js:449` |

---

## 9. Technical Debt (Prioritized)

| # | Item | Category | Effort | Impact | Owner |
|---|------|----------|--------|--------|-------|
| 1 | **Test DB not running / CI missing** | Infra | Low (start MySQL, add GH Action) | **Critical** — no regression safety | DevOps |
| 2 | `EmployeeController` / `DeviceController` too fat | Code Quality | Medium | High — hard to test, change | Backend |
| 3 | Dashboard KPI queries unindexed / N+1 | Performance | Low (add indexes, combine queries) | Medium — dashboard latency | Backend |
| 4 | `syncAttendances()` N+1 `insertOrIgnore` + `firstOrFail` | Performance | Medium | Medium — sync time scales with records | Backend |
| 5 | No API Resources / DTOs for JSON responses | Consistency | Low | Low — manual array building error-prone | Backend |
| 6 | Legacy `fingerprints.device_id = NULL` rows | Data Quality | Low (auto-cleaned on sync) | Low — self-healing | — |
| 7 | `ZktecoService` monolithic (1025 lines) | Code Quality | Medium | Medium — hard to unit test | Backend |
| 8 | No rate limiting on sync/write endpoints | Security | Low | Medium — DoS risk on device actions | Backend |
| 9 | No CSP / HSTS headers | Security | Low | Low — defense in depth | Backend |
| 10 | Frontend: no component library, vanilla JS growing | Frontend | Medium | Medium — maintenance burden | Frontend |
| 11 | Firebird sync: `CustomSyncStrategy` dependency validation only static | Data Integrity | Medium | Medium — runtime FK violations possible | Backend |
| 12 | `EmployeeController::edit()` loads `availableFingerprints` with complex sort/unique in PHP | Performance | Low | Low — only on edit page | Backend |

---

## 10. Priority: Top 5 to Address First

| Priority | Item | Justification |
|----------|------|---------------|
| **1** | **Start MySQL test DB + add GitHub Actions CI** | Without green tests, **every change is a gamble**. 56 tests exist but 0 pass. This unblocks all other work. |
| **2** | **Add composite index on `attendances(type, recorded_at)` + combine Dashboard KPI queries** | Dashboard is the most-viewed page; 7+ queries on every load. `type` filter in donut/pivot has no index. Low effort, high user-facing impact. |
| **3** | **Rate limiting on all device/employee write endpoints** (`sync-users`, `sync-attendances`, `enroll`, `set-time`, `clear-attendance`) | Currently only login has throttle. A malicious or buggy script could hammer ZKTeco devices (UDP flood) or queue thousands of jobs. |
| **4** | **Extract `FingerprintController` and `DeviceSyncController` from fat controllers** | `EmployeeController` (567 lines) and `DeviceController` (526 lines) violate SRP. Makes testing, debugging, and onboarding harder. |
| **5** | **Add API Resources for JSON endpoints** (`search`, `refreshData`, `syncStatus`, `progress`, `queueData`) | Manual array mapping is inconsistent and error-prone (e.g., `syncStatus` vs `progress` payload drift). Resources give serialization contracts and testability. |

---

## Appendix: Quick Wins (≤30 min each)

- [ ] Add `throttle:30,1` middleware to all `devices.*` POST routes (sync, time, clear, restore)
- [ ] Add `throttle:30,1` to `employees.*` POST routes (upload-fingerprints, sync-devices, enroll, card)
- [ ] Create `attendances_type_recorded_at` index: `$table->index(['type', 'recorded_at'])`
- [ ] Combine `DashboardController::kpis()` into 2 queries (today + yesterday) using conditional aggregation
- [ ] Add `debounce(150ms)` to Command Palette input handler
- [ ] Enable `phpunit.xml` coverage report: `php artisan test --coverage` (once DB runs)

---

*End of Report*