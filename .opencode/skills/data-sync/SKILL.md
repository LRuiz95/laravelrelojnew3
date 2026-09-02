---
name: data-sync
description: Design robust data synchronization and ETL with identity, idempotency, transformations, transactions, retries and parity checks.
compatibility: opencode
---
# Data Synchronization

Treat source authority, destination authority, identity and direction as explicit.

Define:
- identity/PK mapping;
- normalization/transformation;
- INSERT/UPDATE/UNCHANGED/DELETE behavior;
- duplicate handling;
- transaction boundaries;
- partial failure behavior;
- retry semantics;
- before/after counts;
- audit trail.

Never delete or replace an old synchronization path without a documented equivalence test when it is still relied upon.
