---
name: dependency-hygiene
description: Evaluate dependencies for security, maintenance, compatibility, license and supply-chain risk.
compatibility: opencode
---
# Dependency Hygiene

## Audit dimensions

1. Known vulnerabilities.
2. Supported/runtime compatibility.
3. Maintenance activity and release cadence.
4. License compatibility with project requirements.
5. Duplicate or overlapping dependencies.
6. Package provenance and lockfile integrity.
7. Transitive dependency risk.
8. Migration cost and rollback path.

## Rules

- Inspect the lockfile before proposing upgrades.
- Prefer the smallest safe upgrade that resolves the issue unless a major upgrade is justified.
- Distinguish security patch, minor upgrade and breaking upgrade.
- Never update dependencies solely to chase the newest version.
- Record why a vulnerable dependency is temporarily retained when immediate removal is unsafe.

## Commands
Discover the real package manager first, then use its native audit tooling. Examples may include:
- Composer: `composer audit`
- npm: `npm audit`
- pip: project-supported audit tool such as `pip-audit`
- Cargo: `cargo audit`

Do not run a command merely because an example exists; verify it is installed and relevant to the project.

## Deliverable
Produce a table of dependency, current version, target, reason, risk, compatibility impact and verification command.
