<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['icon', 'label', 'value', 'color']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['icon', 'label', 'value', 'color']); ?>
<?php foreach (array_filter((['icon', 'label', 'value', 'color']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="kpi-card card h-100 border-0 shadow-sm" style="--bs-card-border-color: var(--bs-<?php echo e($color); ?>-subtle);">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: var(--bs-<?php echo e($color); ?>-subtle);">
                        <i class="<?php echo e($icon); ?> fs-4 text-<?php echo e($color); ?>"></i>
                    </div>
                    <h6 class="mb-0 text-muted fw-semibold"><?php echo e($label); ?></h6>
                </div>
                <div class="kpi-value fs-2 fw-bold text-<?php echo e($color); ?>" data-stat-value><?php echo e($value); ?></div>
            </div>
            <?php echo e($slot ?? ''); ?>

        </div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\components\stat-card.blade.php ENDPATH**/ ?>