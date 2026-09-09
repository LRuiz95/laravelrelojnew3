# generated-files.md

> Registro de archivos **temporales/scratch** creados por cualquier agente
> durante la tarea activa: scripts de diagnóstico puntuales, exports,
> dumps de datos, logs de depuración, reportes de coverage sueltos, etc.

> **No incluye** código de producción, tests permanentes, ni nada que ya
> se haya declarado en `Changed:` dentro de `.opencode/policies/evidence.md`
> — eso lo gestiona `reviewer`/git, no `cleanup`.

> Cualquier agente que cree un archivo de este tipo debe añadir una línea
> aquí, además de mencionarlo en el campo `Generated (temporal):` de su
> propia evidencia. `cleanup` lo lee al cerrar la tarea, confirma cada
> entrada contra el Task Boundary y lo resetea después de limpiar.

## Formato de entrada

```
- <ruta del archivo> — creado por <agente> — <para qué> — TASK-<id>
```

_No hay archivos temporales activos._
