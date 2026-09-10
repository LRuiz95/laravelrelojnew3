

<?php $__env->startSection('title', 'Editar empleado'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?php echo e(route('employees.index')); ?>" class="ref-chip text-decoration-none">Empleados</a>
            <i class="bi bi-chevron-right small text-tertiary-token"></i>
            <span class="small text-tertiary-token">Editar</span>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="avatar is-lg"><?php echo e(strtoupper(substr($employee->name, 0, 1))); ?></span>
            <div>
                <div class="employee-panel-kicker">Catálogo central · Edición</div>
                <h1 class="h4 mb-1 d-flex align-items-center gap-2 flex-wrap">
                    <?php echo e($employee->name); ?>

                    <?php $enrollment = $employee->devices->first(); ?>
                    <?php if($employee->status_actual === 'B'): ?>
                        <span class="badge badge-with-dot cat-gray">Baja</span>
                    <?php elseif(!$enrollment): ?>
                        <span class="badge badge-with-dot cat-gray">Sin enrolar</span>
                    <?php elseif($enrollment->pivot->active): ?>
                        <span class="badge badge-with-dot cat-green">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-with-dot cat-gray">Inactivo</span>
                    <?php endif; ?>
                </h1>
                <p class="text-muted mb-0 small">ID <code><?php echo e($employee->user_id); ?></code>
                    <span class="mono text-tertiary-token ms-2">· <?php echo e($employee->type_label ?? '—'); ?></span>
                    <?php if($employee->departamento): ?><span class="text-tertiary-token"> · <?php echo e($employee->departamento); ?></span><?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <span class="employee-id-badge"><i class="bi bi-person-badge"></i>ID <?php echo e($employee->user_id); ?></span>
</div>


<div class="kpi-grid mb-4">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-fingerprint','value' => $employee->fingerprints->unique('finger')->count(),'label' => 'Huellas disponibles','color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-fingerprint','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($employee->fingerprints->unique('finger')->count()),'label' => 'Huellas disponibles','color' => 'purple']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-hdd-network','value' => $employee->devices->count() . '/' . $totalDevices,'label' => 'Dispositivos enrolados','color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-hdd-network','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($employee->devices->count() . '/' . $totalDevices),'label' => 'Dispositivos enrolados','color' => 'blue']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-card-text','value' => $employee->devices->filter(fn($d)=>filled($d->pivot->card_number))->count(),'label' => 'Tarjetas asignadas','color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bi-card-text','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($employee->devices->filter(fn($d)=>filled($d->pivot->card_number))->count()),'label' => 'Tarjetas asignadas','color' => 'green']); ?>
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

