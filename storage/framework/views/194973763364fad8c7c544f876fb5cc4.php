<?php $__env->startSection('title', $profesor->nombre_completo); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo e($profesor->nombre_completo); ?></h1>
        <p class="text-muted mb-0">
            <?php echo e($profesor->clave_profesor); ?> | <?php echo e($profesor->departamento ?? '—'); ?> · <?php echo e($profesor->origen_horario_label); ?>

        </p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.profesores.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="<?php echo e(route('academia.profesores.horario', $profesor)); ?>" class="btn btn-outline-primary">
            <i class="bi bi-calendar-week me-1"></i> Horario
        </a>
    </div>
</div>


<div class="kpi-grid mb-4">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-calendar-week','label' => 'Total clases','value' => $stats['total_clases'],'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-calendar-week'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Total clases'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total_clases']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?>
        <div class="kpi-trend flat">–</div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-person-badge','label' => 'PTC','value' => $stats['ptc'],'color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-person-badge'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('PTC'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['ptc']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('blue')]); ?>
        <div class="kpi-trend flat">–</div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-book','label' => 'PA','value' => $stats['pa'],'color' => 'teal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-book'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('PA'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['pa']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('teal')]); ?>
        <div class="kpi-trend flat">–</div>
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


<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><span class="fw-bold">Datos Personales</span></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">RFC</dt><dd class="col-sm-8"><?php echo e($profesor->rfc ?? '—'); ?></dd>
                    <dt class="col-sm-4">CURP</dt><dd class="col-sm-8"><?php echo e($profesor->curp ?? '—'); ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?php echo e($profesor->email ?? '—'); ?></dd>
                    <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8"><?php echo e($profesor->telefono ?? '—'); ?></dd>
                    <dt class="col-sm-4">Ingreso</dt><dd class="col-sm-8"><?php echo e($profesor->fecha_ingreso?->format('d/m/Y') ?? '—'); ?></dd>
                    <dt class="col-sm-4">Sede</dt><dd class="col-sm-8"><?php echo e($profesor->sede?->descripcion ?? $profesor->id_campus); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><span class="fw-bold">Contratos</span></div>
            <div class="card-body">
                <?php if($contratos->isEmpty()): ?>
                    <p class="text-muted mb-0">No se encontraron contratos</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Contrato</th><th>Tipo</th><th>Periodo</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $contratos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($c['contrato'] ?? '—'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo e($c['tipo'] ?? '—'); ?></span></td>
                                        <td><?php echo e($c['periodo'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="card mb-4">
    <div class="card-header"><span class="fw-bold">Horarios del Ciclo</span></div>
    <div class="card-body p-0">
        <?php if($horarios->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 mb-2"></i>
                <p>No hay horarios asignados en este ciclo</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Sesión</th>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Aula</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $horarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia => $clases): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $clases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <?php if($i === 0): ?>
                                        <td rowspan="<?php echo e($clases->count()); ?>" class="fw-semibold align-middle">
                                            <?php echo e(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$dia - 1] ?? $dia); ?>

                                        </td>
                                    <?php endif; ?>
                                    <td>Ses. <?php echo e($cl->sesion); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo e($cl->grupo?->codigo_grupo ?? '—'); ?></span></td>
                                    <td><?php echo e($cl->materia?->label ?? $cl->clave_asignatura); ?></td>
                                    <td><small class="text-muted"><?php echo e($cl->ubicacion ?? '—'); ?></small></td>
                                    <td><span class="badge <?php echo e($cl->tipoClase === 'PTC' ? 'bg-purple' : 'bg-info'); ?>"><?php echo e($cl->tipoClase); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\profesores\show.blade.php ENDPATH**/ ?>