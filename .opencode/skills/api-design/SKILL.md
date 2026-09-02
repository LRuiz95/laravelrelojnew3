---
name: api-design
description: Design consistent HTTP/API contracts including resources, errors, pagination, idempotency, auth and documentation.
compatibility: opencode
---
# API Design

## Contract-first principles

- Preserve existing clients before changing response shapes.
- Use predictable resource naming and HTTP semantics.
- Standardize success and error envelopes across a service.
- Document validation failures and authorization failures distinctly.
- Prefer cursor pagination when datasets or traversal requirements justify it; use page pagination when the domain is simpler.
- Make retries safe for operations that may be repeated by clients.
- Never claim an API is idempotent merely because it is using PUT; verify the actual server behavior.

## Errors
Prefer one documented error structure across endpoints. RFC 7807-style problem details are a valid option when compatible with the project.

Minimum useful fields:
- machine-readable type/code
- human-readable title/message
- HTTP status
- stable field/error details when validation fails
- correlation/request identifier when the platform supports it

## Authentication and authorization

Separate identity verification from permission checks. Document both.

## Versioning

Choose one project-wide approach: URL versioning, header/media-type versioning, or compatibility without explicit versioning. Do not mix strategies accidentally.

## Output
Before implementation produce:
- endpoint list
- request schema
- response schema
- error schema
- auth rules
- pagination/filter/sort semantics
- compatibility impact
