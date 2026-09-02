---
name: design-system
description: "Define professional visual-system decisions: hierarchy, typography, spacing, color, contrast, alignment, balance and accessibility."
compatibility: opencode
---
# Design System

## Core goals

Visual quality is consistency plus intentional hierarchy. Never add decorative complexity without a purpose.

## System fundamentals

- Establish a spacing scale and use it consistently.
- Establish a typographic hierarchy with predictable size, weight, line-height and measure.
- Define color roles, not isolated colors: surface, text, border, primary, status, focus, destructive, success.
- Check contrast and focus visibility.
- Prefer alignment on a common grid.
- Use symmetry when it improves scanability; use asymmetry only when it creates intentional hierarchy.
- Reuse component patterns for analogous states and screens.
- Respect responsive behavior instead of shrinking desktop layouts.

## Gestalt checks

Use proximity, similarity, continuity, common region and figure-ground intentionally.

## Design decision record

For significant visual changes state:
- hierarchy goal
- grid/spacing choice
- typography choice
- color-role choice
- responsive behavior
- accessibility consideration
- analogous component/pattern followed

## Implementation boundary
The design agent decides the system; the frontend agent applies it. If the existing project already has a design system, extend it rather than creating a second one.
