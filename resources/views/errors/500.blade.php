<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del sistema</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4f6f8; color: #24313d; }
        main { width: min(92vw, 560px); padding: 2rem; background: #fff; border: 1px solid #dce2e7; border-radius: 12px; box-shadow: 0 12px 32px rgba(35, 49, 61, .08); text-align: center; }
        h1 { margin: 0 0 .75rem; font-size: 1.5rem; }
        p { color: #5c6b76; line-height: 1.5; }
        code { display: inline-block; margin-top: .5rem; padding: .25rem .5rem; border-radius: 6px; background: #eef2f5; color: #465663; }
        a { display: inline-block; margin-top: 1rem; padding: .65rem 1rem; border-radius: 7px; background: #2563eb; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <h1>No se pudo completar la solicitud</h1>
        <p>{{ $message ?? 'Ocurrió un problema inesperado. El evento fue registrado para su revisión.' }}</p>
        @if (!empty($errorId))
            <code>Referencia: {{ $errorId }}</code>
        @endif
        <br>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Volver</a>
    </main>
</body>
</html>
