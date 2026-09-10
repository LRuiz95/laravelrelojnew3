<?php $__env->startSection('title', 'Kardex: ' . $alumnoSeleccionado->nombre_completo); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kardex</h1>
        <p class="text-muted mb-0"><?php echo e($alumnoSeleccionado->nombre_completo); ?> | Control: <?php echo e($alumnoSeleccionado->numero_alumno); ?></p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.alumnos.show', $alumnoSeleccionado)); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="<?php echo e(route('academia.kardex.print', ['alumno_id' => $alumnoSeleccionado->numero_alumno])); ?>" target="_blank" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Imprimir
        </a>
    </div>
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Buscar alumno</label>
                <input type="text" name="buscar" class="form-control" placeholder="Control, nombre, CURP..." value="<?php echo e(request('buscar')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ciclo</label>
                <select name="ciclo" class="form-select">
                    <?php $__currentLoopData = $ciclos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c->label); ?>" <?php echo e(request('ciclo') == $c->label ? 'selected' : ''); ?>><?php echo e($c->label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if($alumnos && $alumnos->isNotEmpty()): ?>
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">Resultados: <?php echo e($alumnos->count()); ?> alumno(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Control</th><th>Nombre</th><th>Nivel</th><th>Turno</th><th>Ciclo</th><th></th></tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $alumnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($a->numero_alumno); ?></td>
                                <td><?php echo e($a->nombre_completo); ?></td>
                                <td><?php echo e($a->nivel); ?></td>
                                <td><?php echo e($a->turno); ?></td>
                                <td><?php echo e(request('ciclo')); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('academia.kardex.show', ['alumno_id' => $a->numero_alumno, 'ciclo' => request('ciclo')])); ?>" class="btn btn-sm btn-primary">Ver Kardex</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php endif; ?>

<?php if($kardex): ?>
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-bold">Kardex Ciclo: <?php echo e($ciclo->label); ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Sem</th>
                            <th class="text-center">P1</th>
                            <th class="text-center">P2</th>
                            <th class="text-center">P3</th>
                            <th class="text-center">CF</th>
                            <th class="text-center">EXR</th>
                            <th class="text-center">EXRS</th>
                            <th class="text-center">CT</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $kardex['materias']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clave => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($m['nombre'] ?? $clave); ?></td>
                                <td><?php echo e($m['semestre'] ?? ''); ?></td>
                                <td class="text-center"><?php echo e($m['P1'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m['P2'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m['P3'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m['CF'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m['EXR'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m['EXRS'] ?? '—'); ?></td>
                                <td class="text-center fw-semibold"><?php echo e($m['CT'] ?? '—'); ?></td>
                                <td>
                                    <?php
                                        $estadoClass = match($m['ESTADO']) {
                                            'APROBADO' => 'bg-success',
                                            'REPROBADO' => 'bg-danger',
                                            'SIN DERECHO' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo e($estadoClass); ?>">
                                        <?php echo e($m['ESTADO']); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body"><div class="h3 text-success"><?php echo e($kardex['resumen']['aprobadas']); ?></div><small class="text-muted">Aprobadas</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body"><div class="h3 text-danger"><?php echo e($kardex['resumen']['reprobadas']); ?></div><small class="text-muted">Reprobadas</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body"><div class="h3 text-warning"><?php echo e($kardex['resumen']['sin_derecho']); ?></div><small class="text-muted">Sin derecho</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body"><div class="h3 text-info"><?php echo e($kardex['resumen']['promedio'] ?? '—'); ?></div><small class="text-muted">Promedio</small></div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\kardex\show.blade.php ENDPATH**/ ?>