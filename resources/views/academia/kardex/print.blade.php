<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex - {{ $alumno->nombre_completo }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000 !important; }
            .table { font-size: 10px; }
        }
        body { font-size: 11px; }
        .table th, .table td { padding: 4px 6px !important; }
        .card { border: 1px solid #000; }
    </style>
</head>
<body class="p-3">
    <div class="no-print text-center mb-3">
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir</button>
        <button class="btn btn-secondary ms-2" onclick="window.close()">Cerrar</button>
    </div>

    <div class="text-center mb-3">
        <h4 class="mb-1"><strong>UNIVERSIDAD TECNOLÓGICA GENERAL MARIANO ESCOBEDO</strong></h4>
        <h5 class="mb-1">KARDEX ACADÉMICO</h5>
        <p class="mb-0">Ciclo: <strong>{{ $ciclo->label }}</strong></p>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <p class="mb-1"><strong>Alumno:</strong> {{ $alumno->nombre_completo }}</p>
            <p class="mb-1"><strong>Control:</strong> {{ $alumno->numero_alumno }}</p>
            <p class="mb-1"><strong>CURP:</strong> {{ $alumno->curp }}</p>
        </div>
        <div class="col-md-6 text-end">
            <p class="mb-1"><strong>Ciclo:</strong> {{ $ciclo->label }} ({{ $ciclo->fechaInicialFormateada }} - {{ $ciclo->fechaFinalFormateada }})</p>
            <p class="mb-1"><strong>Fecha:</strong> {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th rowspan="2" style="vertical-align: middle; width: 40%;">Materia</th>
                <th rowspan="2" style="vertical-align: middle;">Sem</th>
                <th colspan="6" class="text-center">Evaluaciones Parciales</th>
                <th rowspan="2" style="vertical-align: middle;">Final</th>
                <th rowspan="2">Estado</th>
            </tr>
            <tr>
                <th class="text-center">P1</th>
                <th class="text-center">P2</th>
                <th class="text-center">P3</th>
                <th class="text-center">CF</th>
                <th class="text-center">EXR</th>
                <th class="text-center">EXRS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kardex['materias'] as $clave => $m)
                <tr>
                    <td class="fw-semibold small">{{ $m['nombre'] ?? $clave }}</td>
                    <td>{{ $m['semestre'] ?? '' }}</td>
                    <td class="text-center">{{ $m['P1'] ?? '—' }}</td>
                    <td class="text-center">{{ $m['P2'] ?? '—' }}</td>
                    <td class="text-center">{{ $m['P3'] ?? '—' }}</td>
                    <td class="text-center">{{ $m['CF'] ?? '—' }}</td>
                    <td class="text-center">{{ $m['EXR'] ?? '—' }}</td>
                    <td class="text-center">{{ $m['EXRS'] ?? '—' }}</td>
                    <td class="text-center fw-bold">{{ $m['CT'] ?? '—' }}</td>
                    <td>
                        <span style="font-size: 10px;">{{ $m['ESTADO'] }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row mt-3">
        <div class="col-md-3 text-center">
            <p class="mb-0"><strong>{{ $kardex['resumen']['aprobadas'] }}</strong></p>
            <small>Aprobadas</small>
        </div>
        <div class="col-md-3 text-center">
            <p class="mb-0"><strong>{{ $kardex['resumen']['reprobadas'] }}</strong></p>
            <small>Reprobadas</small>
        </div>
        <div class="col-md-3 text-center">
            <p class="mb-0"><strong>{{ $kardex['resumen']['sin_derecho'] }}</strong></p>
            <small>Sin derecho</small>
        </div>
        <div class="col-md-3 text-center">
            <p class="mb-0"><strong>{{ $kardex['resumen']['promedio'] ?? '—' }}</strong></p>
            <small>Promedio</small>
        </div>
    </div>

    <div class="mt-4 text-center text-muted small">
        <p>Documento generado automáticamente el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Universidad Tecnológica General Mariano Escobedo - Sistema Académico</p>
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>