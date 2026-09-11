
<div class="empty-state">
    <i class="bi <?php echo e($icon ?? 'bi-folder2-open'); ?>"></i>
    <div class="es-title"><?php echo e($title ?? 'Sin datos'); ?></div>
    <?php if(isset($desc)): ?>
        <div class="es-desc"><?php echo e($desc); ?></div>
    <?php endif; ?>
    <?php if(!empty($cta)): ?>
        <?php if(!empty($ctaLink)): ?>
            <a href="<?php echo e($cta['url']); ?>" class="btn btn-outline-secondary"><?php echo e($cta['label']); ?></a>
        <?php else: ?>
            <button type="button" class="btn btn-outline-secondary" <?php echo e($cta['attrs'] ?? ''); ?>><?php echo e($cta['label']); ?></button>
        <?php endif; ?>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/partials/empty-state.blade.php ENDPATH**/ ?>