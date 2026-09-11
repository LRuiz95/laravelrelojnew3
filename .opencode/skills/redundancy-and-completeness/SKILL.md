---
name: redundancy-and-completeness
description: Detect functional redundancy, dead code, unnecessary UI elements, and structural disorganization by reasoning about *why* something exists, not just whether it works.
compatibility: opencode
---
# Redundancy & Completeness

Most audits check "does this code work / is it safe / is it fast". This
skill checks a different question: **should this exist, in this form,
at all?** It requires reasoning about intent and data flow, not just
syntax.

## 1. Functional redundancy (the expensive one to find)

A feature is redundant when a *simpler path to the same outcome already
exists elsewhere in the system*, even if the feature itself has no bugs.

Canonical pattern: a dedicated "create X" button/menu-entry exists in
module B, but module B already renders a table of X sourced from a
system of record (another module, a synced external DB, a master table)
where creating X inline would remove a step and a duplicate code path.

To find these:
1. For every create/edit/delete action exposed in the UI, ask: *where
   does the data this acts on ultimately come from?* If the answer is
   "it's synced/mirrored from another table or system", check whether
   that source view already has (or could trivially have) the same
   action inline.
2. For every menu item / button, ask: *is there another screen in this
   project where the same outcome is already one click away?* If yes,
   the second entry point is a candidate for removal or consolidation,
   not automatically a bug — verify it isn't intentional (different
   permission level, different validation, offline vs online device
   context, etc.) before flagging.
3. Look for two controllers/services that validate and persist the same
   entity through different rules — this is redundancy with a
   correctness risk, not just clutter.

Report format per finding:
```
Finding: <what exists>
Redundant with: <the simpler/existing path>
Evidence: <files, routes, lines>
Why it's redundant: <the shared data source / outcome>
Risk if left as-is: <duplicate validation drift, confusing UX, extra
  maintenance surface, etc.>
Recommended action: <remove / merge / justify>
Confidence: high | medium | low
```
Never recommend deletion outright — recommend `merge into <existing
path>` or `remove, superseded by <X>`, and let a human/architect confirm
before anything is touched (this skill is read-only diagnosis).

## 2. Dead / unreachable code

- Routes with no corresponding link, button, or API caller anywhere in
  the codebase (grep for the route name/path across views, JS, tests).
- Controller methods never referenced by any route.
- Blade partials/components not `@include`d or `<x-...>` anywhere.
- Feature-flagged or commented-out blocks left in place after the flag
  was permanently resolved one way.
- Config keys / env vars read nowhere, or written nowhere but read.

Grep-verify, don't assume from naming. A method named `legacy*` that is
still called from an active route is NOT dead code.

## 3. UI/menu distribution and placement

- Does every button's location match where the user is already looking
  when they need it (proximity to the data it acts on), or was it bolted
  onto a generic toolbar out of convenience?
- Are actions of similar destructiveness/frequency given similar visual
  weight across analogous screens? (cross-check with
  `structural-symmetry`)
- Is there a menu entry whose destination is now empty, deprecated, or a
  placeholder ("Coming soon", empty table with no seed path)?

## 4. Cross-reference integrity (end-to-end trace)

For each route:
```
route -> controller@method exists? -> view file exists? -> model(s)
referenced exist with those columns/relations? -> any Blade @include /
component referenced exists? -> any asset (JS/CSS/image) referenced
resolves to a real file?
```
Flag any broken link in that chain, both directions (a view expecting a
variable the controller never passes is as much a bug as a missing
route).

## 5. Organization smells

- Business logic sitting in a controller when a Service/Action class
  pattern is already established elsewhere in the project
  (cross-check `structural-symmetry`).
- Two modules solving the same problem (e.g. two different "sync
  employee" code paths) that evolved independently.
- A model with responsibilities that belong to a different domain
  (violates the project's own established boundaries).

## Output discipline

- Every finding needs file:line evidence — no "creo que" / "probablemente".
- Separate `CONFIRMED` (traced end-to-end) from `SUSPECTED` (pattern
  matches but not fully traced — needs a human or `architect` to
  confirm before acting).
- Never propose removing something whose only evidence of disuse is
  "I didn't find a caller in the files I looked at" without stating the
  search scope used (which directories/greps), so a human can judge
  whether the search was exhaustive.
