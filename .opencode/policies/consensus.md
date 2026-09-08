# Consenso en doble pasada (security y reviewer)

No es un resultado binario APPROVED/REJECTED/HUMAN. Depende de si las dos
pasadas coinciden y, si no coinciden, de qué tan grave es la discrepancia.

```
                REVIEW / SECURITY DOUBLE PASS
                          │
              ¿Resultados iguales (mismo hallazgo,
                    misma severidad)?
                 /                        \
               Sí                          No
                │                           │
                ▼                           ▼
        Proceder según la           ¿La discrepancia es
        severidad más alta          de severidad compatible
        encontrada (ver             (ej. LOW vs MEDIUM) o
        severity.md)                CRITICAL en alguna de
                                     las dos pasadas?
                                        /            \
                                  Compatible       CRITICAL
                                       │               │
                                       ▼               ▼
                                  CONSENSUS:       HUMAN REVIEW
                                  se procede       obligatorio,
                                  con la           sin excepción
                                  severidad
                                  más alta
```

## Reglas de desempate

- Si ambas pasadas aprueban sin hallazgos → `APPROVED`.
- Si ambas pasadas coinciden en un hallazgo (mismo ítem, severidad similar)
  → se procede según esa severidad (`severity.md`).
- Si discrepan pero ninguna alcanza HIGH/CRITICAL → `CONSENSUS`, se
  registra la discrepancia mínima en el estado pero no bloquea.
- Si **cualquiera** de las dos pasadas marca CRITICAL (aunque la otra no lo
  vea) → `HUMAN REVIEW` obligatorio. Nunca se descarta un CRITICAL solo
  porque el otro modelo no lo detectó — el falso negativo de un modelo es
  precisamente el riesgo que la doble pasada existe para mitigar.
- El agente nunca desempata solo — la función de comparar es mecánica
  (aplicar estas reglas), no un tercer juicio de valor del propio agente.

## Ejemplo

```
security (pasada 1, Nemotron): "duplicación leve de validación" → LOW
security (pasada 2, Qwen):     "aceptable, sin hallazgo"        → (nada)

→ Discrepancia de severidad LOW vs ninguna: compatible.
→ CONSENSUS. Se registra la observación LOW y se continúa el flujo.
```

```
security (pasada 1, Nemotron): "endpoint sin validar propiedad
                                 del recurso" → HIGH (posible IDOR)
security (pasada 2, Qwen):     "no encuentra el problema"

→ Discrepancia CRITICAL/HIGH vs nada.
→ HUMAN REVIEW obligatorio, con el detalle de ambas pasadas.
```
