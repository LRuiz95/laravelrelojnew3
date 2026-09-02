---
name: ux-professional-design
description: "Apply professional UX/UI methodology to web interfaces: research, information architecture, interaction design, visual hierarchy, responsive behavior, accessibility, states, content clarity and visual QA."
compatibility: opencode
---
# Professional UX/UI Design

## Mission
Design interfaces that help the real user accomplish the real task with the least unnecessary cognitive load. Visual polish is subordinate to usability, accessibility, consistency, and product goals.

## Discover before designing
- Identify the product type, primary user, task, context of use, and operational/business objective.
- Inspect existing screens, routes, components, tokens, assets, content, and interaction patterns before proposing changes.
- Search for analogous screens and prefer extension of an existing pattern over invention.
- Detect the actual frontend framework/version and technical constraints; never assume a library, breakpoint system, component library, or browser capability.
- Preserve working behavior unless a UX problem justifies changing it.

## Information architecture
- Establish hierarchy before styling.
- Group information and actions by user intent, not implementation detail.
- Distinguish primary, secondary, and destructive actions.
- Use labels that match user vocabulary and existing product terminology.
- Avoid duplicate destinations, ambiguous labels, unnecessary nesting, and navigation that hides important tasks.
- For data-dense/admin interfaces, optimize for scanning, comparison, filtering, context preservation, and efficient repeated tasks.

## Visual hierarchy and composition
- Use a consistent alignment/grid system.
- Use symmetry when it improves order and scanability; use asymmetry only when it creates intentional hierarchy.
- Define spacing rhythm, typography scale, line-height, container widths, radii, elevation, and icon treatment before styling isolated components.
- Use semantic color roles instead of unrelated one-off colors.
- Maintain a deliberate balance between information density and whitespace.
- Prefer a small number of strong visual levels over many competing accents.
- Remove decoration that does not improve comprehension, orientation, or feedback.

## Interaction design
For every meaningful interactive screen define, when applicable:
- default;
- hover;
- focus;
- active/selected;
- loading;
- success;
- error/validation;
- empty/no-results;
- disabled/unavailable;
- confirmation for destructive actions;
- recovery path after failure.

Feedback must make system status understandable. The user should never have to guess whether an action succeeded, failed, or is still running.

## Forms, tables and dashboards
### Forms
- Explicit labels, logical order, helpful defaults, predictable tab sequence, concise validation, and preservation of entered data after recoverable errors.

### Tables
- Optimize for scanning with stable columns, readable density, meaningful sorting/filtering, clear row actions, and useful empty states.
- Keep critical identifiers/status visible.
- On narrow layouts, use deliberate strategies such as priority columns, horizontal scrolling, or row-to-detail transformations rather than arbitrary hiding.

### Dashboards
- KPIs must represent meaningful decisions or operational signals, not decoration.
- Establish a reading path from overview to diagnosis to action.
- Charts must answer a question or reveal a pattern; do not add charts only to make a page look modern.

## Responsive design
Design from content constraints, not device names alone.
- Identify breakpoints where content or interaction stops fitting well.
- Re-evaluate navigation, filters, tables, forms, cards, modals, and actions at narrow widths.
- Maintain usable touch targets and visible focus.
- Never solve responsive design by merely shrinking the desktop layout.

## Accessibility baseline
Use a WCAG 2.2 AA mindset unless the project has a stricter requirement.
- Perceivable: contrast, text alternatives, meaningful structure.
- Operable: keyboard access, visible focus, logical order, sufficient target size.
- Understandable: predictable behavior, labels, error recovery.
- Robust: semantic HTML and compatibility with assistive technology.

Accessibility is part of the design, not a final cosmetic pass.

## UX heuristics and laws
Use principles as decision tools, not rigid formulas:
- Nielsen heuristics for visibility, consistency, error prevention, user control, and help.
- Fitts's law for target size and reachability.
- Hick's law for reducing unnecessary choices.
- Jakob's law for familiar interaction patterns.
- Aesthetic-usability effect without letting visual polish hide serious usability problems.
- Pareto to prioritize high-impact UX problems.

Do not treat simplistic rules such as a fixed "three-click" or "seven-second" threshold as universal requirements. Validate against the actual task and user context.

## Design critique
Evaluate a screen in this order:
1. task success and information architecture;
2. hierarchy and scanability;
3. interaction clarity and feedback;
4. consistency with existing patterns;
5. accessibility;
6. responsive behavior;
7. visual refinement.

For each important finding report the user impact, severity, evidence, and recommended correction.

## Handoff
Every non-trivial design decision should provide implementation-ready guidance:
- objective and user task;
- affected screen/component;
- analogous existing pattern;
- layout/grid and spacing;
- typography;
- semantic color roles;
- component variants/states;
- responsive behavior;
- accessibility requirements;
- content/microcopy considerations;
- constraints;
- acceptance criteria;
- deviations requiring explicit approval.

## Quality gate
Before approving a design, verify:
- it improves task clarity over the current state;
- it follows analogous patterns unless a reason is documented;
- visual balance is intentional rather than mechanically symmetric;
- important states are represented;
- keyboard and narrow-width use are viable;
- the existing design system is extended rather than duplicated;
- each requested element has a user/product justification.
