<?php $__env->startSection('title', 'Asistencia de Clases'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Asistencia de Clases</h1>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-calendar me-1"></i> Cambiar ciclo
        </a>
    </div>
</div>


<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Nivel <span class="text-danger">*</span></label>
                <select name="nivel" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php $__currentLoopData = \App\Models\Academia\Nivel::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n->nivel); ?>" <?php echo e($filtros['nivel'] == $n->nivel ? 'selected' : ''); ?>><?php echo e($n->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Turno <span class="text-danger">*</span></label>
                <select name="turno" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php $__currentLoopData = \App\Models\Academia\Turno::activo()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t->turno); ?>" <?php echo e($filtros['turno'] == $t->turno ? 'selected' : ''); ?>><?php echo e($t->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                    <option value="">Todas</option>
                    <?php $__currentLoopData = $sedes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sede): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($sede->id_campus); ?>" <?php echo e($filtros['sede'] == $sede->id_campus ? 'selected' : ''); ?>><?php echo e($sede->descripcion); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Edificio</label>
                <select name="edificio" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $edificios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edificioOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($edificioOption); ?>" <?php echo e($filtros['edificio'] == $edificioOption ? 'selected' : ''); ?>><?php echo e($edificioOption); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Día</label>
                <select name="dia" class="form-select">
                    <?php $__currentLoopData = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($d); ?>" <?php echo e($filtros['dia'] == $d ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?php echo e($filtros['fecha']); ?>">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">Filtrar ubicación y horario</button>
            </div>
        </form>
    </div>
</div>

