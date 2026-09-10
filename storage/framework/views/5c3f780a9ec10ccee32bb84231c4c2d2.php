<?php $__env->startSection('title', 'Editar Plan: ' . $plan->nombre_plan); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Plan: <?php echo e($plan->nombre_plan); ?></h1>
    <a href="<?php echo e(route('academia.planes.show', $plan)); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo e(route('academia.planes.update', $plan)); ?>" method="POST">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Plan</label>
                    <input type="number" class="form-control" value="<?php echo e($plan->id_plan); ?>" readonly>
                    <div class="form-text">No editable</div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Nombre del Plan <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_plan" class="form-control" required maxlength="100" value="<?php echo e($plan->nombre_plan); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel <span class="text-danger">*</span></label>
                    <select name="nivel" class="form-select" required>
                        <?php $__currentLoopData = $niveles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($n->nivel); ?>" <?php echo e($plan->nivel == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modalidad</label>
                    <input type="text" name="modalidad" class="form-control" maxlength="20" value="<?php echo e($plan->modalidad); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duración (semestres)</label>
                    <input type="number" name="duracion_semestres" class="form-control" min="1" max="12" value="<?php echo e($plan->duracion_semestres); ?>">
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo e($plan->activo ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo e(route('academia.planes.show', $plan)); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\planes\edit.blade.php ENDPATH**/ ?>