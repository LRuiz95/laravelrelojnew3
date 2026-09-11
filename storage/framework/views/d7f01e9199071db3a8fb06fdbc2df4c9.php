<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'action' => null,
    'method' => 'GET',
    'clearUrl' => null,
    'clearLabel' => 'Limpiar',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'action' => null,
    'method' => 'GET',
    'clearUrl' => null,
    'clearLabel' => 'Limpiar',
]); ?>
<?php foreach (array_filter(([
    'action' => null,
    'method' => 'GET',
    'clearUrl' => null,
    'clearLabel' => 'Limpiar',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<form method="<?php echo e($method); ?>" action="<?php echo e($action); ?>" class="row g-3 align-items-end">
    <?php echo e($slot); ?>


    <?php if($clearUrl): ?>
        <div class="col-auto">
            <a href="<?php echo e($clearUrl); ?>" class="btn btn-outline-secondary">
                <?php echo e($clearLabel); ?>

            </a>
        </div>
    <?php endif; ?>
</form>
<?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/components/filter-bar.blade.php ENDPATH**/ ?>