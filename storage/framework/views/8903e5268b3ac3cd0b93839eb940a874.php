<?php $__env->startSection('title', 'Grupos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Grupos</h1>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \App\Models\Academia\Nivel::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n->nivel); ?>" <?php echo e(request('nivel') == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \App\Models\Academia\Turno::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t->turno); ?>" <?php echo e(request('turno') == $t->turno ? 'selected' : ''); ?>><?php echo e($t->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                    <option value="">Todas</option>
                    <?php $__currentLoopData = \App\Models\Academia\Sede::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s->id_campus); ?>" <?php echo e(request('sede') == $s->id_campus ? 'selected' : ''); ?>><?php echo e($s->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Nivel</th>
                        <th>Turno</th>
                        <th>Grado</th>
                        <th>Modalidad</th>
                        <th>Inscritos</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $grupos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grupo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="fw-semibold"><?php echo e($grupo->codigo_grupo); ?></td>
                            <td><?php echo e($grupo->nivelRel?->descripcion ?? $grupo->nivel); ?></td>
                            <td>
                                <?php echo e($grupo->turno_nombre); ?>

                            </td>
                            <td><?php echo e($grupo->grado); ?>°</td>
                            <td>
                                <?php echo e($grupo->modalidad_nombre); ?>

                                <?php if($grupo->codigo_grupo_partes['nivel_superior']): ?>
                                    <small class="d-block text-muted">Ingeniería/Licenciatura</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($grupo->inscritos); ?></td>
                            <td><?php echo e($grupo->sede?->descripcion ?? $grupo->id_campus); ?></td>
                            <td>
                                <span class="badge badge--status <?php echo e($grupo->activo ? 'badge--active' : 'badge--inactive'); ?>">
                                    <?php echo e($grupo->activo ? 'Activo' : 'Inactivo'); ?>

                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo e(route('academia.grupos.show', $grupo)); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo e($grupos->links()); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\grupos\index.blade.php ENDPATH**/ ?>