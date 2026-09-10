<?php $__env->startSection('title', 'Alumnos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Alumnos</h1>
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
                <input type="text" name="buscar" class="form-control" placeholder="Control, nombre, CURP..." value="<?php echo e(request('buscar')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estatus</label>
                <select name="estatus" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = ['ACTIVO','BAJA','EGRESADO','TITULADO','IRREGULAR']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($e); ?>" <?php echo e(request('estatus') == $e ? 'selected' : ''); ?>><?php echo e($e); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \App\Models\Academia\Nivel::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n->nivel); ?>" <?php echo e(request('nivel') == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \App\Models\Academia\Turno::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t->turno); ?>" <?php echo e(request('turno') == $t->turno ? 'selected' : ''); ?>><?php echo e($t->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <a href="<?php echo e(route('academia.alumnos.index')); ?>" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if($alumnos->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-people fs-1 mb-2"></i>
                <p>No se encontraron alumnos</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Control</th>
                            <th>Nombre</th>
                            <th>Grupo</th>
                            <th>CURP</th>
                            <th>Nivel</th>
                            <th>Turno</th>
                            <th>Sede</th>
                            <th>Estatus</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $alumnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($alumno->numero_alumno); ?></td>
                                <td>
                                    <a href="<?php echo e(route('academia.alumnos.show', $alumno)); ?>" class="text-decoration-none fw-semibold">
                                        <?php echo e($alumno->nombre_completo); ?>

                                    </a>
                                </td>
                                <td>
                                    <?php
                                        $inscripcion = $alumno->inscripciones->first();
                                    ?>
                                    <?php if($inscripcion && $inscripcion->grupo): ?>
                                        <span class="badge bg-info text-dark"><?php echo e($inscripcion->grupo->codigo_grupo); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo e($alumno->curp); ?></td>
                                <td><?php echo e($alumno->nivel); ?></td>
                                <td>
                                    <span class="badge <?php echo e($alumno->turnoRel && str_starts_with($alumno->turnoRel->descripcion_corta, 'V') ? 'bg-purple' : 'bg-warning'); ?>">
                                        <?php echo e($alumno->turnoRel?->descripcion_corta ?? $alumno->turno); ?>

                                    </span>
                                </td>
                                <td><?php echo e($alumno->sede?->descripcion); ?></td>
                                <td>
                                    <span class="badge badge--status <?php echo e(in_array($alumno->estatus, ['ACTIVO','REINSCRITO']) ? 'badge--active' : 'badge--inactive'); ?>">
                                        <?php echo e($alumno->estatus); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo e(route('academia.alumnos.show', $alumno)); ?>" class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo e(route('academia.alumnos.kardex', $alumno)); ?>" class="btn btn-outline-success" title="Kardex">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                        <a href="<?php echo e(route('academia.alumnos.historial', $alumno)); ?>" class="btn btn-outline-info" title="Historial">
                                            <i class="bi bi-clock-history"></i>
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

<?php echo e($alumnos->links()); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\alumnos\index.blade.php ENDPATH**/ ?>