<?php if($filtros['nivel'] && $filtros['turno']): ?>
    
    <div class="kpi-grid mb-4">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-calendar-week','label' => 'Total clases','value' => $stats['total_clases'],'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-calendar-week'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Total clases'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total_clases']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?>
            <div class="kpi-trend flat">–</div>
         <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-check-circle','label' => 'Capturadas','value' => $stats['capturadas'] . '/' . $stats['total_clases'],'color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-check-circle'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Capturadas'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['capturadas'] . '/' . $stats['total_clases']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('green')]); ?>
            <div class="kpi-trend flat">–</div>
         <?php echo $__env->renderComponent(); ?>
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
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-check'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Presentes'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['presentes']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('success')]); ?>
            <div class="kpi-trend flat">–</div>
         <?php echo $__env->renderComponent(); ?>
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
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-x-circle'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Ausentes'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['ausentes']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('danger')]); ?>
            <div class="kpi-trend flat">–</div>
         <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-clock','label' => 'Retardos','value' => $stats['retardos'],'color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-clock'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Retardos'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['retardos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('warning')]); ?>
            <div class="kpi-trend flat">–</div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if($stats['total_clases'] > 0): ?>
            <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-graph-up','label' => 'Avance','value' => round(($stats['capturadas'] / max($stats['total_clases'], 1)) * 100) . '%','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-graph-up'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Avance'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(round(($stats['capturadas'] / max($stats['total_clases'], 1)) * 100) . '%'),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('info')]); ?>
                <div class="kpi-trend flat">–</div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php endif; ?>
    </div>

    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sesión / Hora</th>
                            <th>Docente / Materia</th>
                            <th>Sección</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $current_sesion = null; $current_ubicacion = null; ?>
                        <?php $__currentLoopData = $horarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $is_receso = ($cl['RECESO'] ?? '') === 'S';
                                $sesion_label = $cl['SESION'] ?? '?';
                                $hora_inicio = $cl['SESION_INI'] ?? '??:??';
                                $hora_fin = $cl['SESION_FIN'] ?? '??:??';
                                $estado = $cl['ASISTENCIA_ESTADO'] ?? null;
                                $estado_cls = $estado ? strtolower(str_replace(' ', '-', $estado)) : 'sin-captura';
                                $estado_label = $estado ?: 'Sin capturar';
                                $is_new_sesion = $sesion_label !== $current_sesion;
                                $ubicacion_key = ($cl['ID_CAMPUS'] ?? '') . '|' . ($cl['EDIFICIO'] ?? 'SIN EDIFICIO');
                                $is_new_ubicacion = $ubicacion_key !== $current_ubicacion;
                                $current_ubicacion = $ubicacion_key;
                                $current_sesion = $sesion_label;
                            ?>
                            <?php if($is_new_ubicacion): ?>
                                <tr class="table-primary">
                                    <td colspan="6" class="fw-semibold py-2">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?php echo e($cl['SEDE_NOMBRE'] ?? $cl['ID_CAMPUS'] ?? 'Sede sin definir'); ?>

                                        <span class="text-muted">· Edificio <?php echo e($cl['EDIFICIO'] ?? 'sin definir'); ?></span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if($is_receso): ?>
                                <tr class="table-warning">
                                    <td colspan="6" class="text-center py-3">
                                        <span class="badge bg-warning text-dark">RECESO</span> <?php echo e($hora_inicio); ?> - <?php echo e($hora_fin); ?>

                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr class="asist-row">
                                    <td class="asist-session">
                                        <?php if($is_new_sesion): ?>
                                            <strong>Ses. <?php echo e($sesion_label); ?></strong>
                                            <br><small class="text-muted"><?php echo e($hora_inicio); ?> - <?php echo e($hora_fin); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($cl['NOMBREPROFESOR']); ?></div>
                                        <div class="text-muted small"><?php echo e($cl['MATERIA_NOMBRE']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($cl['CODIGO_GRUPO']); ?></div>
                                        <div class="small text-muted">
                                            <?php echo e($cl['GRADO'] ?? ''); ?>° · <?php echo e($cl['TURNO'] ?? ''); ?>

                                            <span class="ms-1">· <?php echo e($cl['ALUMNOS_TOTAL']); ?> alumnos</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo e($cl['SEDE_NOMBRE'] ?? $cl['ID_CAMPUS'] ?? 'Sede sin definir'); ?></div>
                                        <div class="small text-muted">
                                            Edificio <?php echo e($cl['EDIFICIO'] ?? 'sin definir'); ?> · Aula <?php echo e($cl['AULA'] ?? 'sin definir'); ?>

                                        </div>
                                    </td>
                                    <td>
                                        <span class="asist-badge asist-badge--<?php echo e($estado_cls); ?>"><?php echo e($estado_label); ?></span>
                                        <?php if($cl['ASISTENCIA_OBS']): ?>
                                            <div class="small text-muted" title="<?php echo e($cl['ASISTENCIA_OBS']); ?>"><?php echo e(Str::limit($cl['ASISTENCIA_OBS'], 40)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick='openAsistDrawer(<?php echo e(json_encode([
                                                    "I"=>$cl["INICIAL"],"F"=>$cl["FINAL"],"P"=>$cl["PERIODO"],
                                                    "grupo"=>$cl["CODIGO_GRUPO"],"profesor"=>$cl["CLAVEPROFESOR"],
                                                    "asig"=>$cl["CLAVEASIGNATURA"],"dia"=>$cl["DIA"],"sesion"=>$cl["SESION"],
                                                    "fecha"=>$filtros["fecha"],"nombre"=>$cl["NOMBREPROFESOR"],
                                                    "materia"=>$cl["MATERIA_NOMBRE"],"grupoLabel"=>$cl["CODIGO_GRUPO"],
                                                    "sede"=>$cl["SEDE_NOMBRE"] ?? $cl["ID_CAMPUS"],"edificio"=>$cl["EDIFICIO"],"aula"=>$cl["AULA"],
                                                    "hora"=>$hora_inicio." - ".$hora_fin,
                                                    "estado"=>$estado,"obs"=>$cl["ASISTENCIA_OBS"] ?? ""
                                                ], JSON_HEX_APOS | JSON_HEX_TAG)); ?>)'>
                                            <?php echo e($estado ? 'Editar' : 'Capturar'); ?>

                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <?php if (isset($component)) { $__componentOriginale67024a204ba6be83f3556a31cc49b4d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale67024a204ba6be83f3556a31cc49b4d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.drawer','data' => ['id' => 'asistDrawer','title' => 'Capturar Asistencia','size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'asistDrawer','title' => 'Capturar Asistencia','size' => 'lg']); ?>
        <form method="POST" action="<?php echo e(route('academia.horarios.clase.asistencia.guardar')); ?>" id="asistForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="inicial" id="acInicial"><input type="hidden" name="final" id="acFinal"><input type="hidden" name="periodo" id="acPeriodo">
            <input type="hidden" name="codigo_grupo" id="acGrupo"><input type="hidden" name="clave_profesor" id="acProfesor"><input type="hidden" name="clave_asignatura" id="acAsignatura">
            <input type="hidden" name="dia" id="acDia"><input type="hidden" name="sesion" id="acSesion"><input type="hidden" name="fecha" id="acFecha">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label">Profesor</label><input type="text" id="acNombre" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Materia</label><input type="text" id="acMateria" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Grupo</label><input type="text" id="acGrupoLabel" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Sede</label><input type="text" id="acSede" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Edificio</label><input type="text" id="acEdificio" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Aula</label><input type="text" id="acAula" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Sesión</label><input type="text" id="acHora" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label">Fecha</label><input type="text" id="acFechaLabel" class="form-control" readonly></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Estado</label>
                <div class="asist-estado-btns">
                    <label class="asist-estado-btn asist-estado-btn--presente"><input type="radio" name="estado" value="PRESENTE" required><span>Presente</span></label>
                    <label class="asist-estado-btn asist-estado-btn--ausente"><input type="radio" name="estado" value="AUSENTE"><span>Ausente</span></label>
                    <label class="asist-estado-btn asist-estado-btn--retardo"><input type="radio" name="estado" value="RETARDO"><span>Retardo</span></label>
                    <label class="asist-estado-btn asist-estado-btn--justificado"><input type="radio" name="estado" value="JUSTIFICADO"><span>Justificado</span></label>
                </div>
            </div>
            <div class="mb-3"><label class="form-label" for="acObs">Observaciones</label><textarea name="observaciones" id="acObs" rows="3" maxlength="500" class="form-control" placeholder="Nota..."></textarea></div>
            <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-secondary" onclick="closeAsistDrawer()">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale67024a204ba6be83f3556a31cc49b4d)): ?>
