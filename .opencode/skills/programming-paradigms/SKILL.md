---
name: programming-paradigms
description: Keep programming paradigms coherent with the existing code and use the right paradigm for the problem.
compatibility: opencode
---
# Programming Paradigms

Identify the dominant style before introducing a new one.

## Common paradigms

- Imperative: explicit steps and state changes.
- Procedural: organized procedures/functions, often effective in scripts and legacy code.
- Object-oriented: encapsulation, polymorphism, domain/service objects.
- Functional: pure transformations, immutability, composition.
- Declarative: describe desired result rather than control flow, common in SQL and UI markup.
- Reactive/event-driven: respond to streams/events over time.

## Rules

1. Do not rewrite a procedural module into OOP just because OOP is preferred elsewhere.
2. Do not mix paradigms inside one module without a boundary that explains why.
3. Follow the language ecosystem's idioms.
4. Preserve the project's dominant conventions unless the task explicitly improves the boundary.
5. When multiple paradigms coexist, document the boundary and data-flow contract.

## Review questions

- What paradigm dominates this module?
- Does the proposed change preserve that style?
- If it crosses paradigms, is the boundary intentional and testable?
