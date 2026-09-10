<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['variant' => 'default', 'size' => 'md', 'label', 'dot' => false, 'class' => '']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['variant' => 'default', 'size' => 'md', 'label', 'dot' => false, 'class' => '']); ?>
<?php foreach (array_filter((['variant' => 'default', 'size' => 'md', 'label', 'dot' => false, 'class' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $variantClasses = [
        'default' => 'bg-secondary text-white',
        'primary' => 'bg-primary',
        'success' => 'bg-success',
        'warning' => 'bg-warning text-dark',
        'danger' => 'bg-danger',
        'info' => 'bg-info text-dark',
        'purple' => 'bg-purple',
        'pink' => 'bg-pink',
        'teal' => 'bg-teal',
        'orange' => 'bg-warning',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-sm',
        'lg' => 'px-3 py-1 text-base',
    ];

    $baseClass = 'badge rounded-pill d-inline-flex align-items-center gap-1';
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $dotClass = $dot ? 'badge-with-dot' : '';
?>

<span class="<?php echo e($baseClass); ?> <?php echo e($variantClass); ?> <?php echo e($sizeClass); ?> <?php echo e($dotClass); ?> <?php echo e($class); ?>">
    <?php echo e($label); ?>

</span><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\components\badge.blade.php ENDPATH**/ ?>