<?php $attributes = $__attributesOriginale67024a204ba6be83f3556a31cc49b4d; ?>
<?php unset($__attributesOriginale67024a204ba6be83f3556a31cc49b4d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale67024a204ba6be83f3556a31cc49b4d)): ?>
<?php $component = $__componentOriginale67024a204ba6be83f3556a31cc49b4d; ?>
<?php unset($__componentOriginale67024a204ba6be83f3556a31cc49b4d); ?>
<?php endif; ?>

    <script>
    function openAsistDrawer(clase) {
        document.getElementById('acInicial').value = clase.I;
        document.getElementById('acFinal').value = clase.F;
        document.getElementById('acPeriodo').value = clase.P;
        document.getElementById('acGrupo').value = clase.grupo;
        document.getElementById('acProfesor').value = clase.profesor;
        document.getElementById('acAsignatura').value = clase.asig;
        document.getElementById('acDia').value = clase.dia;
        document.getElementById('acSesion').value = clase.sesion;
        document.getElementById('acFecha').value = clase.fecha;
        document.getElementById('acNombre').value = clase.nombre;
        document.getElementById('acMateria').value = clase.materia;
        document.getElementById('acGrupoLabel').value = clase.grupoLabel;
        document.getElementById('acSede').value = clase.sede || 'Sede no definida';
        document.getElementById('acEdificio').value = clase.edificio || 'Edificio no definido';
        document.getElementById('acAula').value = clase.aula || 'Aula no definida';
        document.getElementById('acHora').value = clase.hora;
        document.getElementById('acFechaLabel').value = clase.fecha;
        document.querySelectorAll('input[name="estado"]').forEach(r => r.checked = r.value === (clase.estado || ''));
        document.getElementById('acObs').value = clase.obs || '';
        new bootstrap.Offcanvas(document.getElementById('asistDrawer')).show();
    }
    function closeAsistDrawer() { bootstrap.Offcanvas.getInstance(document.getElementById('asistDrawer'))?.hide(); }
    </script>

<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-funnel display-4 text-muted"></i>
            <h5 class="mt-3 text-muted">Selecciona nivel y turno</h5>
            <p class="text-muted">Usa los filtros superiores para ver la asistencia de clases.</p>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\horarios\clase.blade.php ENDPATH**/ ?>