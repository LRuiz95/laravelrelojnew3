# WORKFLOW

1. Detect project instructions and stack.
2. Inspect Git state and establish a rollback/checkpoint point **before the first edit**.
3. Explore the affected area.
4. Run `change-impact` before altering existing/shared behavior.
5. Load applicable shared knowledge skills (architecture, paradigms, design, API, dependency hygiene, symmetry).
   When the change exposes an API, obtain `api` contract review; when it changes the visual system, obtain `design`; when it changes dependencies, obtain `libraries`.
6. Obtain architecture/API/design decisions when complexity warrants them.
7. Implement in small logical units.
8. Validate with real project commands.
9. Run security, dependency, or performance review when the surface warrants it.
10. Run independent review.
11. Re-check changed consumers/contracts and confirm no compatibility hole was introduced.
12. Prepare a traceable commit.
13. Document important decisions and learning.

No step may claim completion from intent alone: use executed commands, test output, or clearly marked residual risk as evidence.

## Specialized gates

- API changes: `api` before implementation, then QA/reviewer.
- Design-system changes: `design` before `frontend`.
- Dependency changes: `libraries` before updating manifests/lockfiles.
- Multiple languages: load `polyglot-boundaries` before changing the boundary.

## Concurrency

Do not run concurrent editing agents over overlapping files or contracts.

## Rollback

Changes touching data, schema, auth or external integrations require an explicit rollback or recovery strategy before execution.


## Safe change loop

```text
GIT PRECHECK
  -> CHECKPOINT / ROLLBACK POINT
  -> DISCOVER
  -> IMPACT ANALYSIS
  -> PLAN
  -> SMALL CHANGE
  -> TEST AFFECTED AREA
  -> REVIEW DIFF + CONSUMERS
  -> COMMIT
  -> REPORT + OPTIONS
```

If impact analysis discovers a wider blast radius than expected, stop the implementation step and return to planning. Do not “fix forward” blindly.
