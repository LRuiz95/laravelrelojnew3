

<?php $__env->startSection('title', 'Nueva área'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Nueva área</h1>
        <p class="text-muted mb-0">Crea un área con identificador, descripción y responsable.</p>
    </div>
    <a href="<?php echo e(route('areas.index')); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo e(route('areas.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="identificador">Identificador</label>
                    <input type="text" id="identificador" name="identificador" class="form-control" value="<?php echo e(old('identificador')); ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <input type="text" id="descripcion" name="descripcion" class="form-control" value="<?php echo e(old('descripcion')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="empleado_responsable_id">Empleado responsable</label>
                    <select id="empleado_responsable_id" name="empleado_responsable_id" class="form-select">
                        <option value="">Sin responsable</option>
                        <?php $__currentLoopData = $empleados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empleado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($empleado->id); ?>" <?php echo e(old('empleado_responsable_id') == $empleado->id ? 'selected' : ''); ?>>
                                <?php echo e($empleado->name); ?> (<?php echo e($empleado->user_id); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo e(route('areas.index')); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar área</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/areas/create.blade.php ENDPATH**/ ?>