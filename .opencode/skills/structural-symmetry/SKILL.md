---
name: structural-symmetry
description: Detect and preserve intentional structural consistency across analogous modules, CRUDs, screens, services and workflows.
compatibility: opencode
---
# Structural Symmetry

## Principle
When two parts of a system solve the same kind of problem, they should use the same structural pattern unless there is an explicit reason not to.

## Checkpoints

Compare the target change with at least one analogous existing implementation:
- folder/file structure
- naming
- controller/service/repository boundaries
- validation flow
- error handling
- response shape
- transaction handling
- UI layout/component composition
- test organization

## Detect violations
A difference is a finding when:
1. the cases are materially analogous;
2. the pattern is already established;
3. the difference adds cognitive or operational cost;
4. no business/technical reason justifies it.

## Do not force symmetry
Intentional differences are healthy when documented by:
- domain constraints
- performance requirements
- security boundary
- external contract
- lifecycle difference

## Review output
For each deviation:
- analogous reference
- observed difference
- impact
- justification if known
- recommended alignment
