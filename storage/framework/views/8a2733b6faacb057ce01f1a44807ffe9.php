<?php $__env->startSection('title', 'Historial Académico'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Historial Académico</h1>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.kardex.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Buscar alumno</label>
                <input type="text" name="buscar" class="form-control" placeholder="Control, nombre, CURP..." value="<?php echo e(request('buscar')); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="<?php echo e(route('academia.kardex.historial')); ?>" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<?php if($alumno): ?>
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold"><?php echo e($alumno->nombre_completo); ?> | Control: <?php echo e($alumno->numero_alumno); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if(empty($historial)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-clock-history fs-1 mb-2"></i>
            <p>No hay historial académico registrado</p>
        </div>
    </div>
<?php else: ?>
    <?php $__currentLoopData = $historial; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cicloLabel => $materias): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Ciclo: <?php echo e($cicloLabel); ?></span>
                    <?php
                        $cicloMaterias = collect($materias);
                        $aprobadas = $cicloMaterias->where('ESTADO', 'APROBADO')->count();
                        $reprobadas = $cicloMaterias->where('ESTADO', 'REPROBADO')->count();
                        $promedio = $cicloMaterias->where('CT', '!=', null)->where('CT', '!=', '')->avg('CT');
                    ?>
                    <div class="d-flex gap-3 small">
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i><?php echo e($aprobadas); ?> aprobadas</span>
                        <span class="badge bg-danger"><i class="bi bi-x me-1"></i><?php echo e($reprobadas); ?> reprobadas</span>
                        <?php if($promedio): ?>
                            <span class="badge bg-info">Promedio: <?php echo e(number_format($promedio, 2)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>P3</th>
                                <th>CF</th>
                                <th>EXR</th>
                                <th>CT</th>
                                <th>Literal</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $materias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($m['nombre'] ?? $m['clave']); ?></td>
                                    <td class="text-center"><?php echo e($m['P1'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($m['P2'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($m['P3'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($m['CF'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($m['EXR'] ?? '—'); ?></td>
                                    <td class="text-center fw-semibold"><?php echo e($m['CT'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($m['LITERAL']); ?></td>
                                    <td>
                                        <?php
                                            $estadoClass = match($m['ESTADO']) {
                                                'APROBADO' => 'bg-success',
                                                'REPROBADO' => 'bg-danger',
                                                'SIN DERECHO' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?php echo e($estadoClass); ?>">
                                            <?php echo e($m['ESTADO']); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\kardex\historial.blade.php ENDPATH**/ ?>