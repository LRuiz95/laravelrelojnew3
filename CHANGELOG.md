# CHANGELOG

## Updated reusable team — 2026-09-01

- Repaired `testing-strategy` skill.
- Removed stack-specific version commands from `team-lead`.
- Added `api`, `design`, and `libraries` agents.
- Added `architecture-patterns`, `programming-paradigms`, `design-system`, `api-design`, `dependency-hygiene`, `structural-symmetry`, and `polyglot-boundaries` skills.
- Converted polyglot role from agent to skill.
- Added explicit skill permissions to agents.
- Clarified project-local regeneration of `LOCKS.md` and `DECISIONS.md`.
- Integrated new roles into team workflow and model routing.
- Kept `learn` outside the default execution flow.

## Hardening pass — 2026-09-01

- Removed stack-specific command permissions from `implementer`.
- Added explicit shared-skill loading guidance to specialist agents.
- Strengthened reviewer checks for structural symmetry.
- Clarified neutral project discovery and model-availability language.
- Documented current package inventory and project-local coordination state.
- Routed `/sync` explicitly through integration with database/security/QA gates as applicable.

## 2.3 — Design specialist strengthening

- Strengthened `.opencode/agents/design.md` into a professional UX/UI lead role.
- Added `.opencode/skills/ux-professional-design/SKILL.md` with discovery, information architecture, visual hierarchy, interaction states, responsive design, accessibility, UX heuristics, critique and implementation handoff.
- Made design validation evidence-driven and pattern-first, with explicit symmetry/asymmetry rules.
- Clarified that `frontend` implements the visual system defined by `design` and reports deviations.
