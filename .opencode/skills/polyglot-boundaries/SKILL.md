---
name: polyglot-boundaries
description: Manage consistency and interfaces when a project uses multiple languages, runtimes or architectural styles.
compatibility: opencode
---
# Polyglot Boundaries

Use when more than one implementation language/runtime is active or when legacy and modern stacks exchange data/contracts.

## Boundary checklist

- Define which process/runtime owns each responsibility.
- Define data contracts between languages.
- Normalize naming and encoding at boundaries.
- Document error semantics and retries.
- Avoid leaking framework-specific types across boundaries.
- Test serialization/deserialization and null/empty semantics.
- Identify version coupling and deployment order.

## Example concerns

- PHP ↔ JavaScript JSON contracts
- Python ETL ↔ SQL database
- Go service ↔ browser API
- Legacy script ↔ modern framework

The goal is not to make languages look identical. The goal is to make the boundaries predictable.
