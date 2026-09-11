

<?php $__env->startSection('title', 'Puntualidad'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Puntualidad</h4>
        <small class="text-muted">Control de llegadas, salidas y incidencias autorizadas</small>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?php if (isset($component)) { $__componentOriginale9f22847d79d6273acb27aff60f1f678 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9f22847d79d6273acb27aff60f1f678 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter-bar','data' => ['id' => 'puntualidadFilters','action' => route('puntualidad.index'),'clearUrl' => route('puntualidad.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'puntualidadFilters','action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('puntualidad.index')),'clear-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('puntualidad.index'))]); ?>
            <div class="col-md-2">
                <label for="from" class="form-label small mb-1">Desde</label>
                <input type="date" id="from" name="from" class="form-control" value="<?php echo e(request('from', $from->toDateString())); ?>">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small mb-1">Hasta</label>
                <input type="date" id="to" name="to" class="form-control" value="<?php echo e(request('to', $to->toDateString())); ?>">
            </div>
            <div class="col-md-2">
                <label for="gracia_minutos" class="form-label small mb-1">Lapso permitido</label>
                <input type="number" id="gracia_minutos" name="gracia_minutos" class="form-control" min="0" value="<?php echo e(request('gracia_minutos', $graciaMinutos)); ?>">
            </div>
            <div class="col-md-2">
                <label for="hora_entrada_base" class="form-label small mb-1">Hora entrada base</label>
                <input type="time" id="hora_entrada_base" name="hora_entrada_base" class="form-control" value="<?php echo e(request('hora_entrada_base', $horaEntradaBase)); ?>">
            </div>
            <div class="col-md-2">
                <label for="hora_salida_base" class="form-label small mb-1">Hora salida base</label>
                <input type="time" id="hora_salida_base" name="hora_salida_base" class="form-control" value="<?php echo e(request('hora_salida_base', $horaSalidaBase)); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale9f22847d79d6273acb27aff60f1f678)): ?>
<?php $attributes = $__attributesOriginale9f22847d79d6273acb27aff60f1f678; ?>
<?php unset($__attributesOriginale9f22847d79d6273acb27aff60f1f678); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale9f22847d79d6273acb27aff60f1f678)): ?>
<?php $component = $__componentOriginale9f22847d79d6273acb27aff60f1f678; ?>
<?php unset($__componentOriginale9f22847d79d6273acb27aff60f1f678); ?>
<?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Empleados</span>
                <small class="text-muted">Llegada, salida e incidencias</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Área</th>
                            <th>Puesto</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                            <th>Incidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $employeeRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(\Carbon\Carbon::parse($row['fecha'])->locale('es')->isoFormat('D MMM YYYY')); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo e($row['empleado']); ?></div>
                                    <small class="text-muted"><?php echo e($row['tipo_persona']); ?> · <?php echo e($row['user_id']); ?></small>
                                </td>
                                <td><?php echo e($row['area']); ?></td>
                                <td><?php echo e($row['puesto']); ?></td>
                                <td><?php echo e($row['entrada'] ?? '—'); ?></td>
                                <td><?php echo e($row['salida'] ?? '—'); ?></td>
                                <td>
                                    <?php
                                        $stateClasses = [
                                            'puntual' => 'bg-success',
                                            'atraso' => 'bg-warning text-dark',
                                            'llego_temprano' => 'bg-info text-dark',
                                            'falta' => 'bg-danger',
                                            'se_fue_tarde' => 'bg-primary',
                                            'salio_temprano' => 'bg-secondary',
                                            'sin_salida' => 'bg-light text-dark border',
                                        ];
                                        $stateLabels = [
                                            'puntual' => 'Puntual',
                                            'atraso' => 'Atraso',
                                            'llego_temprano' => 'Llegó temprano',
                                            'falta' => 'Falta',
                                            'se_fue_tarde' => 'Se fue tarde',
                                            'salio_temprano' => 'Salió temprano',
                                            'sin_salida' => 'Sin salida',
                                        ];
                                    ?>
                                    <span class="badge <?php echo e($stateClasses[$row['llegada_estado']] ?? 'bg-light text-dark border'); ?>">
                                        <?php echo e($stateLabels[$row['llegada_estado']] ?? $row['llegada_estado']); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $incidenciaClasses = [
                                            'autorizada' => 'bg-success',
                                            'pendiente' => 'bg-warning text-dark',
                                            'ninguna' => 'bg-light text-dark border',
                                        ];
                                        $incidenciaLabels = [
                                            'autorizada' => 'Autorizada',
                                            'pendiente' => 'Pendiente',
                                            'ninguna' => 'Sin incidencia',
                                        ];
                                    ?>
                                    <span class="badge <?php echo e($incidenciaClasses[$row['incidencia_estado']] ?? 'bg-light text-dark border'); ?>">
                                        <?php echo e($incidenciaLabels[$row['incidencia_estado']] ?? $row['incidencia_estado']); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay registros de puntualidad para ese periodo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Profesores</span>
                <small class="text-muted">Estado de asistencia por clase</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Profesor</th>
                            <th>Área</th>
                            <th>Puesto</th>
                            <th>Estado</th>
                            <th>Incidencia</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $professorRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(\Carbon\Carbon::parse($row['fecha'])->locale('es')->isoFormat('D MMM YYYY')); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo e($row['profesor']); ?></div>
                                    <small class="text-muted"><?php echo e($row['clave_profesor']); ?></small>
                                </td>
                                <td><?php echo e($row['area']); ?></td>
                                <td><?php echo e($row['puesto']); ?></td>
                                <td>
                                    <?php
                                        $profState = [
                                            'presente' => 'bg-success',
                                            'retardo' => 'bg-warning text-dark',
                                            'justificado' => 'bg-info text-dark',
                                            'ausente' => 'bg-danger',
                                        ];
                                    ?>
                                    <span class="badge <?php echo e($profState[$row['estado']] ?? 'bg-light text-dark border'); ?>">
                                        <?php echo e(ucfirst($row['estado'])); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $incClase = [
                                            'autorizada' => 'bg-success',
                                            'pendiente' => 'bg-warning text-dark',
                                            'ninguna' => 'bg-light text-dark border',
                                        ];
                                    ?>
                                    <span class="badge <?php echo e($incClase[$row['incidencia_estado']] ?? 'bg-light text-dark border'); ?>">
                                        <?php echo e(ucfirst($row['incidencia_estado'])); ?>

                                    </span>
                                </td>
                                <td><?php echo e($row['detalle'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay registros de profesores para ese periodo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/puntualidad/index.blade.php ENDPATH**/ ?>