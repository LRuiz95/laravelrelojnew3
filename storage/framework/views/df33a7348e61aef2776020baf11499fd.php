<?php $__env->startSection('title', 'Sobrantes en Dispositivos'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-1">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            Sobrantes en Dispositivos
        </h5>
        <p class="text-secondary-token mb-0" style="font-size: 0.875rem;">
            Device_employee sin employee válido o con employee dado de baja.
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if(auth()->user()->isAdmin()): ?>
            <form method="GET" action="<?php echo e(route('employees.sobrantes')); ?>">
                <input type="hidden" name="ignored" value="<?php echo e($includeIgnored ? '0' : '1'); ?>">
                <?php if($deviceId): ?>
                    <input type="hidden" name="device_id" value="<?php echo e($deviceId); ?>">
                <?php endif; ?>
                <?php if($type): ?>
                    <input type="hidden" name="type" value="<?php echo e($type); ?>">
                <?php endif; ?>
                <?php if($search): ?>
                    <input type="hidden" name="q" value="<?php echo e($search); ?>">
                <?php endif; ?>
                <button class="btn btn-sm btn-ghost" type="submit">
                    <i class="bi bi-eye<?php echo e($includeIgnored ? '-slash' : ''); ?>"></i>
                    <?php echo e($includeIgnored ? 'Ocultar ignorados' : 'Mostrar ignorados'); ?>

                </button>
            </form>
        <?php endif; ?>
        <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-sm btn-ghost">
            <i class="bi bi-arrow-left"></i> Volver a Empleados
        </a>
    </div>
</div>


<form method="GET" action="<?php echo e(route('employees.sobrantes')); ?>" class="mb-4">
    <input type="hidden" name="ignored" value="<?php echo e($includeIgnored ? '1' : ''); ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" style="font-size:0.8rem">Dispositivo</label>
            <select name="device_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($device->id); ?>" <?php if($deviceId == $device->id): echo 'selected'; endif; ?>><?php echo e($device->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:0.8rem">Tipo</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="A" <?php if($type === 'A'): echo 'selected'; endif; ?>>A — Sin Catálogo</option>
                <option value="B" <?php if($type === 'B'): echo 'selected'; endif; ?>>B — Baja Firebird</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-size:0.8rem">Buscar</label>
            <input type="search" name="q" value="<?php echo e($search); ?>" class="form-control form-control-sm" placeholder="Nombre o ID de empleado...">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-sm btn-primary" type="submit">
                <i class="bi bi-search"></i> Filtrar
            </button>
            <?php if($deviceId || $type || $search): ?>
                <a href="<?php echo e(route('employees.sobrantes', $includeIgnored ? ['ignored' => '1'] : [])); ?>" class="btn btn-sm btn-ghost">Limpiar</a>
            <?php endif; ?>
        </div>
    </div>
</form>


<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-warning"><?php echo e($stats['total']); ?></div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Total Sobrantes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-danger"><?php echo e($stats['type_a']); ?></div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Tipo A (Sin Catálogo)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-info"><?php echo e($stats['type_b']); ?></div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Tipo B (Baja Firebird)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-secondary"><?php echo e($stats['ignored']); ?></div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Ignorados</div>
            </div>
        </div>
    </div>
</div>


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Dispositivo</th>
                        <th>UID</th>
                        <th>Empleado</th>
                        <th>ID Firebird</th>
                        <th>Razón</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $sobrantes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Tipo">
                                <?php if($row->sobrante_type === 'A'): ?>
                                    <span class="badge badge-with-dot cat-red">Tipo A</span>
                                <?php else: ?>
                                    <span class="badge badge-with-dot cat-orange">Tipo B</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Dispositivo">
                                <a href="<?php echo e(route('devices.show', $row->device_id)); ?>" class="ref-chip">
                                    <i class="bi bi-hdd-network"></i><?php echo e($row->device_name); ?>

                                </a>
                            </td>
                            <td data-label="UID"><code><?php echo e($row->device_uid); ?></code></td>
                            <td data-label="Empleado">
                                <?php if($row->employee_id): ?>
                                    <span class="avatar is-sm me-2"><?php echo e(strtoupper(substr($row->name ?? '?', 0, 1))); ?></span>
                                    <span class="fw-semibold"><?php echo e($row->name ?? '—'); ?></span>
                                <?php else: ?>
                                    <span class="text-secondary-token"><i class="bi bi-person-x me-1"></i>Sin catálogo</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="ID Firebird">
                                <?php if($row->user_id): ?>
                                    <code><?php echo e($row->user_id); ?></code>
                                <?php else: ?>
                                    <span class="text-secondary-token">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Razón">
                                <?php if($row->sobrante_type === 'A'): ?>
                                    <span class="text-danger" style="font-size:0.8rem;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <?php echo e($row->sobrante_reason); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="text-warning" style="font-size:0.8rem;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <?php echo e($row->sobrante_reason); ?>

                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Estado">
                                <?php if($row->ignored_at): ?>
                                    <span class="badge badge-with-dot cat-gray">Ignorado</span>
                                <?php elseif($row->active): ?>
                                    <span class="badge badge-with-dot cat-green">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-with-dot cat-gray">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="" class="text-end">
                                <div class="table-row-actions justify-content-end">
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <?php if($row->ignored_at): ?>
                                            <form action="<?php echo e(route('employees.sobrantes.unignore', [$row->device_id, $row->device_uid])); ?>" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-ghost" title="Restaurar" aria-label="Restaurar sobrante">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?php echo e(route('employees.sobrantes.ignore', [$row->device_id, $row->device_uid])); ?>" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-ghost" title="Ignorar" aria-label="Ignorar sobrante">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="<?php echo e(route('employees.sobrantes.remove', [$row->device_id, $row->device_uid, $row->sobrante_type])); ?>"
                                              method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-title="¿Eliminar sobrante?"
                                              data-confirm-message="Se eliminará del dispositivo<?php echo e($row->sobrante_type === 'B' ? ' y la relación local. Employee se conserva.' : '.'); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar sobrante">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8">
                                <?php echo $__env->make('partials.empty-state', [
                                    'icon'  => 'bi-check-circle',
                                    'title' => 'No hay sobrantes',
                                    'desc'  => 'Todos los enrolamientos en dispositivos tienen un empleado válido activo en el catálogo.',
                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3"><?php echo e($sobrantes->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\employees\sobrantes.blade.php ENDPATH**/ ?>