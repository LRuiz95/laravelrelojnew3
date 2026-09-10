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
        <?php $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($attendance->recorded_at->format('d/m/Y H:i:s')); ?></td>
                <td><?php echo e($attendance->employee?->name ?? 'Sin asignar'); ?></td>
                <td><?php echo e($attendance->user_id); ?></td>
                <td><?php echo e($attendance->stateLabel()); ?></td>
                <td><?php echo e($attendance->verificationTypeLabel()); ?></td>
                <td><?php echo e($attendance->device?->name ?? ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\attendances\print.blade.php ENDPATH**/ ?>