# Severidad — controla el flujo, no solo etiqueta el reporte

## Niveles

| Severidad | Ejemplos | Acción obligatoria |
|---|---|---|
| LOW | nombre poco descriptivo, falta de comentario, estilo | Solo se registra en `state/security-results.md`. No bloquea. |
| MEDIUM | falta de rate limit, mensaje de error verboso, log innecesario | Pasa por `reviewer`. |
| HIGH | XSS reflejado, IDOR en endpoint de bajo impacto, mass assignment parcial | Pasa por `security` **y** `reviewer`. |
| CRITICAL | SQL injection, IDOR sobre datos sensibles, bypass de auth, secreto expuesto | `security` + `reviewer` + **HUMAN APPROVAL obligatorio**, sin excepción. |

## Regla de propagación

Si un mismo hallazgo genera clasificaciones distintas entre las dos
pasadas de `security` (ver `.opencode/policies/consensus.md`), se usa
siempre la severidad más alta reportada como la vigente hasta que un
humano decida lo contrario.

## Ejemplo de asignación

```
Hallazgo: endpoint /api/employees/{id}/attendance no valida que
          {id} pertenezca al usuario autenticado.
Severidad: HIGH (IDOR sobre datos de asistencia — no es CRITICAL porque
           no expone credenciales ni datos financieros directamente,
           pero sí datos personales de terceros).
Acción: security + reviewer. No requiere detener todo el pipeline,
        pero no se marca DONE sin que ambos lo aprueben explícitamente.
```

Un cambio con al menos un hallazgo CRITICAL nunca llega a DONE sin
aprobación humana, sin importar cuántos otros hallazgos LOW/MEDIUM haya.
