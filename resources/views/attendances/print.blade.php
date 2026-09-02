<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de asistencias</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #eee; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimir o guardar como PDF</button>
    <h1>Reporte de asistencias</h1>
    <table>
        <thead><tr><th>Fecha y hora</th><th>Empleado</th><th>ID</th><th>Marcado</th><th>Verificación</th><th>Dispositivo</th></tr></thead>
        <tbody>
        @foreach ($attendances as $attendance)
            <tr>
                <td>{{ $attendance->recorded_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $attendance->employee?->name ?? 'Sin asignar' }}</td>
                <td>{{ $attendance->user_id }}</td>
                <td>{{ $attendance->stateLabel() }}</td>
                <td>{{ $attendance->verificationTypeLabel() }}</td>
                <td>{{ $attendance->device?->name ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>