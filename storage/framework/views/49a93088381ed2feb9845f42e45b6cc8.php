<?php $__env->startSection('title', 'Empleados'); ?>

<?php $__env->startSection('content'); ?>


<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?php if (isset($component)) { $__componentOriginale9f22847d79d6273acb27aff60f1f678 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9f22847d79d6273acb27aff60f1f678 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter-bar','data' => ['action' => route('employees.index'),'clearUrl' => route('employees.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('employees.index')),'clear-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('employees.index'))]); ?>
            <div class="col-md-4">
                <label class="form-label small mb-1" for="employeeSearch">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search text-tertiary-token"></i></span>
                    <input type="search" name="q" id="employeeSearch" value="<?php echo e(request('q')); ?>"
                           class="form-control" placeholder="Buscar empleado por nombre, ID o puesto…"
                           aria-label="Buscar empleados">
                </div>
            </div>

            <?php if(!empty($cargos) && count($cargos)): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterCargo">Puesto</label>
                    <select name="cargo" id="filterCargo" class="form-select" aria-label="Filtrar por puesto">
                        <option value="">Todos</option>
                        <?php $__currentLoopData = $cargos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option <?php if(request('cargo') === $c): echo 'selected'; endif; ?>><?php echo e($c); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if(!empty($departamentos) && count($departamentos)): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterDepto">Departamento</label>
                    <select name="departamento" id="filterDepto" class="form-select" aria-label="Filtrar por departamento">
                        <option value="">Todos</option>
                        <?php $__currentLoopData = $departamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option <?php if(request('departamento') === $d): echo 'selected'; endif; ?>><?php echo e($d); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if(!empty($sedes) && count($sedes)): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterSede">Sede</label>
                    <select name="id_campus" id="filterSede" class="form-select" aria-label="Filtrar por sede">
                        <option value="">Todas</option>
                        <?php $__currentLoopData = $sedes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->id_campus); ?>" <?php if(request('id_campus') == $s->id_campus): echo 'selected'; endif; ?>><?php echo e($s->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Filtrar</button>
            </div>

            
            <?php if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus', 'sin_huella', 'sin_device'])): ?>
                <div class="col-12 d-flex gap-2 flex-wrap align-items-center pt-1">
                    <span class="small text-tertiary-token me-1">Activos:</span>
                    <?php if(request('q')): ?>
                        <span class="badge cat-blue d-inline-flex align-items-center gap-1">
                            <i class="bi bi-search"></i> <?php echo e(request('q')); ?>

                            <a href="<?php echo e(route('employees.index', request()->except('q'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro búsqueda">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if(request('cargo')): ?>
                        <span class="badge cat-purple d-inline-flex align-items-center gap-1">
                            <i class="bi bi-briefcase"></i> <?php echo e(request('cargo')); ?>

                            <a href="<?php echo e(route('employees.index', request()->except('cargo'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro puesto">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if(request('departamento')): ?>
                        <span class="badge cat-blue d-inline-flex align-items-center gap-1">
                            <i class="bi bi-building"></i> <?php echo e(request('departamento')); ?>

                            <a href="<?php echo e(route('employees.index', request()->except('departamento'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro departamento">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if(request('id_campus')): ?>
                        <span class="badge cat-green d-inline-flex align-items-center gap-1">
                            <i class="bi bi-geo-alt"></i> Sede <?php echo e(request('id_campus')); ?>

                            <a href="<?php echo e(route('employees.index', request()->except('id_campus'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro sede">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if(request('sin_huella')): ?>
                        <span class="badge cat-amber d-inline-flex align-items-center gap-1">
                            <i class="bi bi-fingerprint"></i> Sin huellas
                            <a href="<?php echo e(route('employees.index', request()->except('sin_huella'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro sin huellas">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if(request('sin_device')): ?>
                        <span class="badge cat-amber d-inline-flex align-items-center gap-1">
                            <i class="bi bi-hdd-network"></i> Sin enrolar
                            <a href="<?php echo e(route('employees.index', request()->except('sin_device'))); ?>" class="ms-1 text-decoration-none" aria-label="Quitar filtro sin enrolar">&times;</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="col-12 d-flex gap-2 flex-wrap pt-1">
                <span class="small text-tertiary-token me-1">Rápidos:</span>
                <a href="<?php echo e(route('employees.index', array_merge(request()->query(), ['sin_huella' => 1]))); ?>" class="ref-chip">
                    <i class="bi bi-fingerprint"></i> Sin huellas
                </a>
                <a href="<?php echo e(route('employees.index', array_merge(request()->query(), ['sin_device' => 1]))); ?>" class="ref-chip">
                    <i class="bi bi-hdd-network"></i> Sin enrolar
                </a>
                <a href="<?php echo e(route('employees.sobrantes')); ?>" class="ref-chip">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Sobrantes
                </a>
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


