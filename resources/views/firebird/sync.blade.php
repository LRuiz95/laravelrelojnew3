@extends('layouts.admin')

@section('title', 'Detalle Sincronización Firebird')

@section('content')
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
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('firebird.index') }}">Firebird</a></li><li class="breadcrumb-item active">Detalle #{{ $sync->id }}</li></ol></nav>
            <h1 class="h3 mb-1">
                Sincronización <span class="text-muted">#{{ $sync->id }}</span>
                @php
                    $sc = match($sync->status) { 'completed'=>'success', 'failed'=>'danger', 'running'=>'primary', 'pending'=>'secondary', 'cancelled'=>'warning', default=>'secondary' };
                @endphp
                <span class="badge bg-{{ $sc }} ms-2" style="font-size:14px">{{ $sync->statusLabel }}</span>
            </h1>
        </div>
        <a href="{{ route('firebird.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    {{-- Info grid --}}
    <div class="fb-stat-grid row g-3 mb-4">
        @php
            $statusIcon = match($sync->status) { 'completed'=>'check-circle', 'failed'=>'x-circle', 'running'=>'hourglass-split', 'pending'=>'clock', 'cancelled'=>'slash-circle', default=>'question-circle' };
            $duration = $sync->started_at && $sync->finished_at ? $sync->started_at->diffInSeconds($sync->finished_at) . 's' : ($sync->started_at ? '...' : '-');
        @endphp
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-{{ $sc }}"><i class="bi bi-{{ $statusIcon }}"></i></div><div class="fb-stat-label">Estado</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-primary" style="font-size:16px">{{ $sync->operationLabel }}</div><div class="fb-stat-label">Operación</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-info" style="font-size:16px">{{ $sync->ciclo ?? 'N/A' }}</div><div class="fb-stat-label">Ciclo</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-warning" style="font-size:16px">{{ $duration }}</div><div class="fb-stat-label">Duración</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-secondary" style="font-size:13px">{{ $sync->started_at?->format('d/m/Y H:i') ?? '-' }}</div><div class="fb-stat-label">Iniciada</div></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="fb-stat"><div class="fb-stat-value text-secondary" style="font-size:13px">{{ $sync->finished_at?->format('d/m/Y H:i') ?? '-' }}</div><div class="fb-stat-label">Finalizada</div></div></div>
    </div>

    {{-- Error --}}
    @if($sync->error_message)
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
            <i class="bi bi-exclamation-triangle fs-5 mt-1"></i>
            <div>
                <strong>Error</strong><br>
                <span class="small">{{ $sync->error_message }}</span>
            </div>
        </div>
    @endif

    {{-- Progreso --}}
    @if($sync->total > 0)
        <div class="card mb-4">
            <div class="card-header py-2"><h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Progreso</h6></div>
            <div class="card-body py-3">
                <div class="row g-3 mb-3">
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-primary">{{ $sync->processed }}</div><div class="fb-stat-label">Procesados</div></div></div>
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-success">{{ $sync->created_count }}</div><div class="fb-stat-label">Creados</div></div></div>
                    <div class="col-4"><div class="fb-stat"><div class="fb-stat-value text-info">{{ $sync->updated_count }}</div><div class="fb-stat-label">Actualizados</div></div></div>
                </div>
                <div class="progress mb-2" style="height:14px">
                    <div class="progress-bar bg-{{ $sync->status === 'completed' ? 'success' : ($sync->status === 'failed' ? 'danger' : 'primary') }}"
                         role="progressbar"
                         style="width:{{ min(100, ($sync->processed / max(1, $sync->total)) * 100) }}%"
                         aria-valuenow="{{ $sync->processed }}" aria-valuemin="0" aria-valuemax="{{ $sync->total }}">
                        {{ round(min(100, ($sync->processed / max(1, $sync->total)) * 100), 1) }}%
                    </div>
                </div>
                <div class="small text-muted">Etapa: <strong>{{ $sync->stage }}</strong></div>
            </div>
        </div>
    @endif

    {{-- Items --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Detalle ({{ $items->total() }} items)</h6>
            <div class="btn-group btn-group-sm">
                <a href="?status=success" class="btn btn-outline-success {{ request('status') === 'success' ? 'active' : '' }}">Éxito</a>
                <a href="?status=error" class="btn btn-outline-danger {{ request('status') === 'error' ? 'active' : '' }}">Errores</a>
                <a href="?status=skipped" class="btn btn-outline-warning {{ request('status') === 'skipped' ? 'active' : '' }}">Omitidos</a>
                <a href="{{ route('firebird.sync', $sync) }}" class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">Todos</a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($items->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="text-muted mt-2">No hay elementos para este filtro</p>
                </div>
            @else
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
                            @foreach($items as $item)
                                @php
                                    $ac = match($item->action) { 'insert'=>'success', 'update'=>'info', 'delete'=>'danger', 'skip'=>'warning', 'error'=>'danger', default=>'secondary' };
                                    $is = match($item->status) { 'success'=>'success', 'error'=>'danger', 'skipped'=>'warning', default=>'secondary' };
                                @endphp
                                <tr>
                                    <td><code class="small">{{ $item->table_name }}</code></td>
                                    <td><span class="badge bg-{{ $ac }}">{{ $item->actionLabel }}</span></td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px">{{ $item->record_id }}</td>
                                    <td><span class="badge bg-{{ $is }}">{{ $item->statusLabel }}</span></td>
                                    <td class="text-muted small">{{ $item->message ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $items->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Log --}}
    @if(!empty($sync->log))
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-terminal me-2"></i>Log de Ejecución</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-log" aria-label="Copiar log al portapapeles">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="fb-log" role="log" aria-live="polite" id="fb-log-list">
                    @foreach($sync->log as $line)
                        @php
                            $tipo = $line['tipo'] ?? 'info';
                            $tlClass = match($tipo) { 'ok'=>'tl-ok', 'error'=>'tl-error', 'warning'=>'tl-warn', 'skip'=>'tl-skip', default=>'tl-info' };
                            $icon = match($tipo) { 'ok'=>'bi-check-circle-fill', 'error'=>'bi-x-circle-fill', 'warning'=>'bi-exclamation-triangle-fill', 'skip'=>'bi-dash-circle-fill', default=>'bi-info-circle-fill' };
                        @endphp
                        <li class="{{ $tlClass }}">
                            <span class="tl-icon"><i class="bi {{ $icon }}"></i></span>
                            <span class="tl-type">{{ $tipo }}</span>
                            <span class="tl-msg">{{ $line['msg'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy log
    document.getElementById('btn-copy-log')?.addEventListener('click', function() {
        const lines = @json($sync->log ?? []);
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
    @if($sync->status === 'running')
    setTimeout(function() { window.location.reload(); }, 5000);
    @endif
});
</script>
@endpush
