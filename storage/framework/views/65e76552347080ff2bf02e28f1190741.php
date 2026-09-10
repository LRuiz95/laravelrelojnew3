<?php $__env->startSection('title', 'Nuevo Curso'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Curso</h1>
    <a href="<?php echo e(route('academia.cursos.index')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo e(route('academia.cursos.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="inicial" value="<?php echo e($ciclo->inicial); ?>">
            <input type="hidden" name="final" value="<?php echo e($ciclo->final); ?>">
            <input type="hidden" name="periodo" value="<?php echo e($ciclo->periodo); ?>">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Clave Curso <span class="text-danger">*</span></label>
                    <input type="text" name="clave_curso" class="form-control" required maxlength="20" value="<?php echo e(old('clave_curso')); ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre del Curso <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_curso" class="form-control" required maxlength="100" value="<?php echo e(old('nombre_curso')); ?>">
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
                    <label class="form-label">Turno <span class="text-danger">*</span></label>
                    <select name="turno" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php $__currentLoopData = $turnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($t->turno); ?>" <?php echo e(old('turno') == $t->turno ? 'selected' : ''); ?>><?php echo e($t->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sede</label>
                    <select name="id_campus" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        <?php $__currentLoopData = $sedes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->id_campus); ?>" <?php echo e(old('id_campus') == $s->id_campus ? 'selected' : ''); ?>><?php echo e($s->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo e(route('academia.cursos.index')); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Crear Curso
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\cursos\create.blade.php ENDPATH**/ ?>