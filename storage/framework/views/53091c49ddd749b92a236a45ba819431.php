<?php $__env->startSection('title', $curso->nombre_curso . ' - ' . $ciclo->label); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo e($curso->nombre_curso); ?></h1>
        <p class="text-muted mb-0">
            <?php echo e($curso->clave_curso); ?> | <?php echo e($curso->nombre_curso); ?> · <?php echo e($curso->nivelRel?->descripcion ?? $curso->nivel); ?> · <?php echo e($curso->turno_nombre); ?> · <?php echo e($curso->sede?->descripcion ?? $curso->id_campus); ?>

            <span class="ms-2 badge bg-secondary"><?php echo e($alumnos->count()); ?> alumnos</span>
        </p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.cursos.edit', $curso)); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
    </div>
</div>


<div class="kpi-grid mb-4">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-book','label' => 'Materia','value' => $materia?->nombre_asignatura ?? 'No asignada','color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-book'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Materia'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($materia?->nombre_asignatura ?? 'No asignada'),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('blue')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-person-badge','label' => 'Maestros','value' => $docentes->count(),'color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-person-badge'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Maestros'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($docentes->count()),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('green')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-clock','label' => 'Horas Teoría','value' => $curso->materias->sum('horas_teoria'),'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-clock'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Horas Teoría'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($curso->materias->sum('horas_teoria')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-gear','label' => 'Horas Práctica','value' => $curso->materias->sum('horas_practica'),'color' => 'orange']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-gear'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Horas Práctica'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($curso->materias->sum('horas_practica')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('orange')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-mortarboard','label' => 'Créditos Totales','value' => $curso->materias->sum('creditos'),'color' => 'teal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-mortarboard'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Créditos Totales'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($curso->materias->sum('creditos')),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('teal')]); ?>
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
</div>


<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Materia del curso</span>
        <span class="badge bg-secondary"><?php echo e($materia?->clave_asignatura ?? 'Sin clave'); ?></span>
    </div>
    <div class="card-body p-0">
        <?php if(! $materia): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay una materia asignada a este curso</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Materia</th>
                            <th>Semestre</th>
                            <th class="text-center">Teoría</th>
                            <th class="text-center">Práctica</th>
                            <th class="text-center">Créditos</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $m = $materias->first();
                        ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($m->clave_asignatura); ?></td>
                                <td>
                                    <?php if($m->materia): ?>
                                        <?php echo e($m->materia->nombre_asignatura); ?>

                                    <?php else: ?>
                                        <span class="text-warning">Materia no encontrada</span>
                                        <small class="d-block text-muted">Verificar catálogo de materias</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($m->semestre ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($m->horas_teoria); ?></td>
                                <td class="text-center"><?php echo e($m->horas_practica); ?></td>
                                <td class="text-center"><?php echo e($m->materia?->creditos ?? '—'); ?></td>
                                <td>
                                    <span class="badge <?php echo e($m->tipo === 'obligatoria' ? 'bg-primary' : 'bg-secondary'); ?>">
                                        <?php echo e($m->tipo); ?>

                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--status <?php echo e($m->activo ? 'badge--active' : 'badge--inactive'); ?>">
                                        <?php echo e($m->activo ? 'Activa' : 'Inactiva'); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger" onclick="eliminarMateria(<?php echo e($m->id); ?>)" title="Quitar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header fw-bold">Maestro(s) asignado(s)</div>
    <div class="card-body">
        <?php $__empty_1 = true; $__currentLoopData = $docentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $docente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <span class="badge bg-light text-dark border me-2 mb-2"><?php echo e($docente->nombre_completo ?: $docente->clave_profesor); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span class="text-muted">No hay maestro asignado en los horarios de esta materia.</span>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Alumnos inscritos en el curso</span>
        <span class="badge bg-secondary"><?php echo e($alumnos->count()); ?> alumnos</span>
    </div>
    <div class="card-body p-0">
        <?php if($alumnos->isEmpty()): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-people fs-1 mb-2"></i>
                <p>No hay alumnos inscritos en grupos que cursen esta materia.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Alumno</th>
                            <th>Nivel / Carrera</th>
                            <th>Turno</th>
                            <th>Sede</th>
                            <th>Grupo</th>
                            <th>Contacto</th>
                            <th>Estatus</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $alumnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($alumno->numero_alumno); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo e($alumno->nombre_completo); ?></div>
                                    <small class="text-muted">CURP: <?php echo e($alumno->curp ?: 'No registrada'); ?></small>
                                </td>
                                <td>
                                    <div><?php echo e($alumno->nivelRel?->descripcion ?? $alumno->nivel ?? '—'); ?></div>
                                    <small class="text-muted"><?php echo e($alumno->carrera ?: 'Carrera no registrada'); ?></small>
                                </td>
                                <td><?php echo e($alumno->turno_base ? ($alumno->turno_base === 'M' ? 'Matutino' : 'Vespertino') : ($alumno->turno ?: '—')); ?></td>
                                <td><?php echo e($alumno->sede?->descripcion ?? $alumno->id_campus ?? '—'); ?></td>
                                <td><?php echo e($alumno->curso_codigo_grupo); ?></td>
                                <td class="small">
                                    <div><?php echo e($alumno->telefono ?: 'Sin teléfono'); ?></div>
                                    <div class="text-muted"><?php echo e($alumno->email ?: 'Sin correo'); ?></div>
                                </td>
                                <td><span class="badge badge--status <?php echo e($alumno->estatus === 'ACTIVO' ? 'badge--active' : 'badge--inactive'); ?>"><?php echo e($alumno->estatus ?: '—'); ?></span></td>
                                <td class="text-end"><a href="<?php echo e(route('academia.alumnos.show', $alumno)); ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="modalAgregarMateria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAgregarMateria">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Materia al Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Materia <span class="text-danger">*</span></label>
                            <select name="clave_asignatura" class="form-select" required id="materiaSelect">
                                <option value="">-- Seleccionar --</option>
                                <?php $__currentLoopData = \App\Models\Academia\Materia::activa()->where('id_plan', $curso->id_plan)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($m->clave_asignatura); ?>"><?php echo e($m->nombre_asignatura); ?> (<?php echo e($m->clave_asignatura); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semestre</label>
                            <input type="number" name="semestre" class="form-control" min="1" max="12">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Teoría</label>
                            <input type="number" name="horas_teoria" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Práctica</label>
                            <input type="number" name="horas_practica" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="obligatoria">Obligatoria</option>
                                <option value="optativa">Optativa</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formAgregarMateria').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/academia/cursos/<?php echo e($curso->id); ?>/materia', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
});

function eliminarMateria(id) {
    if (confirm('¿Quitar esta materia del curso?')) {
        fetch('/academia/cursos/<?php echo e($curso->id); ?>/materia/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\cursos\show.blade.php ENDPATH**/ ?>