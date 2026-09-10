<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['headers', 'rows', 'actions', 'emptyMessage' => 'No hay datos', 'striped' => true, 'hover' => true, 'bordered' => true, 'pagination' => null]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['headers', 'rows', 'actions', 'emptyMessage' => 'No hay datos', 'striped' => true, 'hover' => true, 'bordered' => true, 'pagination' => null]); ?>
<?php foreach (array_filter((['headers', 'rows', 'actions', 'emptyMessage' => 'No hay datos', 'striped' => true, 'hover' => true, 'bordered' => true, 'pagination' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="card">
    <div class="card-body p-0">
        <?php if($rows->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-table fs-1 mb-2"></i>
                <p><?php echo e($emptyMessage); ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table <?php echo e($striped ? 'table-striped' : ''); ?> <?php echo e($hover ? 'table-hover' : ''); ?> <?php echo e($bordered ? 'table-bordered' : ''); ?> align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th <?php echo e(isset($header['width']) ? 'style="width: ' . $header['width'] . '"' : ''); ?> <?php echo e(isset($header['class']) ? 'class="' . $header['class'] . '"' : ''); ?>>
                                    <?php echo e($header['label'] ?? $header); ?>

                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($actions): ?>
                                <th class="text-end" style="width: 120px;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td <?php echo e(isset($header['class']) ? 'class="' . $header['class'] . '"' : ''); ?>>
                                        <?php if(isset($header['render'])): ?>
                                            <?php echo e($header['render']($row)); ?>

                                        <?php elseif(isset($header['field'])): ?>
                                            <?php echo e($row[$header['field']] ?? ''); ?>

                                        <?php else: ?>
                                            <?php echo e($row->{$header} ?? ''); ?>

                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if($actions): ?>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if($action['type'] === 'link'): ?>
                                                    <a href="<?php echo e($action['url']($row)); ?>" class="btn btn-outline-<?php echo e($action['style'] ?? 'primary'); ?> btn-sm" title="<?php echo e($action['title']); ?>">
                                                        <i class="<?php echo e($action['icon']); ?>"></i>
                                                    </a>
                                                <?php elseif($action['type'] === 'button'): ?>
                                                    <button type="button" class="btn btn-outline-<?php echo e($action['style'] ?? 'primary'); ?> btn-sm" onclick="<?php echo e($action['onclick']($row)); ?>" title="<?php echo e($action['title']); ?>">
                                                        <i class="<?php echo e($action['icon']); ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if($pagination): ?>
        <div class="card-footer border-0 bg-transparent pt-0">
            <?php echo e($pagination->links()); ?>

        </div>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\components\data-table.blade.php ENDPATH**/ ?>