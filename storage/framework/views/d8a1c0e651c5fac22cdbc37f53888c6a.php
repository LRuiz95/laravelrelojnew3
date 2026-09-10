<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['id', 'title', 'size' => 'md', 'closeable' => true]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['id', 'title', 'size' => 'md', 'closeable' => true]); ?>
<?php foreach (array_filter((['id', 'title', 'size' => 'md', 'closeable' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $sizeClasses = [
        'sm' => 'modal-sm',
        'md' => '',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        'full' => 'modal-fullscreen',
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
?>

<div class="offcanvas offcanvas-end" tabindex="-1" id="<?php echo e($id); ?>" aria-labelledby="<?php echo e($id); ?>Label">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="<?php echo e($id); ?>Label"><?php echo e($title); ?></h5>
        <?php if($closeable): ?>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        <?php endif; ?>
    </div>
    <div class="offcanvas-body">
        <?php echo e($slot); ?>

    </div>
</div><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/components/drawer.blade.php ENDPATH**/ ?>