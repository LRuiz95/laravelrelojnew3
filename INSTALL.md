# Installation

1. Back up any existing `.opencode` configuration.
2. Copy this package's `.opencode/` and `docs/` into the target project.
3. Merge with existing `AGENTS.md` instructions rather than replacing project-specific rules.
4. Start OpenCode from the project root.
5. Invoke `@team-lead`.
6. Start substantial work with `/plan <task>`.

## Optional default agent

Merge the following into the project `opencode.jsonc`:

```jsonc
{
  "$schema": "https://opencode.ai/config.json",
  "default_agent": "team-lead"
}
```

## First test

```text
@team-lead
Detect this project's stack, versions, critical paths and real test commands. Do not edit anything.
```


## Project-local state

### Clear before first use

After installation into a real project, clear/regenerate:
- `.opencode/team/LOCKS.md`
- `.opencode/team/DECISIONS.md`

These files are intentionally templates so one project's state does not leak into another. If you version them, commit only the project-local contents. A consuming project may also choose to ignore them locally if coordination state should remain untracked.

## V2 paths

Use `.opencode/agents/` and `.opencode/commands/` as documented by current OpenCode V2. Do not maintain duplicate singular/plural directories.
