

<?php $__env->startSection('title', 'Puestos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Puestos</h1>
        <p class="text-muted mb-0">Catálogo de puestos y su vinculación con áreas.</p>
    </div>
    <?php if(auth()->user()->isAdmin()): ?>
        <a href="<?php echo e(route('puestos.create')); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo puesto
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
                        <th>Área</th>
                        <th>Empleados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $puestos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $puesto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><span class="fw-semibold"><?php echo e($puesto->identificador); ?></span></td>
                            <td><?php echo e($puesto->descripcion ?? '—'); ?></td>
                            <td><?php echo e($puesto->area?->identificador ?? '—'); ?></td>
                            <td><?php echo e($puesto->empleados->count()); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('puestos.show', $puesto)); ?>" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <a href="<?php echo e(route('puestos.edit', $puesto)); ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        <form action="<?php echo e(route('puestos.destroy', $puesto)); ?>" method="POST" onsubmit="return confirm('¿Deseas eliminar este puesto?')">
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
                            <td colspan="5" class="text-center text-muted py-4">No hay puestos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/puestos/index.blade.php ENDPATH**/ ?>