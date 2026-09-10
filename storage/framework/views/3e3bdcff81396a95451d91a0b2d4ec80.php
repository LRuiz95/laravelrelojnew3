<?php $__env->startSection('title', $plan->nombre_plan); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo e($plan->nombre_plan); ?></h1>
        <p class="text-muted mb-0">ID: <?php echo e($plan->id_plan); ?> | Nivel: <?php echo e($plan->nivel); ?> | <?php echo e($plan->duracion_semestres); ?> semestres</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.planes.edit', $plan)); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
    </div>
</div>


<div class="kpi-grid mb-4">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-journal-bookmark','label' => 'Materias','value' => $plan->materias_count,'color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-journal-bookmark'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Materias'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plan->materias_count),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('blue')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-clock','label' => 'Horas Teoría','value' => $plan->materias->sum('horas_teoria'),'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-clock'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Horas Teoría'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plan->materias->sum('horas_teoria')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-gear','label' => 'Horas Práctica','value' => $plan->materias->sum('horas_practica'),'color' => 'orange']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-gear'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Horas Práctica'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plan->materias->sum('horas_practica')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('orange')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-mortarboard','label' => 'Créditos Totales','value' => $plan->materias->sum('creditos'),'color' => 'teal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-mortarboard'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Créditos Totales'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plan->materias->sum('creditos')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('teal')]); ?>
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


<div class="card">
    <div class="card-header">
        <span class="fw-bold">Materias del Plan (<?php echo e($plan->materias_count); ?>)</span>
    </div>
    <div class="card-body">
        <?php if($materiasPorSemestre->isEmpty()): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay materias asignadas a este plan</p>
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $materiasPorSemestre; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $semestre => $materias): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4">
                    <h6 class="fw-semibold text-primary mb-2">Semestre <?php echo e($semestre); ?></h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Materia</th>
                                    <th class="text-center">Teoría</th>
                                    <th class="text-center">Práctica</th>
                                    <th class="text-center">Créditos</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $materias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo e($m->clave_asignatura); ?></td>
                                        <td><?php echo e($m->nombre_asignatura); ?></td>
                                        <td class="text-center"><?php echo e($m->horas_teoria); ?></td>
                                        <td class="text-center"><?php echo e($m->horas_practica); ?></td>
                                        <td class="text-center"><?php echo e($m->creditos); ?></td>
                                        <td>
                                            <span class="badge <?php echo e($m->tipo === 'obligatoria' ? 'bg-primary' : 'bg-secondary'); ?>">
                                                <?php echo e($m->tipo); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge--status <?php echo e($m->activa ? 'badge--active' : 'badge--inactive'); ?>">
                                                <?php echo e($m->activa ? 'Activa' : 'Inactiva'); ?>

                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\planes\show.blade.php ENDPATH**/ ?>