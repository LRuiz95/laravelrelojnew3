# FILE LOCKS

This is a coordination protocol, not an operating-system mutex.

**Lifecycle:** regenerate/clear this file when the package is installed into a new project. It must never carry locks from another project.

| Path/glob | Agent | Start | End | Reason |
|---|---|---|---|---|
| | | | | |

## Rules
- One active editing agent per sensitive file.
- Reviewer is read-only.
- Release a lock when the agent delivers.
- If two tasks need the same file, sequence them.
- When an agent finishes, clear its rows.
