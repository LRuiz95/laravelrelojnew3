<?php $__env->startSection('title', 'Cursos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Cursos</h1>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if($cursos->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay cursos registrados para este ciclo</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>Materia</th>
                            <th>Maestro(s)</th>
                            <th>Nivel</th>
                            <th>Turno</th>
                            <th>Sede</th>
                            <th class="text-center">Alumnos</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $cursos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($c->clave_curso); ?></td>
                                <td><?php echo e($c->nombre_curso); ?></td>
                                <td><?php echo e($c->materias->first()?->materia?->nombre_asignatura ?? 'Materia no asignada'); ?></td>
                                <td class="small">
                                    <?php $__empty_1 = true; $__currentLoopData = $c->docentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $docente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div><?php echo e($docente->nombre_completo ?: $docente->clave_profesor); ?></div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <span class="text-muted">Sin maestro asignado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($c->nivelRel?->descripcion ?? $c->nivel); ?></td>
                                <td><?php echo e($c->turno_nombre); ?></td>
                                <td><?php echo e($c->sede?->descripcion ?? $c->id_campus); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?php echo e($c->alumnos_count); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge--status <?php echo e($c->activo ? 'badge--active' : 'badge--inactive'); ?>">
                                        <?php echo e($c->activo ? 'Activo' : 'Inactivo'); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('academia.cursos.show', $c)); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo e($cursos->withQueryString()->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\cursos\index.blade.php ENDPATH**/ ?>