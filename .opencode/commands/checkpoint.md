---
description: create or verify a safe Git rollback checkpoint before risky work
agent: team-lead
model: opencode/big-pickle
---

Prepare a safe rollback point for: $ARGUMENTS. Inspect status, branch and recent commits. If the worktree is clean, delegate the checkpoint commit through `git` using an explicit checkpoint message. If dirty, preserve existing user changes and do not mix unrelated work into the checkpoint; report the safe HEAD and next action. Never push.
