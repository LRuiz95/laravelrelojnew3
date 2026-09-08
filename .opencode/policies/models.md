# Modelos y fallback (política de team-lead, no del frontmatter del agente)

> OpenCode V2 no soporta `model_fallback` como campo del agente. Cada
> agente tiene UN modelo configurado (`model: provider/model`). El
> fallback se implementa como protocolo que `team-lead` ejecuta cuando
> detecta un fallo o una respuesta de baja confianza.

## Modelo asignado por agente (Fase 1 y 2)

| Agente | model (frontmatter real) |
|---|---|
| team-lead | opencode/nemotron-3.5-lightning-free |
| architect | opencode/nemotron-3-ultra-free |
| laravel | opencode/nemotron-3.5-lightning-free |
| frontend | opencode/mimo-v2.5-free |
| mysql | opencode/nemotron-3.5-lightning-free |
| firebird | opencode/mimo-v2.5-free |
| integration | opencode/nemotron-3-ultra-free |
| data-integrity | opencode/nemotron-3-ultra-free |
| security | opencode/nemotron-3-ultra-free |
| tester | opencode/nemotron-3.5-lightning-free |
| reviewer | opencode/nemotron-3-ultra-free |
| docs | opencode/big-pickle |
| cleanup | opencode/big-pickle |

Asignación por tipo de trabajo, no solo por agente — útil como referencia
cuando el catálogo gratuito cambie:

- **Razonamiento profundo** (architect, integration, data-integrity,
  security, reviewer) → Nemotron 3 Ultra Free.
- **Código/implementación** (laravel, mysql) → Nemotron 3.5 Lightning
  Free.
- **Rápido/UI** (frontend, firebird) → MiMo V2.5 Free.
- **Documentación** (docs) → Big Pickle.
- **Mantenimiento/housekeeping** (cleanup) → Big Pickle — es una tarea
  mecánica (leer, archivar, confirmar, resetear), no requiere
  razonamiento profundo.

## Nivel A — fallback por fallo técnico

Si un agente no responde, agota su cuota, o el proveedor lo retira del
catálogo gratuito, `team-lead` reintenta la misma tarea reconfigurando
temporalmente el `model` de ese agente (vía config, no vía un campo que no
existe) en esta cadena:

```
opencode/<modelo primario>
   ↓ (falla)
qwen/qwen3-coder:free   (o qwen/qwen3-next-80b-a3b-instruct:free para tareas de razonamiento)
   ↓ (falla)
openrouter/free          (autoselección de OpenRouter)
```

Esto es una acción manual/de configuración que team-lead debe señalar al
usuario ("Nemotron 3 Ultra Free no responde, cambia `security.model` a
`qwen/qwen3-coder:free` en su frontmatter"), no algo que ocurra solo.

## Nivel B — baja confianza (heurística, no mecanismo automático)

**Importante:** OpenCode no expone un puntaje de confianza del modelo.
Esto es un protocolo de comportamiento que cada agente debe seguir por
instrucción, no una capacidad técnica verificable:

- Si un agente no tiene certeza sobre su propio resultado (schema
  incompleto, contexto insuficiente, ambigüedad en la tarea), debe
  declararlo explícitamente en su evidencia: `confidence: low — <razón>`.
- `team-lead`, al ver `confidence: low` en `state/findings.md` o
  similares, decide si re-ejecuta la tarea con otro modelo de la cadena
  del Nivel A o si escalca a `HUMAN REVIEW` directamente.
- No existe un umbral numérico automático — es criterio de `team-lead`
  basado en lo que el propio agente reportó.

## Actualización de este archivo

Los modelos gratuitos son temporales por definición. Revisar este archivo
periódicamente (o vía `/health`) y actualizar la tabla si el catálogo
cambia — los agentes no necesitan cambios, solo su campo `model:`.
