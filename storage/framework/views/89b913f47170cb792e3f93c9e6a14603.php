<?php $__env->startSection('title', 'Detalle Sincronización Firebird'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .fb-log{background:var(--surface-elevated);border:1px solid var(--border);border-radius:10px;max-height:380px;overflow:auto;font-family:'JetBrains Mono',monospace;font-size:12px;line-height:1.7;padding:0;margin:0;list-style:none}
    .fb-log li{padding:4px 14px;border-bottom:1px solid color-mix(in srgb, var(--border) 40%, transparent);display:flex;gap:8px;align-items:flex-start}
    .fb-log li:last-child{border-bottom:none}
    .fb-log .tl-icon{flex-shrink:0;width:18px;text-align:center;margin-top:1px}
    .fb-log .tl-ok .tl-icon{color:var(--success)}
    .fb-log .tl-error .tl-icon{color:var(--danger)}
    .fb-log .tl-warn .tl-icon{color:var(--warning)}
    .fb-log .tl-skip .tl-icon{color:var(--text-tertiary)}
    .fb-log .tl-info .tl-icon{color:var(--info)}
    .fb-log .tl-msg{flex:1;word-break:break-word}
    .fb-log .tl-type{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:1px 5px;border-radius:3px;flex-shrink:0}
    .tl-ok .tl-type{background:rgba(16,185,129,.12);color:var(--success)}
    .tl-error .tl-type{background:rgba(239,68,68,.12);color:var(--danger)}
    .tl-warn .tl-type{background:rgba(251,191,36,.12);color:var(--warning)}
    .tl-skip .tl-type{background:rgba(148,163,184,.12);color:var(--text-tertiary)}
    .tl-info .tl-type{background:var(--primary-soft);color:var(--primary)}
    .fb-stat-grid .fb-stat{text-align:center;padding:16px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .fb-stat .fb-stat-value{font-size:28px;font-weight:700;font-family:'JetBrains Mono',monospace;line-height:1}
    .fb-stat .fb-stat-label{font-size:11px;color:var(--text-tertiary);margin-top:4px}
</style>

<div class="container-fluid px-4">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?php echo e(route('firebird.index')); ?>">Firebird</a></li><li class="breadcrumb-item active">Detalle #<?php echo e($sync->id); ?></li></ol></nav>
            <h1 class="h3 mb-1">
                Sincronización <span class="text-muted">#<?php echo e($sync->id); ?></span>
                <?php
                    $sc = match($sync->status) { 'completed'=>'success', 'failed'=>'danger', 'running'=>'primary', 'pending'=>'secondary', 'cancelled'=>'warning', default=>'secondary' };
                ?>
                <span class="badge bg-<?php echo e($sc); ?> ms-2" style="font-size:14px"><?php echo e($sync->statusLabel); ?></span>
            </h1>
        </div>
        <a href="<?php echo e(route('firebird.index')); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    
    <div class="fb-stat-grid row g-3 mb-4">
        <?php
            $statusIcon = match($sync->status) { 'completed'=>'check-circle', 'failed'=>'x-circle', 'running'=>'hourglass-split', 'pending'=>'clock', 'cancelled'=>'slash-circle', default=>'question-circle' };
            $duration = $sync->started_at && $sync->finished_at ? $sync->started_at->diffInSeconds($sync->finished_at) . 's' : ($sync->started_at ? '...' : '-');
        ?>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-<?php echo e($sc); ?>"><i class="bi bi-<?php echo e($statusIcon); ?>"></i></div><div class="fb-stat-label">Estado</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-primary" style="font-size:16px"><?php echo e($sync->operationLabel); ?></div><div class="fb-stat-label">Operación</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-info" style="font-size:16px"><?php echo e($sync->ciclo ?? 'N/A'); ?></div><div class="fb-stat-label">Ciclo</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-warning" style="font-size:16px"><?php echo e($duration); ?></div><div class="fb-stat-label">Duración</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-secondary" style="font-size:13px"><?php echo e($sync->started_at?->format('d/m/Y H:i') ?? '-'); ?></div><div class="fb-stat-label">Iniciada</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-secondary" style="font-size:13px"><?php echo e($sync->finished_at?->format('d/m/Y H:i') ?? '-'); ?></div><div class="fb-stat-label">Finalizada</div></div></div>
    </div>

    
    <?php if($sync->error_message): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
            <i class="bi bi-exclamation-triangle fs-5 mt-1"></i>
            <div>
                <strong>Error</strong><br>
                <span class="small"><?php echo e($sync->error_message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($sync->total > 0): ?>
        <div class="card mb-4">
            <div class="card-header py-2"><h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Progreso</h6></div>
            <div class="card-body py-3">
                <div class="row g-3 mb-3">
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-primary"><?php echo e($sync->processed); ?></div><div class="fb-stat-label">Procesados</div></div></div>
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-success"><?php echo e($sync->created_count); ?></div><div class="fb-stat-label">Creados</div></div></div>
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-info"><?php echo e($sync->updated_count); ?></div><div class="fb-stat-label">Actualizados</div></div></div>
                </div>
                <div class="progress mb-2" style="height:14px">
                    <div class="progress-bar bg-<?php echo e($sync->status === 'completed' ? 'success' : ($sync->status === 'failed' ? 'danger' : 'primary')); ?>"
                         role="progressbar"
                         style="width:<?php echo e(min(100, ($sync->processed / max(1, $sync->total)) * 100)); ?>%"
                         aria-valuenow="<?php echo e($sync->processed); ?>" aria-valuemin="0" aria-valuemax="<?php echo e($sync->total); ?>">
                        <?php echo e(round(min(100, ($sync->processed / max(1, $sync->total)) * 100), 1)); ?>%
                    </div>
                </div>
                <div class="small text-muted">Etapa: <strong><?php echo e($sync->stage); ?></strong></div>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Detalle (<?php echo e($items->total()); ?> items)</h6>
            <div class="btn-group btn-group-sm">
                <a href="?status=success" class="btn btn-outline-success <?php echo e(request('status') === 'success' ? 'active' : ''); ?>">Éxito</a>
                <a href="?status=error" class="btn btn-outline-danger <?php echo e(request('status') === 'error' ? 'active' : ''); ?>">Errores</a>
                <a href="?status=skipped" class="btn btn-outline-warning <?php echo e(request('status') === 'skipped' ? 'active' : ''); ?>">Omitidos</a>
                <a href="<?php echo e(route('firebird.sync', $sync)); ?>" class="btn btn-outline-secondary <?php echo e(!request('status') ? 'active' : ''); ?>">Todos</a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if($items->isEmpty()): ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="text-muted mt-2">No hay elementos para este filtro</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tabla</th>
                                <th>Acción</th>
                                <th>ID</th>
                                <th>Estado</th>
                                <th>Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $ac = match($item->action) { 'insert'=>'success', 'update'=>'info', 'delete'=>'danger', 'skip'=>'warning', 'error'=>'danger', default=>'secondary' };
                                    $is = match($item->status) { 'success'=>'success', 'error'=>'danger', 'skipped'=>'warning', default=>'secondary' };
                                ?>
                                <tr>
                                    <td><code class="small"><?php echo e($item->table_name); ?></code></td>
                                    <td><span class="badge bg-<?php echo e($ac); ?>"><?php echo e($item->actionLabel); ?></span></td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?php echo e($item->record_id); ?></td>
                                    <td><span class="badge bg-<?php echo e($is); ?>"><?php echo e($item->statusLabel); ?></span></td>
                                    <td class="text-muted small"><?php echo e($item->message ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer"><?php echo e($items->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if(!empty($sync->log)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-terminal me-2"></i>Log de Ejecución</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-log" aria-label="Copiar log al portapapeles">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="fb-log" role="log" aria-live="polite" id="fb-log-list">
                    <?php $__currentLoopData = $sync->log; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $tipo = $line['tipo'] ?? 'info';
                            $tlClass = match($tipo) { 'ok'=>'tl-ok', 'error'=>'tl-error', 'warning'=>'tl-warn', 'skip'=>'tl-skip', default=>'tl-info' };
                            $icon = match($tipo) { 'ok'=>'bi-check-circle-fill', 'error'=>'bi-x-circle-fill', 'warning'=>'bi-exclamation-triangle-fill', 'skip'=>'bi-dash-circle-fill', default=>'bi-info-circle-fill' };
                        ?>
                        <li class="<?php echo e($tlClass); ?>">
                            <span class="tl-icon"><i class="bi <?php echo e($icon); ?>"></i></span>
                            <span class="tl-type"><?php echo e($tipo); ?></span>
                            <span class="tl-msg"><?php echo e($line['msg']); ?></span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy log
    document.getElementById('btn-copy-log')?.addEventListener('click', function() {
        const lines = <?php echo json_encode($sync->log ?? [], 15, 512) ?>;
        const text = lines.map(l => '[' + (l.tipo || 'info') + '] ' + l.msg).join('\n');
        navigator.clipboard.writeText(text).then(function() {
            const btn = document.getElementById('btn-copy-log');
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i> Copiado';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            setTimeout(function() { btn.innerHTML = original; btn.classList.replace('btn-success', 'btn-outline-secondary'); }, 2000);
        });
    });

    // Auto-refresh if running
    <?php if($sync->status === 'running'): ?>
    setTimeout(function() { window.location.reload(); }, 5000);
    <?php endif; ?>
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\firebird\sync.blade.php ENDPATH**/ ?>