# Niveles de testing — no siempre "suite completa"

`architect` asigna el nivel en `state/plan.md` para tareas COMPLEJAS.
`team-lead` lo asigna directamente para tareas SIMPLE.

| Nivel | Cuándo aplica | Qué corre `tester` |
|---|---|---|
| 0 | Solo documentación (`docs`) | Ningún test. |
| 1 | CSS/visual simple, copy, textos | Tests de UI directamente relacionados, si existen. |
| 2 | Cambio en JS, Blade, un Controller aislado | Tests específicos del módulo + tests relacionados por dependencia directa. |
| 3 | Cambios en BD, auth, permisos, o cualquier cosa que toque `integration`/`data-integrity` | Suite relevante amplia (todo el módulo + integraciones conocidas). |
| 4 | Crítico: seguridad, sincronización entre sistemas, migraciones | Suite completa del proyecto. |

## Regla para bug fixing (aplica en cualquier nivel)

```
reproduce → test que falla → fix → test pasa → regresión del módulo afectado
```

Un bug nunca se da por resuelto solo porque el síntoma desaparezca
visualmente — el test que reproduce el bug debe existir y pasar.

## Regla de escalamiento automático de nivel

Si durante la implementación `tester` detecta que el cambio toca más de lo
que el Task Boundary declaraba (por ejemplo, un cambio "Nivel 2" termina
afectando una tabla compartida con Firebird), debe **escalar el nivel a 3
o 4** y notificarlo en `state/test-results.md`, no quedarse en el nivel
original asignado inicialmente.
