<?php $__env->startSection('title', 'Academia - Sin ciclos configurados'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="text-center" style="max-width: 480px;">
        <div class="mb-4">
            <i class="bi bi-calendar-x" style="font-size: 4rem; color: var(--bs-secondary);"></i>
        </div>
        <h2 class="h4 mb-3">No hay ciclos escolares configurados</h2>
        <p class="text-secondary mb-4">
            Para usar el módulo de Academia primero debes crear al menos un ciclo escolar.
            Los ciclos definen los periodos académicos (año inicial, año final y periodo).
        </p>
        <a href="<?php echo e(route('academia.ciclos.create')); ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-plus-lg me-1"></i> Crear primer ciclo
        </a>
        <div class="mt-4">
            <a href="<?php echo e(route('dashboard')); ?>" class="text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Volver al panel
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\empty-ciclos.blade.php ENDPATH**/ ?>