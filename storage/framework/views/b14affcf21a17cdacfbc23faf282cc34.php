<?php $__env->startSection('title', 'Profesores'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Profesores</h1>
    <?php if (isset($component)) { $__componentOriginal50f7720e882b68836720a7a50217df1d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50f7720e882b68836720a7a50217df1d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.academia.ciclo-selector','data' => ['ciclo' => $ciclo,'ciclos' => \App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('academia.ciclo-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['ciclo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ciclo),'ciclos' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Academia\Ciclo::orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo')->get())]); ?>
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
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Clave, nombre..." value="<?php echo e(request('buscar')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estatus</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($val); ?>" <?php echo e(request('status') == $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Origen</label>
                <select name="origen" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $origenOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($val); ?>" <?php echo e(request('origen') == $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Departamento</label>
                <input type="text" name="departamento" class="form-control" value="<?php echo e(request('departamento')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="soloCiclo" name="solo_ciclo" value="1" <?php echo e($soloCiclo ? 'checked' : ''); ?>>
                    <label class="form-check-label small" for="soloCiclo">Solo con horarios en ciclo</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if($profesores->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-person-badge fs-1 mb-2"></i>
                <p>No se encontraron profesores</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Origen</th>
                            <th>Contrato</th>
                            <th>Sede</th>
                            <th>Estatus</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $profesores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($p->clave_profesor); ?></td>
                                <td><?php echo e($p->nombre_completo); ?></td>
                                <td><?php echo e($p->departamento ?? '—'); ?></td>
                                <td>
                                    <span class="badge <?php echo e($p->esPTC ? 'bg-purple' : 'bg-info'); ?>">
                                        <?php echo e($p->origen_horario_label); ?>

                                    </span>
                                </td>
                                <td><?php echo e($p->tipo_contrato); ?></td>
                                <td><?php echo e($p->sede?->descripcion ?? $p->id_campus); ?></td>
                                <td>
                                    <span class="badge badge--status <?php echo e($p->status_actual === 'A' ? 'badge--active' : 'badge--inactive'); ?>">
                                        <?php echo e($p->status_label); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo e(route('academia.profesores.show', $p)); ?>" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo e(route('academia.profesores.horario', $p)); ?>" class="btn btn-outline-secondary" title="Horario">
                                            <i class="bi bi-calendar-week"></i>
                                        </a>
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

<?php echo e($profesores->withQueryString()->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\profesores\index.blade.php ENDPATH**/ ?>