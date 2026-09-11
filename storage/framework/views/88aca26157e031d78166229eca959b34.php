

<?php $__env->startSection('title', 'Áreas'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Áreas</h1>
        <p class="text-muted mb-0">Administración y organización por áreas responsables.</p>
    </div>
    <?php if(auth()->user()->isAdmin()): ?>
        <a href="<?php echo e(route('areas.create')); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva área
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Identificador</th>
                        <th>Descripción</th>
                        <th>Empleado responsable</th>
                        <th>Puestos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $areas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><span class="fw-semibold"><?php echo e($area->identificador); ?></span></td>
                            <td><?php echo e($area->descripcion ?? '—'); ?></td>
                            <td><?php echo e($area->empleadoResponsable?->name ?? '—'); ?></td>
                            <td><?php echo e($area->puestos->count()); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('areas.show', $area)); ?>" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <a href="<?php echo e(route('areas.edit', $area)); ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        <form action="<?php echo e(route('areas.destroy', $area)); ?>" method="POST" onsubmit="return confirm('¿Deseas eliminar esta área?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay áreas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/areas/index.blade.php ENDPATH**/ ?>