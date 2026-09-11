<?php $__env->startSection('title', 'Notificaciones'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="h5 mb-1">Centro de notificaciones</h2>
        <p class="text-muted mb-0">Avisos importantes sobre la red, sincronizaciones y asistencias.</p>
    </div>
    <a href="<?php echo e(route('operations.queue')); ?>" class="btn btn-outline-secondary"><i class="bi bi-list-task me-1"></i> Ver cola</a>
</div>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e($notification['url'] ?? '#'); ?>" class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3">
                <span class="avatar is-sm"><?php echo e($notification['initials']); ?></span>
                <span class="flex-grow-1"><strong class="d-block"><?php echo e($notification['title']); ?></strong><span class="text-muted small d-block mt-1"><?php echo e($notification['desc']); ?></span><span class="text-tertiary-token small"><?php echo e($notification['category']); ?> · <?php echo e($notification['time']); ?></span></span>
                <?php if(!$notification['read']): ?><span class="notify-unread-dot mt-2"></span><?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="notify-empty"><i class="bi bi-bell"></i><strong>No hay notificaciones</strong><span class="notify-empty-sub">Estás al día.</span></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/operations/notifications.blade.php ENDPATH**/ ?>