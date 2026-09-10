<?php $__env->startSection('title', 'Dispositivos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="h5 mb-1">Red biométrica</h2>
        <p class="text-muted mb-0">Supervisa la red biométrica y sus registros desde un solo lugar.</p>
    </div>
    <?php if(auth()->user()->isAdmin()): ?>
        <div class="d-flex gap-2">
            <form action="<?php echo e(route('devices.deduplicate')); ?>" method="POST"
                  data-confirm
                  data-confirm-danger
                  data-confirm-type="ELIMINAR"
                  data-confirm-title="¿Eliminar registros duplicados?"
                  data-confirm-message="Se conservará el registro más antiguo de cada empleado y asistencia repetida. Las huellas y relaciones se conservarán cuando sea posible. Escribe ELIMINAR para confirmar.">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-funnel me-1"></i> Limpiar duplicados
                </button>
            </form>
            <a href="<?php echo e(route('devices.create')); ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Registrar dispositivo
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="kpi-grid">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-hdd-network','value' => $stats['devices'],'label' => 'Checadores registrados','color' => 'teal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-hdd-network','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['devices']),'label' => 'Checadores registrados','color' => 'teal']); ?>
        <div class="kpi-spark">
            <?php echo $__env->make('partials.sparkline', ['points' => $spark['devices'], 'color' => 'var(--primary)', 'width' => 84, 'height' => 28], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-wifi','value' => $stats['online'],'label' => 'En línea ahora','color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-wifi','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['online']),'label' => 'En línea ahora','color' => 'green']); ?>
        <div class="kpi-trend flat"><span class="text-tertiary-token" style="font-weight:500"><?php echo e((int) round(($stats['online'] / max(1, $stats['devices'])) * 100)); ?>% de la red</span></div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-people','value' => $stats['employees'],'label' => 'Empleados sincronizados','color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-people','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['employees']),'label' => 'Empleados sincronizados','color' => 'purple']); ?>
        <div class="kpi-spark">
            <?php echo $__env->make('partials.sparkline', ['points' => $spark['employees'], 'color' => 'var(--cat-purple)', 'width' => 84, 'height' => 28], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-calendar-check','value' => $stats['attendances'],'label' => 'Checadas almacenadas','color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-calendar-check','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['attendances']),'label' => 'Checadas almacenadas','color' => 'blue']); ?>
        <div class="kpi-spark">
            <?php echo $__env->make('partials.sparkline', ['points' => $spark['attendances'], 'color' => 'var(--cat-blue)', 'width' => 84, 'height' => 28], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
</div>

<div class="section-heading">
    <h2>Dispositivos de la red</h2>
    <span class="text-muted small"><?php echo e($stats['fingerprints']); ?> huellas protegidas</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Dispositivo</th>
                        <th>Dirección IP</th>
                        <th>Puerto</th>
                        <th>Estado</th>
                        <th class="num-cell">Empleados</th>
                        <th class="num-cell">Registros</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Dispositivo">
                                <a href="<?php echo e(route('devices.show', $device)); ?>" class="fw-semibold text-decoration-none"><?php echo e($device->name); ?></a>
                                <?php if($device->device_name): ?>
                                    <div class="small text-muted"><?php echo e($device->device_name); ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="IP"><code><?php echo e($device->ip); ?></code></td>
                            <td data-label="Puerto"><span class="mono text-secondary-token"><?php echo e($device->port); ?></span></td>
                            <td data-label="Estado">
                                <?php $cat = $device->status === 'online' ? 'cat-green' : ($device->status === 'offline' ? 'cat-red' : 'cat-gray'); ?>
                                <span class="badge badge-with-dot <?php echo e($cat); ?>">
                                    <?php echo e(\App\Models\Device::states()[$device->status] ?? $device->status); ?>

                                </span>
                            </td>
                            <td data-label="Empleados" class="num-cell mono"><?php echo e($device->employees_count); ?></td>
                            <td data-label="Registros" class="num-cell mono"><?php echo e($device->attendances_count); ?></td>
                            <td data-label="">
                                <div class="table-row-actions justify-content-end">
                                    <a href="<?php echo e(route('devices.show', $device)); ?>" class="btn btn-sm btn-ghost" title="Ver detalle" aria-label="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <form action="<?php echo e(route('devices.sync-attendances', $device)); ?>" method="POST" class="d-inline" data-sync>
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-sm btn-ghost" title="Sincronizar asistencias" aria-label="Sincronizar asistencias"><i class="bi bi-calendar-plus"></i></button>
                                        </form>
                                        <a href="<?php echo e(route('devices.edit', $device)); ?>" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="<?php echo e(route('devices.destroy', $device)); ?>" method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-type="ELIMINAR"
                                              data-confirm-title="¿Eliminar dispositivo de la red?"
                                              data-confirm-message="Se eliminará «<?php echo e($device->name); ?>» junto con sus empleados, huellas y registros asociados. Esta acción no se puede deshacer. Escribe ELIMINAR para confirmar.">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7">
                                <?php echo $__env->make('partials.empty-state', [
                                    'icon'     => 'bi-hdd-network',
                                    'title'    => 'No hay checadores registrados aún',
                                    'desc'     => 'Los dispositivos aparecerán aquí cuando se registren en la red.',
                                    'cta'      => auth()->user()->isAdmin()
                                        ? ['label' => 'Registrar primer dispositivo', 'url' => route('devices.create')]
                                        : null,
                                    'ctaLink'  => true,
                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3"><?php echo e($devices->links()); ?></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/devices/index.blade.php ENDPATH**/ ?>