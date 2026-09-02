---
name: architecture-patterns
description: Compare architecture patterns and preserve the chosen architecture consistently.
compatibility: opencode
---
# Architecture Patterns

## Principle
Choose the simplest architecture that satisfies current constraints. Do not introduce layers, events, repositories, CQRS, or services merely because they are fashionable.

## Catalog

- Layered/MVC: good default for conventional web applications when boundaries are clear.
- Modular monolith: strong choice when one deployable system has distinct business areas.
- Hexagonal / Ports and Adapters: useful when domain logic must be isolated from infrastructure and external systems.
- Clean Architecture: useful where dependency direction and use-case isolation justify the added ceremony.
- DDD: useful when domain complexity, language, invariants, and bounded contexts dominate the problem.
- CQRS: useful when read/write models have materially different needs; avoid for ordinary CRUD.
- Event-driven: useful for asynchronous decoupling and integration workflows; account for eventual consistency.
- Microservices: use only when team/domain/deployment boundaries justify distributed-system costs.
- Serverless: useful for event-driven workloads with suitable operational constraints.

## Decision test
Before changing architecture, record:
1. Current architecture evidence.
2. Pain point.
3. Constraints.
4. Options considered.
5. Why the chosen option is simpler or safer.
6. Migration/rollback implications.

## Consistency rule
A new module should resemble analogous existing modules unless there is a documented reason not to.
