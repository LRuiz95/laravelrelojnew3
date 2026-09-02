# TEAM PROTOCOL

## Role boundaries

Lead coordinates. Explorer discovers. Architect decides system boundaries. API defines HTTP/API contracts. Design defines visual-system decisions. Implementer writes general application code. Database owns persistence concerns. Integration owns data exchange and synchronization. Libraries owns dependency/supply-chain hygiene. Security audits trust boundaries. Performance measures. Frontend implements presentation. QA validates. Reviewer independently audits the final diff. Git prepares traceable commits. Docs records decisions. Learn teaches on demand. `polyglot-boundaries` handles cross-language boundaries as a skill.

## Escalation

A specialist should return to Architect rather than invent a cross-layer design. A reviewer reports problems rather than rewriting the implementation. A blocked agent reports the blocker instead of guessing.

## File ownership

Use `.opencode/team/LOCKS.md` for coordination. Do not run concurrent editors over overlapping files.

## Mandatory safety gates

**Pre-change gate — before the first mutation of every task:**
- inspect `git status`, branch, recent commits and diff;
- establish a rollback/checkpoint point;
- run `change-impact` on the affected code/contracts;
- list known consumers and invariants;
- define the smallest safe change and its validation plan.

**Post-change gate — before declaring success:**
- compare the final diff against the requested objective;
- re-check consumers of every changed shared function/interface/schema;
- run relevant tests and project-native validation;
- review security/performance/data implications when applicable;
- preserve or document residual risk;
- create a traceable commit for the logical change.

**No silent breakage rule:** changing a shared function, route, API shape, DB column, query contract, event, configuration key, or exported symbol requires an explicit consumer-impact check. If the signature changes, verify every known caller or provide a compatibility adapter.

## Decision support

For non-trivial work, the lead should present a small set of continuation options after analysis (normally 2–3), clearly label the recommended option, and explain the trade-off. Do not dump a list of choices without a recommendation.

## Evidence

Every important claim should be traceable to a repository fact, executed command, test result, official documentation, or explicitly marked proposal.
