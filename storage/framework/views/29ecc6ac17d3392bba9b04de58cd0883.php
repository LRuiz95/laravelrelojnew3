

<?php $__env->startSection('title', 'Asistencia por grupo: ' . $grupo->codigo_grupo); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Asistencia por grupo</h1>
        <p class="text-muted mb-2">Captura individual de alumnos por clase y fecha.</p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-dark">Grupo <?php echo e($grupo->codigo_grupo); ?></span>
            <span class="badge bg-secondary"><?php echo e($grupo->grado); ?>° · <?php echo e($grupo->nivel); ?></span>
            <span class="badge bg-secondary">Turno <?php echo e($grupo->turnoRel?->descripcion ?? $grupo->turno); ?></span>
            <span class="badge bg-secondary"><?php echo e($grupo->sede?->descripcion ?? 'Sede sin definir'); ?></span>
            <span class="badge bg-light text-dark border">Ciclo <?php echo e($ciclo->label); ?></span>
        </div>
    </div>
    <a href="<?php echo e(route('academia.grupos.show', [$grupo, 'ciclo_principal' => $ciclo->label])); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al grupo
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="ciclo_principal" value="<?php echo e($ciclo->label); ?>">
            <div class="col-md-4">
                <label class="form-label">Fecha de asistencia</label>
                <input type="date" name="fecha" class="form-control" value="<?php echo e($fecha); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Clase seleccionada</label>
                <select name="horario_id" class="form-select">
                    <?php $__currentLoopData = $horariosPorDia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia => $clasesDia): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $__currentLoopData = $clasesDia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($clase->id); ?>" <?php echo e($claseSeleccionada?->id === $clase->id ? 'selected' : ''); ?>>
                                <?php echo e($diasSemana[$dia]); ?> · Sesión <?php echo e($clase->sesion); ?> · <?php echo e($clase->materia?->nombre_asignatura); ?> · <?php echo e($clase->aula ?? 'Sin aula'); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-calendar-check me-1"></i> Ver clase y alumnos
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-calendar-week me-2"></i>Horario semanal</span>
        <small class="text-muted">Selecciona una clase para capturar asistencia</small>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <?php $__currentLoopData = $diasSemana; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia => $nombreDia): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="text-center" style="min-width: 170px;"><?php echo e($nombreDia); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php $__currentLoopData = $horariosPorDia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia => $clasesDia): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td class="p-2" style="vertical-align: top;">
                                <?php $__empty_1 = true; $__currentLoopData = $clasesDia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <a href="<?php echo e(request()->fullUrlWithQuery(['horario_id' => $clase->id, 'fecha' => $fecha])); ?>"
                                       class="d-block text-decoration-none border rounded p-2 mb-2 <?php echo e($claseSeleccionada?->id === $clase->id ? 'border-primary bg-primary-subtle' : 'bg-light'); ?>">
                                        <div class="fw-semibold text-dark">Ses. <?php echo e($clase->sesion); ?> · <?php echo e($clase->sesionBase?->hora_inicio?->format('H:i')); ?>-<?php echo e($clase->sesionBase?->hora_fin?->format('H:i')); ?></div>
                                        <div class="small text-dark"><?php echo e($clase->materia?->nombre_asignatura ?? $clase->clave_asignatura); ?></div>
                                        <div class="small text-muted"><?php echo e($clase->profesor?->nombre_completo ?? $clase->clave_profesor); ?></div>
                                        <div class="small text-muted"><?php echo e($grupo->sede?->descripcion ?? 'Sede sin definir'); ?> · Ed. <?php echo e($clase->edificio ?? '—'); ?> · Aula <?php echo e($clase->aula ?? '—'); ?></div>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <span class="small text-muted">Sin clase</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if($claseSeleccionada): ?>
    <div class="kpi-grid mb-4">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-people','label' => 'Alumnos inscritos','value' => $stats['total_alumnos'],'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-people'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Alumnos inscritos'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total_alumnos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?><div class="kpi-trend flat">-</div> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-check-circle','label' => 'Capturados','value' => $stats['capturadas'] . '/' . $stats['total_alumnos'],'color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-check-circle'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Capturados'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['capturadas'] . '/' . $stats['total_alumnos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('green')]); ?><div class="kpi-trend flat">-</div> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-check','label' => 'Presentes','value' => $stats['presentes'],'color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-check'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Presentes'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['presentes']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('success')]); ?><div class="kpi-trend flat">-</div> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-x-circle','label' => 'Ausentes','value' => $stats['ausentes'],'color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-x-circle'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Ausentes'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['ausentes']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('danger')]); ?><div class="kpi-trend flat">-</div> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="fw-semibold"><?php echo e($claseSeleccionada->materia?->nombre_asignatura ?? $claseSeleccionada->clave_asignatura); ?></div>
            <div class="small text-muted">
                Grupo <?php echo e($grupo->codigo_grupo); ?> · <?php echo e($claseSeleccionada->profesor?->nombre_completo ?? $claseSeleccionada->clave_profesor); ?> ·
                <?php echo e($grupo->sede?->descripcion ?? 'Sede sin definir'); ?> · Edificio <?php echo e($claseSeleccionada->edificio ?? '-'); ?> · Aula <?php echo e($claseSeleccionada->aula ?? '-'); ?> · <?php echo e($fecha); ?>

            </div>
        </div>
        <form method="POST" action="<?php echo e(route('academia.grupos.asistencia.guardar')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="horario_id" value="<?php echo e($claseSeleccionada->id); ?>">
            <input type="hidden" name="inicial" value="<?php echo e($ciclo->inicial); ?>">
            <input type="hidden" name="final" value="<?php echo e($ciclo->final); ?>">
            <input type="hidden" name="periodo" value="<?php echo e($ciclo->periodo); ?>">
            <input type="hidden" name="codigo_grupo" value="<?php echo e($grupo->codigo_grupo); ?>">
            <input type="hidden" name="fecha" value="<?php echo e($fecha); ?>">

            <div class="p-3 border-bottom">
                <label class="form-label fw-semibold" for="observacionGrupo">Observación general del grupo</label>
                <textarea id="observacionGrupo" name="observacion_grupo" class="form-control" rows="2" maxlength="1000" placeholder="Incidencia general de la clase o situación del grupo..."><?php echo e($grupoAsistencia?->observaciones); ?></textarea>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Control</th><th>Alumno</th><th style="min-width: 180px;">Estado</th><th>Observación individual</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $alumnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $asistencia = $asistencias->get($alumno->numero_alumno); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($alumno->numero_alumno); ?></td>
                                <td><?php echo e($alumno->nombre_completo); ?></td>
                                <td>
                                    <select name="alumnos[<?php echo e($alumno->numero_alumno); ?>][estado]" class="form-select form-select-sm" required>
                                        <option value="">Sin capturar</option>
                                        <?php $__currentLoopData = ['PRESENTE' => 'Presente', 'AUSENTE' => 'Ausente', 'RETARDO' => 'Retardo', 'JUSTIFICADO' => 'Justificado']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $valor => $etiqueta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($valor); ?>" <?php echo e($asistencia?->estado === $valor ? 'selected' : ''); ?>><?php echo e($etiqueta); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td><input type="text" name="alumnos[<?php echo e($alumno->numero_alumno); ?>][observaciones]" class="form-control form-control-sm" maxlength="500" value="<?php echo e($asistencia?->observaciones); ?>" placeholder="Opcional"></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay alumnos inscritos en este grupo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary" <?php echo e($alumnos->isEmpty() ? 'disabled' : ''); ?>>
                    <i class="bi bi-save me-1"></i> Guardar asistencia del grupo
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-info">Este grupo no tiene clases activas registradas en su horario semanal.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\grupos\asistencia.blade.php ENDPATH**/ ?>