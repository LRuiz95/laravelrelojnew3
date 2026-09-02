---
name: testing-strategy
description: Define practical, layered test strategies from acceptance criteria, regression risk, boundaries, and the project's real commands.
compatibility: opencode
---
# Testing Strategy

## Purpose

Choose the smallest test set that gives high confidence without pretending that
all tests provide the same kind of evidence.

## Core principles

1. Test behavior and contracts, not implementation details.
2. Prefer the project's existing test stack and conventions.
3. Use the real database/service boundary when the changed behavior depends on it.
4. A passing test suite is evidence, not proof that an untested risk disappeared.
5. Never claim tests passed unless the command was actually executed.
6. When no tests exist, create a verification strategy before creating a large test suite.

## Test layers

### Static checks
Use when the project provides them:
- syntax/type checks
- linters/formatters in check mode
- framework diagnostics
- dependency checks

These catch cheap failures early.

### Unit tests
Use for isolated rules:
- pure transformations
- validation rules
- parsers
- domain calculations
- small services with deterministic inputs

A unit test is insufficient when correctness depends on SQL, transactions,
HTTP middleware, filesystem behavior, queues, or external services.

### Integration tests
Use when multiple real components must agree:
- database queries and constraints
- repositories/services against a real test database
- HTTP endpoint + middleware + validation + persistence
- queue/event boundaries
- data synchronization against representative fixtures

### End-to-end tests
Use selectively for critical workflows where a defect crossing several layers
would be expensive:
- authentication/login
- payment or irreversible actions
- critical CRUD journeys
- high-value synchronization flows

Do not use end-to-end tests for every permutation of a low-level rule.

## Decision matrix

| Change | Minimum evidence |
|---|---|
| Pure function | Unit test + static check |
| Validation/business rule | Unit tests for boundaries + integration if persistence affects outcome |
| Controller/endpoint | Integration/HTTP test + relevant unit tests |
| SQL/query | Integration test against the project's supported database |
| Migration/schema | Migration execution + schema/integrity verification + affected feature tests |
| Authentication/authorization | Positive + negative authorization tests + security review |
| File upload/import | Validation tests + integration with filesystem/parser |
| Sync/ETL | Fixtures + insert/update/unchanged/delete cases + retry/transaction checks |
| Performance change | Before/after measurement + correctness regression tests |
| UI behavior | Existing frontend test/lint stack + manual acceptance where automation is absent |

## Regression and consumer coverage

For any existing function, class, route, query, event, schema, configuration key or exported symbol, testing must cover the known consumers identified by `change-impact`.

Before changing a shared signature, record the current call shape. After the change, verify: `arity` (parameter count/order/defaults), return shape, error behavior and side effects. For dynamic systems, include string/config/template/reflection-based consumers when discovered.

A test that passes only the modified module is not sufficient when the change has `module`, `cross-module`, or `public/external` blast radius.

## Change-without-tests protocol

When a changed area has no tests:

1. Identify the behavior that must not regress.
2. Capture a baseline using safe commands or representative fixtures.
3. Add the smallest characterization test possible, if feasible.
4. Implement the change.
5. Re-run the baseline and regression checks.
6. Document any untestable risk explicitly.

Do not manufacture a fake test solely to make CI green.

## Data and synchronization

For synchronization, the test matrix should explicitly cover:

- source record absent / target absent → INSERT
- source changed / target exists → UPDATE
- source unchanged / target exists → UNCHANGED
- source no longer present → DELETE only when the sync contract permits it
- duplicate source identity
- missing primary key / invalid identity
- null vs empty vs normalized values
- batch boundaries
- transaction rollback on failure
- safe retry after partial failure

For destructive behavior, prefer dry-run or isolated fixtures before touching real data.

## Output contract

Return:

```markdown
## Test Strategy
- Scope:
- Risk:
- Minimum evidence:
- Commands:

### Cases
| Case | Type | Expected |
|---|---|---|

### Gaps
- ...

### Ready when
- [ ] ...
```