<div class="row g-4 align-items-start">
    
    <div class="col-12 col-xl-8">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-person me-2 text-tertiary-token"></i>Identidad</h2>
                <span class="small text-tertiary-token"><i class="bi bi-info-circle me-1"></i>Catálogo central</span>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('employees.update', $employee)); ?>" method="POST" novalidate>
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('name', $employee->name)); ?>" maxlength="24" required
                                   aria-describedby="name-help" autocomplete="name">
                            <div id="name-help" class="form-text">Máximo 24 caracteres. Se refleja en todos los checadores tras sincronizar.</div>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="user_id_display" class="form-label">ID / Badge</label>
                            <input type="text" id="user_id_display" class="form-control" value="<?php echo e($employee->user_id); ?>" readonly disabled>
                            <div class="form-text">No editable aquí. Si necesitas cambiar badge, duplica/reenrola (escalar a soporte).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado catálogo</label>
                            <div class="pt-1">
                                <span class="badge badge-with-dot <?php echo e($employee->status_actual === 'B' ? 'cat-gray' : 'cat-green'); ?>">
                                    <?php echo e($employee->status_actual_label ?? ($employee->status_actual === 'B' ? 'Baja' : 'Activo')); ?>

                                </span>
                            </div>
                            <div class="form-text">Bajas se gestionan desde la tabla con confirmación.</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-shield-lock me-2 text-tertiary-token"></i>Credenciales de acceso</h2>
                <span class="badge cat-amber"><i class="bi bi-exclamation-circle me-1"></i>Propaga a <?php echo e($employee->devices->count() ?: '—'); ?> checadores al sincronizar</span>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('employees.update', $employee)); ?>" method="POST" id="employee-credentials-form" novalidate>
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <input type="hidden" name="name" value="<?php echo e(old('name', $employee->name)); ?>" data-sync-name>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">PIN / Contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   maxlength="8" inputmode="numeric" pattern="[0-9]{1,8}"
                                   placeholder="Conservar actual" aria-describedby="password-help" autocomplete="off">
                            <div id="password-help" class="form-text">Solo números, 1–8 dígitos. Vacío = no cambia. Se aplica al sincronizar.</div>
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Rol en checador</label>
                            <select id="role" name="role" class="form-select <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" aria-describedby="role-help">
                                <?php $currentRole = old('role', $employee->devices->first()?->pivot->role ?? 0); ?>
                                <?php $__currentLoopData = \App\Models\Employee::roles(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if($currentRole == $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div id="role-help" class="form-text">0 = Usuario · 13 = Supervisor · 14 = Admin.</div>
                            <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Nombre, PIN y rol se guardan en el catálogo y se <strong>propagan a los <?php echo e($employee->devices->count()); ?> checador(es) enrolados</strong> solo al ejecutar "Sincronizar". Si no está enrolado, quedan solo en catálogo.
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-hdd-network me-2 text-tertiary-token"></i>Dispositivos enrolados · <?php echo e($employee->devices->count()); ?></h2>
                <?php if($employee->devices->isNotEmpty()): ?>
                    <span class="small text-tertiary-token">Tarjeta por checador</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php $__empty_1 = true; $__currentLoopData = $employee->devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="employee-device-entry">
                        <div class="employee-device-meta">
                            <a href="<?php echo e(route('devices.show', $device)); ?>" class="ref-chip" title="UID <?php echo e($device->pivot->device_uid); ?> en <?php echo e($device->name); ?>">
                                <i class="bi bi-hdd-network"></i><?php echo e($device->name); ?>

                            </a>
                            <span class="mono text-secondary-token small">UID <?php echo e($device->pivot->device_uid); ?></span>
                        </div>
                        <form action="<?php echo e(route('employees.update-card', $employee)); ?>" method="POST" class="mt-2" novalidate>
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="device_id" value="<?php echo e($device->id); ?>">
                            <label class="form-label small mb-1" for="card-<?php echo e($device->id); ?>">Código de tarjeta</label>
                            <div class="input-group" style="max-width: 380px">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" id="card-<?php echo e($device->id); ?>" name="card_number"
                                       class="form-control <?php $__errorArgs = ['card_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('card_number', $device->pivot->card_number)); ?>"
                                       inputmode="numeric" maxlength="10" pattern="[0-9]+"
                                       placeholder="Sin tarjeta" aria-label="Código de tarjeta para <?php echo e($device->name); ?>">
                                <button class="btn btn-outline-primary" type="submit">Guardar</button>
                            </div>
                            <?php $__errorArgs = ['card_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="form-text">Solo números, máx. 10 dígitos.</div>
                        </form>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Este empleado no está enrolado en ningún checador; los cambios se guardarán solo en el catálogo.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($syncDevices->isNotEmpty()): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0"><i class="bi bi-send me-2 text-tertiary-token"></i>Enviar a dispositivos</h2>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all-devices>Seleccionar todos</button>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('employees.sync-devices', $employee)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <fieldset>
                        <legend class="visually-hidden">Selecciona checadores destino</legend>
                        <div class="employee-device-select-list">
                            <?php $__currentLoopData = $syncDevices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $syncDevice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="employee-device-select">
                                    <input type="checkbox" name="device_ids[]" value="<?php echo e($syncDevice->id); ?>"
                                           <?php if($employee->devices->contains('id', $syncDevice->id)): echo 'checked'; endif; ?>>
                                    <span>
                                        <strong><?php echo e($syncDevice->name); ?></strong>
                                        <small><?php echo e($syncDevice->ip); ?> · <?php echo e($employee->devices->contains('id', $syncDevice->id) ? 'Actualizar acceso' : 'Agregar acceso'); ?></small>
                                    </span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </fieldset>
                    <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i> Sincronizar seleccionados</button>
                    <div class="form-text mt-2">Envía nombre, PIN, tarjeta, rol y todas las huellas. Cada checador genera una tarea en la cola.</div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="col-12 col-xl-4">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-fingerprint me-2"></i>Huellas guardadas</h2>
                <span class="badge <?php echo e($employee->fingerprints->count() ? 'cat-green' : 'cat-gray'); ?>"><?php echo e($employee->fingerprints->count()); ?></span>
            </div>
            <div class="card-body">
                <?php $__empty_1 = true; $__currentLoopData = $employee->fingerprints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fingerprint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="employee-fingerprint-row">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar is-sm" style="background: var(--primary-soft); color: var(--primary)"><i class="bi bi-fingerprint"></i></span>
                                <div>
                                    <div class="fw-semibold small">Dedo <?php echo e($fingerprint->finger); ?></div>
                                    <span class="badge cat-gray"><?php echo e($fingerprint->device?->name ?? 'Origen desconocido'); ?></span>
                                </div>
                            </div>
                            <code class="small"><?php echo e(substr($fingerprint->template_hash, 0, 12)); ?>…</code>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php if($fingerprint->device_id && $employee->devices->count() > 1): ?>
                                <form action="<?php echo e(route('employees.copy-fingerprint', [$employee, $fingerprint])); ?>" method="POST" class="d-flex gap-1 flex-grow-1" style="max-width: 260px">
                                    <?php echo csrf_field(); ?>
                                    <select name="device_id" class="form-select form-select-sm" aria-label="Checador destino para dedo <?php echo e($fingerprint->finger); ?>" required>
                                        <option value="">Copiar a…</option>
                                        <?php $__currentLoopData = $employee->devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $targetDevice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($targetDevice->id !== $fingerprint->device_id): ?>
                                                <option value="<?php echo e($targetDevice->id); ?>"><?php echo e($targetDevice->name); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Copiar huella y conservar original"><i class="bi bi-copy"></i></button>
                                </form>
                            <?php endif; ?>
                            <form action="<?php echo e(route('employees.delete-fingerprint', [$employee, $fingerprint])); ?>" method="POST"
                                  data-confirm data-confirm-danger
                                  data-confirm-title="¿Quitar esta huella?"
                                  data-confirm-message="Se eliminará el dedo <?php echo e($fingerprint->finger); ?> del empleado y del checador de origen.">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-icon-danger" type="submit" title="Quitar huella"><i class="bi bi-trash me-1"></i>Quitar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty-state', [
                        'icon' => 'bi-fingerprint',
                        'title' => 'Sin huellas sincronizadas',
                        'desc' => 'Este empleado no tiene huellas. Extrae desde el checador o enrola biométricamente.',
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>

                <?php if(auth()->user()->isAdmin() && $employee->devices->isNotEmpty()): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php $__currentLoopData = $employee->devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <form action="<?php echo e(route('devices.sync-fingerprints', $device)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="employee_id" value="<?php echo e($employee->id); ?>">
                                <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i> Actualizar desde <?php echo e($device->name); ?></button>
                            </form>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($employee->syncs->isNotEmpty()): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h2 class="h6 mb-0"><i class="bi bi-clock-history me-2 text-tertiary-token"></i>Historial de sincronización</h2>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php $__currentLoopData = $employee->syncs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sync): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $syncClass = ['completed'=>'cat-green','failed'=>'cat-red','running'=>'cat-amber','queued'=>'cat-gray'][$sync->status] ?? 'cat-gray'; ?>
                        <div class="list-group-item d-flex align-items-center gap-3 py-3">
                            <span class="badge <?php echo e($syncClass); ?>"><?php echo e(strtoupper(substr($sync->status,0,1))); ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-truncate"><?php echo e($sync->device?->name ?? 'Dispositivo eliminado'); ?> · <?php echo e($sync->operation_label); ?></div>
                                <div class="small text-tertiary-token text-truncate"><?php echo e($sync->stage); ?> · <?php echo e($sync->created_at?->format('d/m/Y H:i')); ?><?php if($sync->error_message): ?> · <?php echo e($sync->error_message); ?><?php endif; ?></div>
                            </div>
                            <span class="mono small text-secondary-token"><?php echo e($sync->processed); ?>/<?php echo e($sync->total); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="card-footer bg-transparent text-center py-2">
                    <a href="<?php echo e(route('operations.queue')); ?>" class="btn btn-sm btn-ghost">Ver cola completa <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="card shadow-sm border" style="border-color: var(--border) !important;">
            <div class="card-body">
                <h3 class="h6 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Zona de riesgo</h3>
                <p class="small text-secondary-token mb-3">Dar de baja elimina accesos en todos los checadores. Se conserva histórico de checadas.</p>
                <form action="<?php echo e(route('employees.destroy', $employee)); ?>" method="POST"
                      data-confirm data-confirm-danger
                      data-confirm-title="¿Quitar a <?php echo e($employee->name); ?>?"
                      data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-person-x me-1"></i> Dar de baja empleado</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Sincronizar input name visible con hidden de credenciales
document.getElementById('name')?.addEventListener('input', e => {
    const h = document.querySelector('[data-sync-name]');
    if (h) h.value = e.target.value;
});
document.querySelector('[data-select-all-devices]')?.addEventListener('click', (event) => {
    const cbs = document.querySelectorAll('.employee-device-select input[type="checkbox"]');
    const anyUnchecked = [...cbs].some(cb => !cb.checked);
    cbs.forEach(cb => cb.checked = anyUnchecked);
    event.currentTarget.textContent = anyUnchecked ? 'Quitar selección' : 'Seleccionar todos';
});
// Spinner on submit para sync forms
document.querySelectorAll('form[action*="/sync-"]').forEach(form => {
    form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando…';
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\employees\edit.blade.php ENDPATH**/ ?>