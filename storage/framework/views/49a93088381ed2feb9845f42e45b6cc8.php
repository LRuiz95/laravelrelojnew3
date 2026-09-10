<?php $__env->startSection('title', 'Empleados'); ?>

<?php $__env->startSection('content'); ?>


<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="employeeFilters" class="row g-3 align-items-end" method="GET">
            <div class="col-md-4">
                <label class="form-label small mb-1" for="employeeSearch">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search text-tertiary-token"></i></span>
                    <input type="search" name="q" id="employeeSearch" value="<?php echo e(request('q')); ?>" class="form-control" placeholder="Buscar empleado por nombre, ID o puesto…">
                </div>
            </div>

            <?php if(!empty($cargos) && count($cargos)): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterCargo">Puesto</label>
                    <select name="cargo" id="filterCargo" class="form-select">
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
                    <select name="departamento" id="filterDepto" class="form-select">
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
                    <select name="id_campus" id="filterSede" class="form-select">
                        <option value="">Todas</option>
                        <?php $__currentLoopData = $sedes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->id_campus); ?>" <?php if(request('id_campus') == $s->id_campus): echo 'selected'; endif; ?>><?php echo e($s->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2 d-flex gap-2 align-items-end">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Filtrar</button>
                <?php if(request()->hasAny(['q', 'cargo', 'departamento', 'id_campus'])): ?>
                    <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-ghost">Limpiar</a>
                <?php endif; ?>
            </div>

            
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
        </form>
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
    <div class="small text-tertiary-token">
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
            <table class="table table-hover align-middle mb-0 table-cards">
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
                <tbody>
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
                                <?php echo $__env->make('partials.empty-state', [
                                    'icon'     => request()->hasAny(['q','cargo','departamento','id_campus','sin_huella','sin_device'])
                                        ? 'bi-search' : 'bi-people',
                                    'title'    => request()->hasAny(['q','cargo','departamento','id_campus','sin_huella','sin_device'])
                                        ? 'Sin resultados para tu filtro' : 'No hay empleados',
                                    'desc'     => request()->hasAny(['q','cargo','departamento','id_campus','sin_huella','sin_device'])
                                        ? 'Prueba con otro nombre, ID, puesto o departamento, o limpia los filtros.'
                                        : 'Vacía los checadores con «Traer usuarios» o sincroniza Firebird EMPLEADOS para poblar el catálogo.',
                                    'cta'      => auth()->user()->isAdmin()
                                        ? ['label' => 'Agregar empleado', 'url' => route('employees.create')]
                                        : null,
                                    'ctaLink'  => true,
                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3"><?php echo e($employees->links()); ?></div>

<?php $__env->startPush('scripts'); ?>
<script>
// ═══════════════════════════════════════════════════════════
// Empleados — debounce + fetch JSON + render 6 cols
// ═══════════════════════════════════════════════════════════

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function setLoading(isLoading) {
    const tbody = document.querySelector('table tbody');
    const searchInput = document.getElementById('employeeSearch');

    if (isLoading) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">' +
            '<div class="spinner-border spinner-border-sm text-primary me-2" role="status">' +
            '<span class="visually-hidden">Cargando...</span></div>Buscando empleados...</td></tr>';
        searchInput.disabled = true;
    } else {
        searchInput.disabled = false;
    }
}

function renderEmployees(data) {
    const tbody = document.querySelector('table tbody');
    const pagination = document.querySelector('.pagination');

    if (!data.employees.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">' +
            '<i class="bi bi-people fs-1 text-secondary"></i>' +
            '<p class="mt-2 mb-0">No hay empleados</p></td></tr>';
        if (pagination) pagination.innerHTML = '';
        return;
    }

    const isAdmin = <?php echo e(auth()->user()->isAdmin() ? 'true' : 'false'); ?>;

    const rows = data.employees.map(emp => {
        // ── Empleado (col A) ──
        const initial = emp.name ? emp.name.charAt(0).toUpperCase() : '?';
        const antiguedad = emp.fecha_ingreso
            ? `<span class="mono small text-tertiary-token" title="Fecha ingreso">${emp.fecha_ingreso}</span>`
            : '';

        const empleadoHtml = `
            <td data-label="Empleado">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="avatar is-sm flex-shrink-0">${initial}</span>
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate" title="${emp.name || ''}" style="max-width:18ch">${emp.name || '—'}</div>
                        <div class="d-flex align-items-center gap-2">
                            <code class="small">${emp.user_id || ''}</code>
                            ${antiguedad}
                        </div>
                    </div>
                </div>
            </td>`;

        // ── Puesto (col B) ──
        const cargo = emp.cargo || '';
        const depto = emp.departamento || '';
        let puestoHtml;
        if (cargo) {
            puestoHtml = `<td data-label="Puesto">
                <div class="fw-semibold small text-truncate" title="${cargo}" style="max-width:20ch"><i class="bi bi-briefcase me-1 text-tertiary-token"></i>${cargo}</div>
                ${depto ? `<div class="small text-secondary-token text-truncate" style="max-width:20ch"><i class="bi bi-building me-1"></i>${depto}</div>` : ''}
            </td>`;
        } else {
            puestoHtml = `<td data-label="Puesto">
                <span class="small text-tertiary-token">—</span>
                ${depto ? `<div class="small text-secondary-token text-truncate" style="max-width:20ch"><i class="bi bi-building me-1"></i>${depto}</div>` : ''}
            </td>`;
        }

        // ── Adscripción (col C) ──
        const sedeLabel = emp.sede_label || emp.id_campus || '';
        const contrato = emp.contrato || '';
        const nivel = emp.nivel || '';
        let adscripcionHtml = '<td data-label="Sede"><div class="d-flex flex-wrap gap-1 align-items-center">';
        if (sedeLabel) {
            adscripcionHtml += `<span class="badge cat-blue"><i class="bi bi-geo-alt me-1"></i>${sedeLabel}</span>`;
        } else {
            adscripcionHtml += '<span class="small text-tertiary-token">—</span>';
        }
        if (contrato) adscripcionHtml += `<span class="badge cat-gray">${contrato}</span>`;
        if (nivel) adscripcionHtml += `<span class="badge cat-purple" title="Nivel ${nivel}">${nivel}</span>`;
        adscripcionHtml += '</div></td>';

        // ── Hardware (col D) ──
        const devices = emp.devices || [];
        const devicesCount = devices.length;
        let hardwareHtml = '<td data-label="Hardware"><div class="d-flex flex-wrap align-items-center gap-1">';

        if (devicesCount === 0) {
            hardwareHtml += '<span class="badge cat-gray">Sin enrolar</span>';
        } else {
            devices.slice(0, 2).forEach(d => {
                const pivot = d.pivot || {};
                const tipParts = [`UID ${pivot.device_uid || ''}`];
                if (pivot.card_number) tipParts.push(`Tarjeta ${pivot.card_number}`);
                hardwareHtml += `<a href="${window.location.origin}/devices/${d.id}" class="ref-chip" title="${tipParts.join(' · ')}"><i class="bi bi-hdd-network"></i>${d.name}</a>`;
            });
            if (devicesCount > 2) {
                const extraNames = devices.slice(2).map(d => d.name).join(', ');
                hardwareHtml += `<span class="badge cat-gray" title="${extraNames}">+${devicesCount - 2}</span>`;
            }
        }

        // Huellas
        const fpCount = emp.fingerprints_count || 0;
        const fpCat = fpCount === 0 ? 'cat-gray' : (fpCount < 3 ? 'cat-amber' : 'cat-green');
        hardwareHtml += `<span class="badge ${fpCat}" title="${fpCount} huellas guardadas"><i class="bi bi-fingerprint me-1"></i>${fpCount}</span>`;

        // Tarjeta icon
        if (emp.has_card) {
            hardwareHtml += '<span class="small text-tertiary-token" title="Con tarjeta RFID"><i class="bi bi-credit-card"></i></span>';
        }
        hardwareHtml += '</div>';

        // Último sync
        if (emp.last_sync) {
            const syncMap = { completed: 'cat-green', failed: 'cat-red', running: 'cat-amber', queued: 'cat-gray' };
            const sc = syncMap[emp.last_sync.status] || 'cat-gray';
            const syncDate = emp.last_sync.finished_at || emp.last_sync.created_at || '';
            hardwareHtml += `<div class="small mono text-tertiary-token mt-1 text-truncate" style="max-width:22ch" title="${emp.last_sync.stage || ''}${emp.last_sync.error_message ? ' · ' + emp.last_sync.error_message : ''}">
                <span class="badge ${sc}" style="font-size:10px">${emp.last_sync.status}</span>
                ${syncDate}
            </div>`;
        }
        hardwareHtml += '</td>';

        // ── Estado compuesto (col F) ──
        const isBaja = emp.is_baja || emp.status_actual === 'B';
        const fbStatus = emp.status_actual;
        const enrolled = devicesCount > 0;
        const anyActive = enrolled && devices.some(d => d.pivot && d.pivot.active);

        let estadoHtml = '<td data-label="Estado">';
        if (isBaja) {
            estadoHtml += '<span class="badge badge-with-dot cat-gray" title="STATUSACTUAL=B Baja nómina">Baja</span>';
        } else {
            estadoHtml += `<span class="badge badge-with-dot cat-green" title="STATUSACTUAL=${fbStatus || 'NULL→Activo'}">Activo</span>`;
        }
        estadoHtml += '<div class="small mt-1">';
        if (!enrolled) {
            estadoHtml += '<span class="badge badge-with-dot cat-gray">Sin enrolar</span>';
        } else if (anyActive) {
            estadoHtml += '<span class="badge badge-with-dot cat-green">Enrolado</span>';
        } else {
            estadoHtml += '<span class="badge badge-with-dot cat-amber">Inactivo</span>';
        }
        estadoHtml += '</div></td>';

        // ── Acciones (col G) ──
        let accionesHtml = '<td data-label=""><div class="table-row-actions justify-content-end">';
        accionesHtml += `<a href="${emp.edit_url || '#'}" class="btn btn-sm btn-ghost" title="Ver detalle / editar" aria-label="Ver ${emp.name || ''}"><i class="bi bi-eye"></i></a>`;

        if (isAdmin) {
            accionesHtml += `<a href="${emp.edit_url || '#'}" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>`;

            if (enrolled) {
                const deviceIds = devices.map(d => `<input type="hidden" name="device_ids[]" value="${d.id}">`).join('');
                accionesHtml += `<form action="${emp.sync_url || '#'}" method="POST" class="d-inline" data-sync>
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content ?? ''}">
                    ${deviceIds}
                    <button class="btn btn-sm btn-ghost" title="Re-sincronizar en ${devicesCount} checador(es)" aria-label="Sincronizar"><i class="bi bi-cloud-arrow-up"></i></button>
                </form>`;
            }

            accionesHtml += `<form action="${emp.destroy_url || '#'}" method="POST" class="d-inline" data-confirm data-confirm-danger data-confirm-title="¿Quitar a ${emp.name || ''}?" data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content ?? ''}">
                <input type="hidden" name="_method" value="DELETE">
                <button class="btn btn-sm btn-icon-danger" title="Dar de baja" aria-label="Dar de baja"><i class="bi bi-person-x"></i></button>
            </form>`;
        } else {
            devices.forEach(d => {
                accionesHtml += `<a href="/devices/${d.id}" class="btn btn-sm btn-ghost" title="Ver ${d.name}" aria-label="Ver dispositivo ${d.name}"><i class="bi bi-box-arrow-up-right"></i></a>`;
            });
        }
        accionesHtml += '</div></td>';

        return `<tr>${empleadoHtml}${puestoHtml}${adscripcionHtml}${hardwareHtml}${estadoHtml}${accionesHtml}</tr>`;
    }).join('');

    tbody.innerHTML = rows;

    // ── Paginación ──
    if (pagination && data.pagination) {
        const p = data.pagination;
        let paginationHtml = '';
        if (p.last_page > 1) {
            paginationHtml = '<nav><ul class="pagination pagination-sm justify-content-center">';
            if (p.current_page > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${p.current_page - 1}">&laquo;</a></li>`;
            }
            for (let i = 1; i <= p.last_page; i++) {
                if (i === 1 || i === p.last_page || (i >= p.current_page - 2 && i <= p.current_page + 2)) {
                    paginationHtml += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === p.current_page - 3 || i === p.current_page + 3) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            if (p.current_page < p.last_page) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${p.current_page + 1}">&raquo;</a></li>`;
            }
            paginationHtml += '</ul></nav>';
        }
        pagination.innerHTML = paginationHtml;

        pagination.querySelectorAll('a.page-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchEmployees(link.dataset.page);
            });
        });
    }
}

