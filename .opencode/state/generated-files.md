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

## Ejemplo

```
- storage/debug/attendance-sync-dump.json — creado por integration — inspeccionar payload duplicado — TASK-2026-0912
- database/scratch_check_orphans.sql — creado por data-integrity — query puntual de verificación de huérfanos — TASK-2026-0912
```

(sin archivos temporales registrados para la tarea activa)