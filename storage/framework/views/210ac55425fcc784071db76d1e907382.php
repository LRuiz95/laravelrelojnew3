
<?php
    $segments = $segments ?? [];
    $total = $total ?? array_sum(array_column($segments, 'value'));
    $r = 70;
    $C = 2 * M_PI * $r;
    $css = [
        'blue'     => 'var(--cat-blue)',
        'orange'   => 'var(--cat-orange)',
        'purple'   => 'var(--cat-purple)',
        'pink'     => 'var(--cat-pink)',
        'red'      => 'var(--cat-red)',
        'green'    => 'var(--cat-green)',
        'amber'    => 'var(--cat-amber)',
        'lavender' => 'var(--cat-lavender)',
        'gray'     => 'var(--cat-gray)',
    ];
    $offset = 0;
?>
<div class="donut-wrap">
    <div class="donut-box">
        <svg viewBox="0 0 180 180">
            <circle cx="90" cy="90" r="<?php echo e($r); ?>" fill="none"
                    style="stroke:var(--border)" stroke-width="22"/>
            <?php $__currentLoopData = $segments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $len = $total > 0 ? ($seg['value'] / $total) * $C : 0;
                    $color = $css[$seg['color']] ?? $seg['color'];
                ?>
                <circle class="donut-seg" cx="90" cy="90" r="<?php echo e($r); ?>" fill="none"
                        stroke="<?php echo e($color); ?>" stroke-width="22"
                        stroke-dasharray="<?php echo e(round($len, 2)); ?> <?php echo e(round($C - $len, 2)); ?>"
                        stroke-dashoffset="<?php echo e(round(-$offset, 2)); ?>"
                        data-percent="<?php echo e($total > 0 ? round(($seg['value'] / $total) * 100, 1) : 0); ?>">
                    <title><?php echo e($seg['label']); ?> · <?php echo e($seg['value']); ?> (<?php echo e($total > 0 ? round(($seg['value'] / $total) * 100, 1) : 0); ?>%)</title>
                </circle>
                <?php $offset += $len; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </svg>
        <div class="donut-hole">
            <div class="dh-value"><?php echo e($total); ?></div>
            <div class="dh-label">Marcados</div>
        </div>
    </div>
    <div class="donut-legend">
        <?php $__empty_1 = true; $__currentLoopData = $segments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="donut-legend-item">
                <span class="dl-dot" style="background:<?php echo e($css[$seg['color']] ?? $seg['color']); ?>"></span>
                <span class="dl-label"><?php echo e($seg['label']); ?></span>
                <span class="dl-value"><?php echo e($seg['value']); ?>

                    <span class="text-tertiary-token" style="font-size:11px">(<?php echo e($total > 0 ? round(($seg['value'] / $total) * 100) : 0); ?>%)</span>
                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-tertiary-token" style="font-size:13px"><?php echo e($emptyText ?? 'Sin registros todavía.'); ?></div>
        <?php endif; ?>
    </div>
</div><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\partials\donut.blade.php ENDPATH**/ ?>