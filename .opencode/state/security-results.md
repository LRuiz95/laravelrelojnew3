# Security Review — `resources/views/academia/dashboard/index.blade.php`

**Reviewer:** opencode/security (review 1)
**Date:** 2026-09-09
**Severity:** LOW
**Verdict:** PASS — no security issues found.

---

## 1. CSRF on forms

- **GET form** (`ciclo-switcher`, line 21): GET requests don't require CSRF tokens. Correct.
- **POST fetch** (line 422): Reads CSRF from `<meta name="csrf-token">` and sends as `X-CSRF-TOKEN` header. If the meta tag is absent, the fetch is silently skipped (`if (csrfMeta)` guard at line 421). Correct.

## 2. XSS — Blade escaping

- All user/DB output uses `{{ }}` (escaped). No `{!! !!}` with dynamic content anywhere.
- `Str::limit()` output at line 27 is auto-escaped by `{{ }}`.
- SVG aria-label at line 200 iterates `$data` array (server-derived, not raw user input). Safe.
- `<title>` inside SVG circles (line 211) also uses `{{ }}`. Safe.

## 3. Inline handlers with dynamic content

- No `onclick`, `onchange`, or similar inline JS handlers with dynamic values. All JS is attached via `addEventListener` in `@push('scripts')`.

## 4. Data attributes safe

- `data-kpis-url` (line 60): set via `route()` helper, not user input. Safe.
- `data-cycle`, `data-mod-ciclo`, `data-origen`: all set from server-side variables, not raw user input. Safe.
- JS reads `data-kpis-url` via `getAttribute()` (line 365) and appends `encodeURIComponent(label)`. Correctly encoded.

## 5. Secrets exposure

- No API keys, passwords, tokens, or secrets in the template. All sensitive values come from environment config.

## 6. Fetch URLs safe

- KPI fetch URL built from `data-kpis-url` attribute + `encodeURIComponent(label)` (line 365). No injection vector.
- Session-sync POST to `/academia/set-ciclo` (line 422): hardcoded path, CSRF-protected. Safe.
- `history.replaceState` (line 417): uses `encodeURIComponent(label)`. Safe.

## 7. Additional checks

- No `eval()`, `innerHTML` with user data, or `document.write()`.
- `textContent` used everywhere for DOM updates (lines 391, 399, 407, 413). Correctly prevents XSS in dynamic updates.
- No external script imports or `<script src>` with untrusted sources.

---

**Summary:** No CSRF, XSS, injection, or secrets issues. The file follows secure Blade and JS practices throughout.
