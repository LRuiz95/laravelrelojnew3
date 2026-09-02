# MODEL ROUTING — FREE ONLY

This reusable team is configured to use OpenCode free models only.

Model availability is dynamic. Before a new project session, run `/models` and verify that the IDs below are available in the connected provider. Do not assume a model remains free forever.

## Free model pool

| Model | ID | Strength | Typical use |
|---|---|---|---|
| Big Pickle | `opencode/big-pickle` | reasoning / review | lead, review, complex planning |
| MiMo-V2.5 Free | `opencode/mimo-v2.5-free` | reasoning / trade-offs | architecture, database, teaching |
| Nemotron 3.5 Lightning Free | `opencode/nemotron-3.5-lightning-free` | implementation / testing | coding, debugging, QA |
| Nemotron 3 Ultra Free | `opencode/nemotron-3-ultra-free` | deep technical audit | integration, security, libraries |
| Muse Spark 1.2 Contributor Free | `opencode/muse-spark-1.2-contributor-free` | UI / design | design, frontend |
| Ling 3.0 Flash Fin Free | `opencode/ling-3.0-flash-fin-free` | fast scanning | explorer, docs, git, research, performance |

These are the free models currently listed by OpenCode Zen; OpenCode explicitly notes that the free availability is temporary/dynamic, so availability must be verified in the current `/models` catalog.

## Role routing

| Agent | Preferred | Fallback 1 | Fallback 2 |
|---|---|---|---|
| `team-lead` | Big Pickle | MiMo-V2.5 Free | Nemotron 3.5 Lightning Free |
| `reviewer` | Big Pickle | Nemotron 3 Ultra Free | MiMo-V2.5 Free |
| `architect` | MiMo-V2.5 Free | Nemotron 3 Ultra Free | Big Pickle |
| `database` | MiMo-V2.5 Free | Nemotron 3 Ultra Free | Nemotron 3.5 Lightning Free |
| `api` | MiMo-V2.5 Free | Big Pickle | Nemotron 3.5 Lightning Free |
| `integration` | Nemotron 3 Ultra Free | MiMo-V2.5 Free | Nemotron 3.5 Lightning Free |
| `security` | Nemotron 3 Ultra Free | Big Pickle | MiMo-V2.5 Free |
| `libraries` | Nemotron 3 Ultra Free | Ling 3.0 Flash Fin Free | MiMo-V2.5 Free |
| `implementer` | Nemotron 3.5 Lightning Free | Nemotron 3 Ultra Free | MiMo-V2.5 Free |
| `qa` | Nemotron 3.5 Lightning Free | Nemotron 3 Ultra Free | Ling 3.0 Flash Fin Free |
| `design` | Muse Spark 1.2 Contributor Free | MiMo-V2.5 Free | Big Pickle |
| `frontend` | Muse Spark 1.2 Contributor Free | Nemotron 3.5 Lightning Free | MiMo-V2.5 Free |
| `explorer` | Ling 3.0 Flash Fin Free | Nemotron 3.5 Lightning Free | MiMo-V2.5 Free |
| `git` | Ling 3.0 Flash Fin Free | Nemotron 3.5 Lightning Free | MiMo-V2.5 Free |
| `docs` | Ling 3.0 Flash Fin Free | MiMo-V2.5 Free | Muse Spark 1.2 Contributor Free |
| `researcher` | Ling 3.0 Flash Fin Free | MiMo-V2.5 Free | Big Pickle |
| `performance` | Ling 3.0 Flash Fin Free | Nemotron 3 Ultra Free | MiMo-V2.5 Free |
| `learn` | MiMo-V2.5 Free | Big Pickle | Ling 3.0 Flash Fin Free |

## Selection policy

1. Prefer the first available model in the role's fallback chain.
2. Never replace a free model with a paid model automatically.
3. If none of the listed free models is available, stop and report the missing model instead of silently using a paid model.
4. For high-risk architecture, security, data migration, or production changes, prefer a reasoning/deep-audit model over a fast model.
5. For UI/design decisions, keep `design` on Muse Spark when available; use the documented fallback only when unavailable.
6. The model assigned in each agent file is the preferred model. The fallback chain is coordination policy for `team-lead`, not a guarantee that OpenCode will auto-switch providers.

## Current agent assignments

All agent files in this package use one of the free IDs above. The exact assigned ID is visible in each `.opencode/agents/*.md` file.

## Verification

At the start of a new environment or after OpenCode updates:

```text
/models
```

Then confirm the preferred model for the active agent exists. If not, use the first available fallback from this document and record the deviation in the session handoff.

## Important

OpenCode's catalog is dynamic and the free models are offered for a limited time. Do not encode assumptions such as "this model will always be free" into project logic.
