# Permisos y delegación (sintaxis real OpenCode V2)

> Esto documenta la intención detrás de las reglas `permissions:` que cada
> agente declara en su propio frontmatter (`.opencode/agents/*.md`). Los
> valores reales están en cada archivo de agente — esto es la referencia
> centralizada para entender el diseño completo de un vistazo.

## Formato real de una regla

```yaml
permissions:
  - action: edit        # edit | shell | subagent | read | webfetch | ...
    resource: "app/**"  # ruta, comando, o ID de agente; soporta wildcards
    effect: allow        # allow | ask | deny
```

**Gana la última regla que haga match** — por eso el orden importa: reglas
amplias primero, excepciones después.

## Quién puede delegar (`action: subagent`) a quién

```
team-lead   → * (allow)  — puede invocar a cualquier subagente
architect   → ninguno (deny *) — solo produce el plan, team-lead delega
security    → ninguno (deny *)
reviewer    → ninguno (deny *)
tester      → ninguno (deny *)
cleanup     → ninguno (deny *)
todos los demás subagentes → ninguno (deny *)
```

Ningún subagente delega a otro subagente. Solo `team-lead` (el único
`mode: primary`) orquesta. Esto evita ciclos `A → B → C → A`.

## Quién puede editar qué (`action: edit`)

| Agente | Puede editar | No puede editar |
|---|---|---|
| team-lead | nada directamente (delega) | todo |
| architect | `.opencode/state/plan.md`, `.opencode/state/decisions.md` | código fuente |
| laravel | `app/**`, `routes/**`, `tests/**`, `.opencode/state/findings.md` | `database/migrations/**`, `.env*`, `config/auth.php`, `resources/views/**` (uso mínimo permitido, dominio de frontend) |
| frontend | `resources/**`, `public/**`, `.opencode/state/findings.md` | `app/Http/Controllers/**`, `.env*` |
| mysql / firebird | nada (solo lectura + diagnóstico) | todo el código |
| integration / data-integrity | nada (solo lectura + diagnóstico) | todo el código |
| security / reviewer | nada | todo |
| tester | `tests/**` únicamente | código de producción |
| docs | `*.md`, `docs/**`, `CHANGELOG.md` | código fuente |
| cleanup | `.opencode/state/*.md`, `.opencode/state/archive/**` (archivar/resetear); archivos temporales declarados en `generated-files.md` vía `rm` con `ask` | código de producción, tests, `decisions.md`, cualquier archivo no declarado como temporal |

## Rutas sensibles (bloqueadas por defecto en todos los agentes de escritura)

- `database/migrations/**`
- `.env`, `.env.*`
- `config/auth.php`
- `app/Policies/**`, cualquier Gate
- Cualquier ruta de pagos/facturación

Solo se abren temporalmente cuando el Task Boundary
(`.opencode/policies/task-boundary.md`) las incluye explícitamente en
`Allowed files`, y aun así requieren HUMAN APPROVAL antes de DONE.

## Shell (`action: shell`)

- `mysql`, `firebird`: `ask` para cualquier comando (nunca `allow`
  irrestricto) — son diagnóstico, no ejecución libre.
- `tester`: `allow` para comandos de test (`php artisan test *`,
  `vendor/bin/phpunit *`), `deny` para el resto.
- `laravel`: `ask` para `php artisan *` fuera de generación de código,
  `deny` para comandos de sistema.
- `architect`, `security`, `reviewer`, `integration`, `data-integrity`:
  `deny` — son de solo lectura, no ejecutan nada.
- `cleanup`: `ask` para `rm *` y `git clean *` (nunca `allow` — cada
  eliminación se confirma explícitamente), `allow` solo para `git status
  *` (inspección, no destructivo), `deny` para el resto.
