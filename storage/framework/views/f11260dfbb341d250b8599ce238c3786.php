<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'ciclo' => null,
    'ciclos' => collect(),
    'showBadge' => true,
    'totales' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'ciclo' => null,
    'ciclos' => collect(),
    'showBadge' => true,
    'totales' => null,
]); ?>
<?php foreach (array_filter(([
    'ciclo' => null,
    'ciclos' => collect(),
    'showBadge' => true,
    'totales' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $route = request()->url();
    $params = request()->except(['ciclo_principal']);
?>

<form method="GET" action="<?php echo e($route); ?>" class="d-flex align-items-center gap-2 flex-wrap">
    <?php $__currentLoopData = $params; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(is_array($value)): ?>
            <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($v); ?>">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <div class="input-group input-group-sm" style="max-width: 280px;">
        <span class="input-group-text">
            <i class="bi bi-calendar3"></i>
        </span>
        <select name="ciclo_principal" class="form-select" required aria-label="Seleccionar ciclo escolar">
            <?php $__empty_1 = true; $__currentLoopData = $ciclos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($c->label); ?>" <?php echo e($ciclo && $ciclo->inicial == $c->inicial && $ciclo->final == $c->final && $ciclo->periodo == $c->periodo ? 'selected' : ''); ?>>
                    <?php echo e($c->label); ?>

                    <?php if($c->activo): ?>
                        ★
                    <?php endif; ?>
                    <?php if($c->fecha_inicial && $c->fecha_final): ?>
                        (<?php echo e($c->fecha_inicial->format('d/m')); ?>–<?php echo e($c->fecha_final->format('d/m/Y')); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <option value="" disabled selected>Sin ciclos disponibles</option>
            <?php endif; ?>
        </select>
    </div>

    <?php if($showBadge && $ciclo): ?>
        <span class="badge <?php echo e($ciclo->activo ? 'bg-success' : 'bg-secondary'); ?> rounded-pill">
            <?php echo e($ciclo->activo ? 'Activo' : 'Inactivo'); ?>

        </span>
    <?php endif; ?>

    <?php if($totales !== null): ?>
        <small class="text-muted">
            de <?php echo e(number_format($totales)); ?> en total
        </small>
    <?php endif; ?>

    <?php if($ciclos->isNotEmpty()): ?>
        <button type="submit" class="btn btn-sm btn-primary" title="Aplicar ciclo seleccionado">
            <i class="bi bi-arrow-repeat"></i>
        </button>
    <?php endif; ?>
</form>
<?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\components\academia\ciclo-selector.blade.php ENDPATH**/ ?>