<?php $__env->startSection('title', 'Planes de Estudio'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Planes de Estudio</h1>
    <div class="d-flex align-items-center gap-3">
        <?php if (isset($component)) { $__componentOriginal50f7720e882b68836720a7a50217df1d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50f7720e882b68836720a7a50217df1d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.academia.ciclo-selector','data' => ['ciclo' => $ciclo,'ciclos' => \App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get(),'showBadge' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('academia.ciclo-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['ciclo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ciclo),'ciclos' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get()),'showBadge' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50f7720e882b68836720a7a50217df1d)): ?>
<?php $attributes = $__attributesOriginal50f7720e882b68836720a7a50217df1d; ?>
<?php unset($__attributesOriginal50f7720e882b68836720a7a50217df1d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50f7720e882b68836720a7a50217df1d)): ?>
<?php $component = $__componentOriginal50f7720e882b68836720a7a50217df1d; ?>
<?php unset($__componentOriginal50f7720e882b68836720a7a50217df1d); ?>
<?php endif; ?>
        <a href="<?php echo e(route('academia.planes.create')); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Plan
        </a>
    </div>
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre o ID plan..." value="<?php echo e(request('buscar')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \App\Models\Academia\Nivel::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n->nivel); ?>" <?php echo e(request('nivel') == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="<?php echo e(route('academia.planes.index')); ?>" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if($planes->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-journal-bookmark fs-1 mb-2"></i>
                <p>No hay planes registrados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID Plan</th>
                            <th>Nombre del Plan</th>
                            <th>Nivel</th>
                            <th>Modalidad</th>
                            <th>Duración</th>
                            <th>Materias</th>
                            <th>Ciclos donde se usa</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $planes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($plan->id_plan); ?></td>
                                <td><?php echo e($plan->nombre_plan); ?></td>
                                <td><?php echo e($plan->nivel); ?></td>
                                <td><?php echo e($plan->modalidad); ?></td>
                                <td><?php echo e($plan->duracion_semestres); ?> semestres</td>
                                <td><?php echo e($plan->materias_count); ?></td>
                                <td>
                                    <?php
                                        $ciclosCount = $ciclosPorPlan[$plan->id_plan] ?? 0;
                                    ?>
                                    <span class="badge <?php echo e($ciclosCount > 0 ? 'bg-success' : 'bg-secondary'); ?> rounded-pill">
                                        <?php echo e($ciclosCount); ?> <?php echo e($ciclosCount === 1 ? 'ciclo' : 'ciclos'); ?>

                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--status <?php echo e($plan->activo ? 'badge--active' : 'badge--inactive'); ?>">
                                        <?php echo e($plan->activo ? 'Activo' : 'Inactivo'); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo e(route('academia.planes.show', $plan)); ?>" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo e(route('academia.planes.edit', $plan)); ?>" class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="<?php echo e(route('academia.planes.destroy', $plan)); ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este plan?')">
                                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo e($planes->links()); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\planes\index.blade.php ENDPATH**/ ?>