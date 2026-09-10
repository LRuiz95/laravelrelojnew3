
<?php
    $points = $points ?? [];
    $w = $width ?? 96;
    $h = $height ?? 30;
    $pad = 2;
    $color = $color ?? 'var(--primary)';
    $max = max(1, ...$points);
    $n = count($points);
    $stepX = $n > 1 ? ($w - $pad * 2) / ($n - 1) : 0;
    $line = [];
    foreach ($points as $i => $v) {
        $x = $n > 1 ? round($pad + $i * $stepX, 1) : round($w / 2, 1);
        $y = round($pad + ($h - $pad * 2) * (1 - ($v / $max)), 1);
        $line[] = "$x,$y";
    }
    $area = $line ? implode(' ', [...$line, "$w,$h", "0,$h", $line[array_key_first($line)]]) : '';
    $uid = 'sl' . \Illuminate\Support\Str::random(6);
?>
<svg class="sparkline-svg" width="<?php echo e($w); ?>" height="<?php echo e($h); ?>" viewBox="0 0 <?php echo e($w); ?> <?php echo e($h); ?>"
     aria-hidden="true" <?php if(isset($label)): ?> role="img" aria-label="<?php echo e($label); ?>" <?php endif; ?>>
    <?php if($area): ?>
        <defs>
            <linearGradient id="<?php echo e($uid); ?>" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="<?php echo e($color); ?>"/>
                <stop offset="100%" stop-color="<?php echo e($color); ?>" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <polygon class="sl-fill" style="fill:url(#<?php echo e($uid); ?>)" points="<?php echo e($area); ?>"/>
        <polyline class="sl-line" style="stroke:<?php echo e($color); ?>" points="<?php echo e(implode(' ', $line)); ?>"/>
    <?php endif; ?>
</svg><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\partials\sparkline.blade.php ENDPATH**/ ?>