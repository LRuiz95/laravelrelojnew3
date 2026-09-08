---
description: Ejecuta el nivel de testing correspondiente y el protocolo de bug fixing
agent: tester
---

Ejecuta testing para: $ARGUMENTS

Aplica el nivel de testing que declara el Task Boundary activo
(`.opencode/policies/test-levels.md`). Si es un bug fix, confirma primero
que existe un test que reproduce el bug (créalo si no existe) antes de
correr la suite correspondiente. Escala el nivel si detectas que el
cambio afecta más de lo declarado.

Escribe el resultado en `.opencode/state/test-results.md` con el formato
de `.opencode/policies/evidence.md`. Si todo pasa, encadena a `/security`
(si el Task Boundary lo requiere) o directo a `/review`.
