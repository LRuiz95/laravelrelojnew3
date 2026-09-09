@extends('layouts.admin')

@section('title', 'Cola de sincronización')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="h5 mb-1">Cola de sincronización</h2>
        <p class="text-muted mb-0">Consulta qué operaciones están pendientes, en proceso o terminadas.</p>
    </div>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-hdd-network me-1"></i> Ver dispositivos
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-2">
                <label class="form-label small mb-1" for="type">Tipo</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos</option>
                    <option value="firebird" @selected(request('type') === 'firebird')>Firebird</option>
                    <option value="device" @selected(request('type') === 'device')>Dispositivo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1" for="status">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    @foreach (['queued' => 'En cola', 'running' => 'Procesando', 'completed' => 'Completada', 'failed' => 'Fallida'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="operation">Operación</label>
                <select class="form-select" id="operation" name="operation">
                    <option value="">Todas</option>
                    @foreach (['sync_full' => 'Empleado completo', 'all' => 'Todo', 'users' => 'Usuarios', 'attendances' => 'Asistencias', 'fingerprints' => 'Huellas'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('operation') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-2"><a href="{{ route('operations.queue') }}" class="btn btn-link">Limpiar filtros</a></div>
        </form>
    </div>
</div>
<div class="d-flex gap-2 mb-2">
    <a href="{{ route('firebird.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-database me-1"></i>Ir a Firebird</a>
    <a href="{{ route('operations.notifications') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bell me-1"></i>Notificaciones</a>
    <span class="ms-auto small text-muted align-self-center">Centro de Operaciones — cola unificada (Firebird + Dispositivos)</span>
</div>

<div class="card shadow-sm" data-sync-queue
    data-url="{{ route('operations.queue.data.unified', request()->query()) }}"
    data-cancel-url="{{ url('/sync-queue') }}"
    data-legacy-url="{{ route('operations.queue.data', request()->query()) }}">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-cards">
            <thead><tr><th>Tipo</th><th>Origen</th><th>Operación</th><th>Estado</th><th>Progreso</th><th>Resultado</th><th>Fecha</th><th>Acciones</th></tr></thead>
            <tbody data-sync-rows>
            @forelse ($syncs as $sync)
                @php
                    $percent = $sync->total ? min(100, (int) round(($sync->processed / $sync->total) * 100)) : ($sync->status === 'completed' ? 100 : 0);
                    $statusLabels = ['queued' => 'En cola', 'running' => 'Procesando', 'completed' => 'Completada', 'failed' => 'Fallida'];
                    $statusClass = ['queued' => 'cat-orange', 'running' => 'cat-blue', 'completed' => 'cat-green', 'failed' => 'cat-red'][$sync->status] ?? 'cat-gray';
                @endphp
                <tr data-sync-id="{{ $sync->id }}">
                    <td data-label="Tipo"><span class="badge bg-secondary">Dispositivo</span></td>
                    <td data-label="Origen"><a href="{{ route('devices.show', $sync->device) }}" class="fw-semibold text-decoration-none">{{ $sync->device->name }}</a></td>
                    <td data-label="Operación">
                        {{ $sync->operation_label }}
                        @if ($sync->operation === 'all' && in_array($sync->stage, ['usuarios', 'asistencias', 'huellas'], true))
                            <div class="small text-tertiary-token"><i class="bi bi-arrow-right-short"></i>{{ ucfirst($sync->stage) }}</div>
                        @endif
                    </td>
                    <td data-label="Estado">
                        <span class="badge badge-with-dot {{ $statusClass }}">
                            @if ($sync->status === 'completed')<i class="bi bi-check-circle-fill me-1"></i>@endif
                            {{ $statusLabels[$sync->status] ?? $sync->status }}
                        </span>
                    </td>
                    <td data-label="Progreso" style="min-width:180px"><div class="progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar {{ $sync->status === 'running' ? 'progress-bar-striped progress-bar-animated' : '' }}" style="width:{{ $percent }}%">{{ $percent }}%</div></div><small class="text-muted">{{ $sync->processed }} de {{ $sync->total ?: '—' }}</small></td>
                    <td data-label="Resultado">
                        {{ $sync->created_count }} creados · {{ $sync->updated_count }} actualizados
                        @if ($sync->operation === 'sync_full' && $sync->employee)
                            <div class="small text-tertiary-token">{{ $sync->employee->name }}</div>
                        @endif
                        @if($sync->error_message)<div class="text-danger small">{{ $sync->error_message }}</div>@endif
                    </td>
                    <td data-label="Fecha" class="small text-muted">{{ $sync->created_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Acciones" class="text-end">
                        @if ($sync->status === 'failed' && $sync->operation === 'sync_full')
                            <form action="{{ route('operations.retry', $sync) }}" method="POST" class="d-inline" data-sync-retry>
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" type="submit" title="Reintentar solo este dispositivo" aria-label="Reintentar sincronización"><i class="bi bi-arrow-repeat"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay operaciones en la cola.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $syncs->links() }}</div>
@endsection

@push('scripts')
<script>
    (() => {
        const queue = document.querySelector('[data-sync-queue]');
        const rows = queue?.querySelector('[data-sync-rows]');
        if (!queue || !rows) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const labels = { queued: 'En cola', running: 'Procesando', completed: 'Completada', failed: 'Fallida', cancelled: 'Cancelada' };
        const classes = { queued: 'cat-orange', running: 'cat-blue', completed: 'cat-green', failed: 'cat-red', cancelled: 'cat-gray' };
        const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
        const render = (syncs) => {
            if (!syncs.length) {
                rows.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay operaciones en la cola.</td></tr>';
                return;
            }
            rows.innerHTML = syncs.map((sync) => {
                const percent = sync.total ? Math.min(100, Math.round((sync.processed / sync.total) * 100)) : (sync.status === 'completed' ? 100 : 0);
                const active = ['queued', 'running'].includes(sync.status);
                const typeBadge = sync.type === 'firebird' ? '<span class="badge bg-info-subtle text-info">Firebird</span>' : (sync.type === 'device' ? '<span class="badge bg-secondary-subtle text-secondary">Dispositivo</span>' : '');
                // Usa URLs específicas si vienen del backend unificado, si no fallback a cancelUrl/id
                const cancelUrl = sync.cancel_url || (active ? `${queue.dataset.cancelUrl}/${sync.id}/cancel` : null);
                const retryUrl = sync.retry_url || null;
                const deleteUrl = sync.delete_url || (!active ? `${queue.dataset.cancelUrl}/${sync.id}` : null);
                let action = '';
                if (active && cancelUrl) action = `<button class="btn btn-sm btn-outline-warning" data-cancel-url="${esc(cancelUrl)}" title="Cancelar"><i class="bi bi-stop-circle"></i></button>`;
                else if (retryUrl) action = `<button class="btn btn-sm btn-outline-warning" data-retry-url="${esc(retryUrl)}" title="Reintentar"><i class="bi bi-arrow-repeat"></i></button>`;
                else if (deleteUrl) action = `<button class="btn btn-sm btn-icon-danger" data-delete-url="${esc(deleteUrl)}" title="Eliminar"><i class="bi bi-trash"></i></button>`;
                else if (sync.type === 'firebird') action = `<a href="${esc(sync.device_url)}" class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></a>`;
                const stageNames = { usuarios: 'Usuarios', asistencias: 'Asistencias', huellas: 'Huellas' };
                const subStage = (sync.operation === 'all' && stageNames[sync.stage])
                    ? `<div class="small text-tertiary-token"><i class="bi bi-arrow-right-short"></i>${stageNames[sync.stage]}</div>`
                    : (sync.type === 'firebird' && sync.stage ? `<div class="small text-tertiary-token">${esc(sync.stage)}</div>` : '');
                const originCell = sync.device_url ? `<a href="${esc(sync.device_url)}" class="fw-semibold text-decoration-none">${esc(sync.device)}</a>` : esc(sync.device);
                return `<tr data-sync-id="${sync.id}" data-sync-type="${esc(sync.type || '')}">
                    <td data-label="Tipo">${typeBadge}</td>
                    <td data-label="Origen">${originCell}</td>
                    <td data-label="Operación">${esc(sync.operation_label)}${subStage}</td>
                    <td data-label="Estado"><span class="badge badge-with-dot ${classes[sync.status] || 'cat-gray'}">${sync.status === 'completed' ? '<i class="bi bi-check-circle-fill me-1"></i>' : ''}${labels[sync.status] || esc(sync.status)}</span></td>
                    <td data-label="Progreso" style="min-width:180px"><div class="progress" role="progressbar" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar ${sync.status === 'running' ? 'progress-bar-striped progress-bar-animated' : ''}" style="width:${percent}%">${percent}%</div></div><small class="text-muted">${sync.processed} de ${sync.total || '—'}</small></td>
                    <td data-label="Resultado">${sync.created} creados · ${sync.updated} actualizados${sync.items?.length ? `<div class="small text-tertiary-token">${sync.items.map((item) => `${esc(item.label)}: ${esc(item.status)}`).join(' · ')}</div>` : ''}${sync.error ? `<div class="text-danger small">${esc(sync.error)}</div>` : ''}</td>
                    <td data-label="Fecha" class="small text-muted">${esc(sync.created_at)}</td>
                    <td data-label="Acciones" class="text-end">${action}</td>
                </tr>`;
            }).join('');
        };
        const request = async (url, method = 'GET') => {
            const response = await fetch(url, { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo completar la operación.');
            return data;
        };
        const refresh = async () => {
            try {
                const data = await request(queue.dataset.url);
                render(data.syncs);
                if (data.syncs.some((sync) => ['queued', 'running'].includes(sync.status))) setTimeout(refresh, 3000);
            } catch (error) {
                window.dashToast?.({ type: 'error', title: 'No se pudo actualizar la cola', message: error.message });
                setTimeout(refresh, 8000);
            }
        };
        rows.addEventListener('click', async (event) => {
            const cancelBtn = event.target.closest('[data-cancel-url]');
            const deleteBtn = event.target.closest('[data-delete-url]');
            const retryBtn = event.target.closest('[data-retry-url]');
            // Fallback legacy id-based
            const cancelLegacy = event.target.closest('[data-cancel]');
            const removeLegacy = event.target.closest('[data-delete]');
            const retryLegacy = event.target.closest('[data-retry]');
            const legacyId = cancelLegacy?.dataset.cancel || removeLegacy?.dataset.delete || retryLegacy?.dataset.retry;
            const targetUrl = cancelBtn?.dataset.cancelUrl || deleteBtn?.dataset.deleteUrl || retryBtn?.dataset.retryUrl;
            const isCancel = !!cancelBtn || !!cancelLegacy;
            const isDelete = !!deleteBtn || !!removeLegacy;
            const isRetry = !!retryBtn || !!retryLegacy;
            if (!targetUrl && !legacyId) return;
            if (isCancel && !window.confirm('¿Cancelar esta sincronización?')) return;
            if (isDelete && !window.confirm('¿Eliminar este registro de la cola?')) return;
            if (isRetry && !window.confirm('¿Reintentar?')) return;
            try {
                let url = targetUrl;
                let method = isDelete ? 'DELETE' : 'POST';
                if (!url && legacyId) {
                    url = `${queue.dataset.cancelUrl}/${legacyId}${isCancel ? '/cancel' : (isRetry ? '/retry' : '')}`;
                }
                const data = await request(url, method);
                window.dashToast?.({ type: 'success', title: isCancel ? 'Sincronización cancelada' : (isRetry ? 'Reintento enviado' : 'Registro eliminado'), message: data.message });
                refresh();
            } catch (error) {
                window.dashToast?.({ type: 'error', title: 'No se pudo completar la acción', message: error.message });
            }
        });
        refresh();
    })();
</script>
@endpush