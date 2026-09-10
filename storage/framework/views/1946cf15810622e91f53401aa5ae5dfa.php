<?php $__env->startSection('title', 'Nuevo Plan'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Plan de Estudio</h1>
    <a href="<?php echo e(route('academia.planes.index')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo e(route('academia.planes.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Plan <span class="text-danger">*</span></label>
                    <input type="number" name="id_plan" class="form-control" required min="1" value="<?php echo e(old('id_plan')); ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Nombre del Plan <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_plan" class="form-control" required maxlength="100" value="<?php echo e(old('nombre_plan')); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel <span class="text-danger">*</span></label>
                    <select name="nivel" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php $__currentLoopData = $niveles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($n->nivel); ?>" <?php echo e(old('nivel') == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modalidad</label>
                    <input type="text" name="modalidad" class="form-control" maxlength="20" value="<?php echo e(old('modalidad')); ?>" placeholder="Ej: Escolarizada, Mixta, etc.">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duración (semestres)</label>
                    <input type="number" name="duracion_semestres" class="form-control" min="1" max="12" value="<?php echo e(old('duracion_semestres')); ?>">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo e(route('academia.planes.index')); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Crear Plan
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\planes\create.blade.php ENDPATH**/ ?>