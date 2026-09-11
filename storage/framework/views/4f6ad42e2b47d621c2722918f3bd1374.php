

<?php $__env->startSection('title', 'Incidencias'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="<?php echo e(route('incidencias.create')); ?>" class="btn btn-primary">Nueva incidencia</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('incidencias.index')); ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="text" id="q" name="q" value="<?php echo e($q); ?>" class="form-control" placeholder="Asunto, tipo, motivo, número">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo e($estado === 'pendiente' ? 'selected' : ''); ?>>Pendiente</option>
                    <option value="aprobada" <?php echo e($estado === 'aprobada' ? 'selected' : ''); ?>>Aprobada</option>
                    <option value="rechazada" <?php echo e($estado === 'rechazada' ? 'selected' : ''); ?>>Rechazada</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="<?php echo e(route('incidencias.index')); ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Asunto</th>
                        <th>Tipo</th>
                        <th>Persona</th>
                        <th>Área / Puesto</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $incidencias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $incidencia): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($incidencia->asunto); ?></td>
                            <td><?php echo e($incidencia->tipo_justificacion); ?></td>
                            <td>
                                <?php if($incidencia->empleado): ?>
                                    <?php echo e($incidencia->empleado->name); ?>

                                <?php else: ?>
                                    <?php echo e(trim("{$incidencia->profesor?->paterno} {$incidencia->profesor?->materno} {$incidencia->profesor?->nombre_profesor}")); ?>

                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo e($incidencia->area?->identificador ?? '—'); ?>

                                <?php if($incidencia->puesto): ?>
                                    / <?php echo e($incidencia->puesto->identificador); ?>

                                <?php endif; ?>
                            </td>
                            <td><?php echo e($incidencia->fecha_justificacion->format('d/m/Y')); ?></td>
                            <td>
                                <?php
                                $estadoBadge = match($incidencia->estado) {
                                    'aprobada' => 'bg-success',
                                    'rechazada' => 'bg-danger',
                                    default => 'bg-warning text-dark'
                                };
                                ?>
                                <span class="badge <?php echo e($estadoBadge); ?>"><?php echo e($incidencia->estado); ?></span>
                            </td>
                            <td>
                                <?php if($incidencia->estado !== 'aprobada' || auth()->user()->isAdmin()): ?>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <form method="POST" action="<?php echo e(route('incidencias.estado', $incidencia)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="estado" value="aprobada">
                                            <button type="submit" class="btn btn-outline-success">Aprobar</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('incidencias.estado', $incidencia)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="estado" value="rechazada">
                                            <button type="submit" class="btn btn-outline-danger">Rechazar</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay incidencias registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <?php echo e($incidencias->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/incidencias/index.blade.php ENDPATH**/ ?>