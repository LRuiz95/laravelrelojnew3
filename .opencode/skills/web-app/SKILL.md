---
name: web-app
description: Apply disciplined patterns for web applications, APIs, MVC layers, validation, sessions and frontend/backend contracts.
compatibility: opencode
---
# Web Application

Preserve the application architecture already present.

Trace request -> route -> controller/handler -> service/use case -> persistence -> response.

Validate inputs at the boundary, authorize actions explicitly, keep business rules out of presentation when the project architecture supports a service/use-case layer, and preserve API contracts unless a breaking change is planned.
