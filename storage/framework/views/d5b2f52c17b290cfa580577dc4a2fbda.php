<?php $__env->startSection('title', $grupo->codigo_grupo . ' - ' . $ciclo->label); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo e($grupo->codigo_grupo); ?></h1>
        <p class="text-muted mb-0">
            <?php echo e($grupo->grado); ?>° · <?php echo e($grupo->turno_nombre); ?> · <?php echo e($grupo->nivelRel?->descripcion ?? $grupo->nivel); ?> · <?php echo e($grupo->modalidad_nombre); ?>

            <span class="ms-2 badge bg-secondary"><?php echo e($grupo->inscritos); ?> inscritos</span>
            <span class="ms-1 badge bg-secondary"><?php echo e($grupo->sede?->descripcion); ?></span>
        </p>
        <?php
            $partesCodigo = $grupo->codigo_grupo_partes;
        ?>
        <small class="text-muted">
            Plan <?php echo e($partesCodigo['anio_plan'] ?? '—'); ?> · Nivel <?php echo e($partesCodigo['nivel'] ?? $grupo->nivel); ?> ·
            Sede código <?php echo e($partesCodigo['sede'] ?? '—'); ?> · Modelo <?php echo e($partesCodigo['modelo'] ?? '—'); ?> ·
            Grado/grupo <?php echo e($partesCodigo['grado_grupo'] ?? '—'); ?>

            <?php if($partesCodigo['nivel_superior']): ?> · Ingeniería/Licenciatura (<?php echo e($partesCodigo['nivel_superior']); ?>) <?php endif; ?>
        </small>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo e(route('academia.grupos.asistencia', $grupo)); ?>" class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i> Asistencia
        </a>
    </div>
</div>


<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-alumnos">Alumnos (<?php echo e($alumnos->total()); ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-horarios">Horarios</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-conflictos">Conflictos Aula</button></li>
</ul>

<div class="tab-content">
    
    <div class="tab-pane fade show active" id="tab-alumnos">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre</th>
                                <th>Nivel / Carrera</th>
                                <th>Contacto</th>
                                <th>Estatus académico</th>
                                <th>Estatus en grupo</th>
                                <th>Inscripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $alumnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumnoGrupo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($alumnoGrupo->numero_alumno); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($alumnoGrupo->nombre_completo); ?></div>
                                        <small class="text-muted">CURP: <?php echo e($alumnoGrupo->curp ?: 'No registrada'); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo e($alumnoGrupo->nivelRel?->descripcion ?? $alumnoGrupo->nivel ?? '—'); ?></div>
                                        <small class="text-muted"><?php echo e($alumnoGrupo->carrera ?: 'Carrera no registrada'); ?></small>
                                    </td>
                                    <td class="small">
                                        <div><?php echo e($alumnoGrupo->telefono ?: 'Sin teléfono'); ?></div>
                                        <div class="text-muted"><?php echo e($alumnoGrupo->email ?: 'Sin correo'); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                            $estatusAcademico = strtoupper((string) $alumnoGrupo->estatus);
                                            $estatusAcademicoClase = $estatusAcademico === 'ACTIVO' ? 'badge--active' : 'badge--inactive';
                                        ?>
                                        <span class="badge badge--status <?php echo e($estatusAcademicoClase); ?>">
                                            <?php echo e($alumnoGrupo->estatus ?: '—'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge--status <?php echo e(($alumnoGrupo->pivot_estatus ?? null) === 'INSCRITO' ? 'badge--active' : 'badge--inactive'); ?>">
                                            <?php echo e($alumnoGrupo->pivot_estatus ?? '—'); ?>

                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo e($alumnoGrupo->pivot_fecha_inscripcion ? \Carbon\Carbon::parse($alumnoGrupo->pivot_fecha_inscripcion)->format('d/m/Y') : '—'); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('academia.alumnos.show', $alumnoGrupo)); ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php echo e($alumnos->links()); ?>

        </div>
    </div>

    
    <div class="tab-pane fade" id="tab-horarios">
        <?php if($horarios->isEmpty()): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-calendar-x fs-1 mb-2"></i>
                    <p>No hay horarios programados para este grupo</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php $__currentLoopData = $horarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia => $clases): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="<?php echo e($loop->first ? '' : 'border-top'); ?>">
                            <div class="p-3 bg-light border-bottom fw-semibold">
                                <span class="badge <?php echo e(in_array($dia, [6,7]) ? 'bg-purple' : 'bg-primary'); ?> me-2">
                                    <?php echo e(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$dia-1]); ?>

                                </span>
                                <?php echo e($clases->first()->sesionBase?->descripcion); ?>

                            </div>
                            <?php $__currentLoopData = $clases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                                    <div class="text-nowrap small text-muted" style="width: 120px;">
                                        <?php echo e($clase->sesionBase?->hora_inicio?->format('H:i')); ?> - <?php echo e($clase->sesionBase?->hora_fin?->format('H:i')); ?>

                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo e($clase->materia?->label); ?></div>
                                        <div class="small text-muted">
                                            <?php echo e($clase->profesor?->nombre_completo); ?> · <?php echo e($clase->ubicacion); ?> · <span class="badge <?php echo e($clase->tipoClase === 'PTC' ? 'bg-purple' : 'bg-info'); ?>"><?php echo e($clase->tipoClase); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="tab-pane fade" id="tab-conflictos">
        <?php if(empty($conflictos)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <p>No se detectaron conflictos de aula</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-danger-subtle">
                    <span class="fw-bold text-danger">Se detectaron <?php echo e(count($conflictos)); ?> conflicto(s) de aula</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Sesión</th>
                                    <th>Sede</th>
                                    <th>Edificio</th>
                                    <th>Aula</th>
                                    <th>Clases en conflicto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $conflictos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][$c->dia-1]); ?></td>
                                        <td><?php echo e($c->sesion); ?></td>
                                        <td><?php echo e($c->id_campus); ?></td>
                                        <td><?php echo e($c->edificio); ?></td>
                                        <td><?php echo e($c->aula); ?></td>
                                        <td><span class="badge bg-danger"><?php echo e($c->total); ?></span></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\grupos\show.blade.php ENDPATH**/ ?>