async function fetchEmployees(page = 1) {
    const search = document.getElementById('employeeSearch').value;
    const cargoEl = document.getElementById('filterCargo');
    const deptoEl = document.getElementById('filterDepto');
    const sedeEl = document.getElementById('filterSede');

    setLoading(true);

    try {
        const url = new URL('<?php echo e(route("employees.search")); ?>', window.location.origin);
        url.searchParams.set('q', search);
        url.searchParams.set('page', page);
        url.searchParams.set('status', '<?php echo e($status); ?>');
        if (cargoEl && cargoEl.value) url.searchParams.set('cargo', cargoEl.value);
        if (deptoEl && deptoEl.value) url.searchParams.set('departamento', deptoEl.value);
        if (sedeEl && sedeEl.value) url.searchParams.set('id_campus', sedeEl.value);

        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('Error en la petición');

        const data = await response.json();
        renderEmployees(data);
    } catch (error) {
        console.error('Error fetching employees:', error);
        setLoading(false);
        const tbody = document.querySelector('table tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">' +
            '<i class="bi bi-exclamation-triangle"></i> Error al cargar empleados. ' +
            '<button class="btn btn-sm btn-link" onclick="fetchEmployees()">Reintentar</button></td></tr>';
    }
}

// ── Eventos de búsqueda y filtros ──
const searchDebounced = debounce(() => fetchEmployees(1), 300);
document.getElementById('employeeSearch').addEventListener('input', searchDebounced);

['filterCargo', 'filterDepto', 'filterSede'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => fetchEmployees(1));
});

// ── Paginación delegada ──
document.addEventListener('click', (e) => {
    if (e.target.matches('.pagination a.page-link[data-page]')) {
        e.preventDefault();
        fetchEmployees(e.target.dataset.page);
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/employees/index.blade.php ENDPATH**/ ?>