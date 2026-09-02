---
name: change-impact
description: Mandatory impact analysis before changing existing code, interfaces, schemas, signatures, routes, shared helpers or dependencies.
compatibility: opencode
---
# Change Impact Analysis

Use before modifying existing behavior, public/internal contracts, shared functions, database schema, configuration, dependencies, or files consumed by other modules.

## Goal
Prevent regressions by finding consumers and invariants before editing, not after something breaks.

## Required checks
1. Identify the definition being changed.
2. Find every known caller/consumer/reference using repository search (`rg`, project-native reference tools, route maps, import graphs when available).
3. Inspect interfaces, abstract classes, implementations, inheritance, traits/mixins, event listeners, jobs, CLI commands, routes, templates, SQL, migrations and tests that depend on the changed contract.
4. Compare the current and proposed signature/shape: parameters/defaults, return type/shape, exceptions/errors, side effects, nullability, edge cases, performance, transaction/locking behavior, and security assumptions.
5. Classify blast radius: `local`, `module`, `cross-module`, `public/external`.
6. Decide the compatibility strategy before editing.

## Compatibility rules
- Never remove or reorder parameters in a shared function without checking all consumers.
- Prefer backward-compatible adapters/defaults when the project can support them cleanly.
- If a breaking change is justified, update all known consumers in the same logical change and prove they were searched.
- Do not rename database columns, routes, configuration keys, events, API fields, or exported functions without tracing consumers.
- Do not rely on a single search pattern; search aliases, imports, call syntax, route names, string references and documentation when relevant.
- For dynamic languages, treat reflection, string-based calls, template references and configuration-driven references as possible consumers.

## Output
```markdown
# Impact Analysis
## Target change
## Consumers found
## Dependencies/invariants
## Compatibility risk
## Blast radius
## Recommended safe approach
## Verification needed after change
```

A missing reference is not proof that no consumer exists. State uncertainty explicitly.
