---
name: safe-git
description: Safe Git-oriented workflow with mandatory pre-change inspection, rollback points, checkpoints, review and traceable commits.
compatibility: opencode
---
# Safe Git

Git is part of the engineering safety system, not an afterthought.

## Before any edit
1. Run `git status`, inspect the current branch, recent commits and relevant diff.
2. Determine whether the worktree is clean or already contains user changes.
3. Never overwrite, reset, checkout over, stash destructively, or commit unrelated user work.
4. If the worktree is clean, create a checkpoint commit before the first mutation. Use an explicit checkpoint message; an empty commit is acceptable and intentional.
5. If the worktree is dirty, preserve the existing changes and create a safe rollback point at the current `HEAD` (for example a temporary safety branch/tag) instead of mixing unrelated changes into the checkpoint. Report the dirty state before proceeding.

## During changes
- Keep one logical change per commit when practical.
- Do not rewrite history unless explicitly requested.
- Do not push unless explicitly authorized.
- If a change becomes unsafe or validation fails, stop and report the rollback point instead of continuing blindly.

## Before each final commit
- inspect the complete diff;
- inspect status and changed-file list;
- scan for secrets, credentials, generated artifacts and accidental files;
- verify affected consumers were checked;
- run the relevant tests/lint/build/type checks;
- run security/data/integration validation when applicable;
- commit only the intended logical change.

## Rollback
Every risky task must be able to answer: “What exact Git state do we return to if this breaks?”
