<?php $__env->startSection('title', 'Conflictos de Aula'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Conflictos de Aula</h1>
    <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar me-1"></i> Cambiar ciclo
    </a>
</div>

<?php if(empty($conflictos)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
            <p>No se detectaron conflictos de aula en el ciclo <?php echo e($ciclo->label); ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-header bg-danger-subtle">
            <span class="fw-bold text-danger">Se detectaron <?php echo e(count($conflictos)); ?> conflicto(s) de aula</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Sesión</th>
                            <th>Sede</th>
                            <th>Edificio</th>
                            <th>Aula</th>
                            <th>Clases en conflicto</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $conflictos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$c->dia-1]); ?></td>
                                <td><?php echo e($c->sesion); ?></td>
                                <td><?php echo e($c->id_campus); ?></td>
                                <td><?php echo e($c->edificio); ?></td>
                                <td><?php echo e($c->aula); ?></td>
                                <td><span class="badge bg-danger"><?php echo e($c->total); ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalleConflicto(<?php echo e($c->dia); ?>, <?php echo e($c->sesion); ?>, '<?php echo e($c->id_campus); ?>', '<?php echo e($c->edificio); ?>', '<?php echo e($c->aula); ?>')">
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\horarios\aula.blade.php ENDPATH**/ ?>