<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo e($status === 'todos' ? 'active' : ''); ?>"
           href="<?php echo e(route('employees.index', array_merge(request()->query(), ['status' => 'todos']))); ?>">
            Todos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo e($status === 'activos' ? 'active' : ''); ?>"
           href="<?php echo e(route('employees.index', array_merge(request()->query(), ['status' => 'activos']))); ?>">
            Activos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo e($status === 'bajas' ? 'active' : ''); ?>"
           href="<?php echo e(route('employees.index', array_merge(request()->query(), ['status' => 'bajas']))); ?>">
            Bajas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo e(route('employees.sobrantes')); ?>">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            Sobrantes
            <?php if(($sobrantesStats['total'] ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo e($sobrantesStats['total']); ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>


<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div id="employees-counter" class="small text-tertiary-token">
        <i class="bi bi-people me-1"></i> <?php echo e($employees->total()); ?> empleados
        <?php if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus'])): ?>
            · filtrado por
            <span class="text-secondary-token">
                <?php echo e(request('q') ?: request('cargo') ?: request('departamento') ?: ('sede ' . request('id_campus'))); ?>

            </span>
        <?php endif; ?>
    </div>
    <?php if(auth()->user()->isAdmin()): ?>
        <a href="<?php echo e(route('employees.create')); ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Agregar empleado
        </a>
    <?php endif; ?>
</div>


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="employees-table" class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th style="width:28%">Empleado</th>
                        <th style="width:22%">Puesto</th>
                        <th style="width:14%">Adscripción</th>
                        <th style="width:20%">Hardware</th>
                        <th style="width:8%">Estado</th>
                        <th class="text-end" style="width:8%">Acciones</th>
                    </tr>
                </thead>
                <tbody data-is-admin="<?php echo e(auth()->user()->isAdmin() ? '1' : '0'); ?>">
                    <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            // ── Dispositivos y enrolamiento ──
                            $enrollment = $employee->devices->first();
                            $devicesCount = $employee->devices->count();

                            // ── Huellas (con fallbacks) ──
                            $fpCount = $employee->fingerprints_count ?? $employee->fingerprints->count() ?? 0;

                            // ── Estado Firebird ──
                            $fbStatus = $employee->status_actual ?? null;
                            $isBaja = $fbStatus === 'B';

                            // ── Estado enrolamiento ──
                            $enrolled = $devicesCount > 0;
                            $anyActive = $enrolled && $employee->devices->contains(fn($d) => $d->pivot->active);

                            // ── Color huellas por count ──
                            $fpCat = $fpCount === 0 ? 'cat-gray' : ($fpCount < 3 ? 'cat-amber' : 'cat-green');

                            // ── Último sync (si viene con latestSync eager o syncs) ──
                            $lastSync = null;
                            if (isset($employee->syncs) && method_exists($employee->syncs, 'first')) {
                                $lastSync = $employee->syncs->first();
                            } elseif (isset($employee->latestSync)) {
                                $lastSync = $employee->latestSync;
                            }

                            // ── Sede label (si existe relación o fallback a id_campus) ──
                            $sedeLabel = '—';
                            if (isset($employee->sede) && $employee->sede) {
                                $sedeLabel = $employee->sede->descripcion ?? $employee->id_campus ?? '—';
                            } elseif (!empty($employee->id_campus)) {
                                $sedeLabel = $employee->id_campus;
                            }
                        ?>
                        <tr>
                            
                            <td data-label="Empleado">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="avatar is-sm flex-shrink-0"><?php echo e(strtoupper(mb_substr($employee->name, 0, 1))); ?></span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" title="<?php echo e($employee->name); ?>" style="max-width:18ch">
                                            <?php echo e($employee->name); ?>

                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="small"><?php echo e($employee->user_id); ?></code>
                                            <?php if(!empty($employee->fecha_ingreso)): ?>
                                                <span class="mono small text-tertiary-token" title="Fecha ingreso <?php echo e(\Carbon\Carbon::parse($employee->fecha_ingreso)->format('d/m/Y')); ?>">
                                                    <?php echo e(\Carbon\Carbon::parse($employee->fecha_ingreso)->locale('es')->isoFormat('MMM YYYY')); ?>

                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            
                            <td data-label="Puesto">
                                <?php if(!empty($employee->cargo)): ?>
                                    <div class="fw-semibold small text-truncate" title="<?php echo e($employee->cargo); ?>" style="max-width:20ch">
                                        <i class="bi bi-briefcase me-1 text-tertiary-token"></i><?php echo e($employee->cargo); ?>

                                    </div>
                                    <div class="small text-secondary-token text-truncate" style="max-width:20ch">
                                        <?php if(!empty($employee->departamento)): ?>
                                            <i class="bi bi-building me-1"></i><?php echo e($employee->departamento); ?>

                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-tertiary-token">—</span>
                                    <?php if(!empty($employee->departamento)): ?>
                                        <div class="small text-secondary-token text-truncate" style="max-width:20ch">
                                            <i class="bi bi-building me-1"></i><?php echo e($employee->departamento); ?>

                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            
                            <td data-label="Sede">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <?php if($sedeLabel !== '—'): ?>
                                        <span class="badge cat-blue" title="<?php echo e($employee->id_campus ? 'ID_CAMPUS '.$employee->id_campus : ''); ?>">
                                            <i class="bi bi-geo-alt me-1"></i><?php echo e($sedeLabel); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="small text-tertiary-token">—</span>
                                    <?php endif; ?>
                                    <?php if(!empty($employee->contrato)): ?>
                                        <span class="badge cat-gray"><?php echo e($employee->contrato); ?></span>
                                    <?php endif; ?>
                                    <?php if(!empty($employee->nivel)): ?>
                                        <span class="badge cat-purple" title="Nivel <?php echo e($employee->nivel); ?>"><?php echo e($employee->nivel); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            
                            <td data-label="Hardware">
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <?php $__empty_2 = true; $__currentLoopData = $employee->devices->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                        <a href="<?php echo e(route('devices.show', $device)); ?>" class="ref-chip"
                                           title="UID <?php echo e($device->pivot->device_uid); ?> · <?php echo e($device->pivot->roleLabel()); ?> · <?php echo e($device->pivot->card_number ? 'Tarjeta '.$device->pivot->card_number : 'Sin tarjeta'); ?>">
                                            <i class="bi bi-hdd-network"></i><?php echo e($device->name); ?>

                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                        <span class="badge cat-gray">Sin enrolar</span>
                                    <?php endif; ?>
                                    <?php if($devicesCount > 2): ?>
                                        <span class="badge cat-gray" title="<?php echo e($employee->devices->slice(2)->pluck('name')->join(', ')); ?>">
                                            +<?php echo e($devicesCount - 2); ?>

                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="badge <?php echo e($fpCat); ?>" title="<?php echo e($fpCount); ?> huellas guardadas">
                                        <i class="bi bi-fingerprint me-1"></i><?php echo e($fpCount); ?>

                                    </span>
                                    
                                    <?php if($employee->devices->contains(fn($d) => filled($d->pivot->card_number))): ?>
                                        <span class="small text-tertiary-token" title="Con tarjeta RFID">
                                            <i class="bi bi-credit-card"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($lastSync): ?>
                                    <?php
                                        $scMap = ['completed' => 'cat-green', 'failed' => 'cat-red', 'running' => 'cat-amber', 'queued' => 'cat-gray'];
                                        $sc = $scMap[$lastSync->status] ?? 'cat-gray';
                                    ?>
                                    <div class="small mono text-tertiary-token mt-1 text-truncate" style="max-width:22ch"
                                         title="<?php echo e($lastSync->stage ?? ''); ?><?php echo e($lastSync->error_message ? ' · '.$lastSync->error_message : ''); ?>">
                                        <span class="badge <?php echo e($sc); ?>" style="font-size:10px"><?php echo e($lastSync->status); ?></span>
                                        <?php echo e($lastSync->finished_at?->format('d/m H:i') ?? $lastSync->created_at?->format('d/m H:i')); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            
                            <td data-label="Estado">
                                <?php if($isBaja): ?>
                                    <span class="badge badge-with-dot cat-gray" title="STATUSACTUAL=B Baja nómina">Baja</span>
                                <?php else: ?>
                                    <span class="badge badge-with-dot cat-green" title="STATUSACTUAL=<?php echo e($fbStatus ?? 'NULL→Activo'); ?>">Activo</span>
                                <?php endif; ?>
                                <div class="small mt-1">
                                    <?php if(!$enrolled): ?>
                                        <span class="badge badge-with-dot cat-gray">Sin enrolar</span>
                                    <?php elseif($anyActive): ?>
                                        <span class="badge badge-with-dot cat-green">Enrolado</span>
                                    <?php else: ?>
                                        <span class="badge badge-with-dot cat-amber">Inactivo</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            
                            <td data-label="">
                                <div class="table-row-actions justify-content-end">
                                    <a href="<?php echo e(route('employees.edit', $employee)); ?>" class="btn btn-sm btn-ghost"
                                       title="Ver detalle / editar" aria-label="Ver <?php echo e($employee->name); ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <a href="<?php echo e(route('employees.edit', $employee)); ?>" class="btn btn-sm btn-ghost"
                                           title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if($enrolled): ?>
                                            <form action="<?php echo e(route('employees.sync-devices', $employee)); ?>" method="POST" class="d-inline" data-sync>
                                                <?php echo csrf_field(); ?>
                                                <?php $__currentLoopData = $employee->devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <input type="hidden" name="device_ids[]" value="<?php echo e($d->id); ?>">
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <button class="btn btn-sm btn-ghost"
                                                        title="Re-sincronizar en <?php echo e($devicesCount); ?> checador(es)"
                                                        aria-label="Sincronizar">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="<?php echo e(route('employees.destroy', $employee)); ?>" method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-title="¿Quitar a <?php echo e($employee->name); ?>?"
                                              data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="btn btn-sm btn-icon-danger" title="Dar de baja" aria-label="Dar de baja">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php $__currentLoopData = $employee->devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <a href="<?php echo e(route('devices.show', $device)); ?>" class="btn btn-sm btn-ghost"
                                               title="Ver <?php echo e($device->name); ?>" aria-label="Ver dispositivo <?php echo e($device->name); ?>">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6">
                                <?php if(request()->hasAny(['q','cargo','departamento','id_campus','sin_huella','sin_device'])): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-search fs-1 text-secondary d-block mb-2"></i>
                                        <p class="fw-semibold mb-1">Sin resultados para tu filtro</p>
                                        <p class="small text-tertiary-token mb-3">Prueba con otro nombre, ID, puesto o departamento, o limpia los filtros.</p>
                                        <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-x-lg me-1"></i> Limpiar filtros
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                                        <p class="fw-semibold mb-1">No hay empleados</p>
                                        <p class="small text-tertiary-token mb-3">Vacía los checadores con «Traer usuarios» o sincroniza Firebird EMPLEADOS para poblar el catálogo.</p>
                                        <?php if(auth()->user()->isAdmin()): ?>
                                            <a href="<?php echo e(route('employees.create')); ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Agregar empleado
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="employees-pagination" class="mt-3"><?php echo e($employees->links()); ?></div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/employees/index.blade.php ENDPATH**/ ?>