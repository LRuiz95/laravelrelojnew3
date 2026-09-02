@extends('layouts.admin')

@section('title', 'Dispositivos')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="h5 mb-1">Red biométrica</h2>
        <p class="text-muted mb-0">Supervisa la red biométrica y sus registros desde un solo lugar.</p>
    </div>
    @if (auth()->user()->isAdmin())
        <div class="d-flex gap-2">
            <form action="{{ route('devices.deduplicate') }}" method="POST"
                  data-confirm
                  data-confirm-danger
                  data-confirm-type="ELIMINAR"
                  data-confirm-title="¿Eliminar registros duplicados?"
                  data-confirm-message="Se conservará el registro más antiguo de cada empleado y asistencia repetida. Las huellas y relaciones se conservarán cuando sea posible. Escribe ELIMINAR para confirmar.">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-funnel me-1"></i> Limpiar duplicados
                </button>
            </form>
            <a href="{{ route('devices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Registrar dispositivo
            </a>
        </div>
    @endif
</div>

<div class="kpi-grid">
    <x-stat-card icon="bi-hdd-network" :value="$stats['devices']" label="Checadores registrados" color="teal">
        <div class="kpi-spark">
            @include('partials.sparkline', ['points' => $spark['devices'], 'color' => 'var(--primary)', 'width' => 84, 'height' => 28])
        </div>
    </x-stat-card>

    <x-stat-card icon="bi-wifi" :value="$stats['online']" label="En línea ahora" color="green">
        <div class="kpi-trend flat"><span class="text-tertiary-token" style="font-weight:500">{{ (int) round(($stats['online'] / max(1, $stats['devices'])) * 100) }}% de la red</span></div>
    </x-stat-card>

    <x-stat-card icon="bi-people" :value="$stats['employees']" label="Empleados sincronizados" color="purple">
        <div class="kpi-spark">
            @include('partials.sparkline', ['points' => $spark['employees'], 'color' => 'var(--cat-purple)', 'width' => 84, 'height' => 28])
        </div>
    </x-stat-card>

    <x-stat-card icon="bi-calendar-check" :value="$stats['attendances']" label="Checadas almacenadas" color="blue">
        <div class="kpi-spark">
            @include('partials.sparkline', ['points' => $spark['attendances'], 'color' => 'var(--cat-blue)', 'width' => 84, 'height' => 28])
        </div>
    </x-stat-card>
</div>

<div class="section-heading">
    <h2>Dispositivos de la red</h2>
    <span class="text-muted small">{{ $stats['fingerprints'] }} huellas protegidas</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Dispositivo</th>
                        <th>Dirección IP</th>
                        <th>Puerto</th>
                        <th>Estado</th>
                        <th class="num-cell">Empleados</th>
                        <th class="num-cell">Registros</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td data-label="Dispositivo">
                                <a href="{{ route('devices.show', $device) }}" class="fw-semibold text-decoration-none">{{ $device->name }}</a>
                                @if ($device->device_name)
                                    <div class="small text-muted">{{ $device->device_name }}</div>
                                @endif
                            </td>
                            <td data-label="IP"><code>{{ $device->ip }}</code></td>
                            <td data-label="Puerto"><span class="mono text-secondary-token">{{ $device->port }}</span></td>
                            <td data-label="Estado">
                                @php $cat = $device->status === 'online' ? 'cat-green' : ($device->status === 'offline' ? 'cat-red' : 'cat-gray'); @endphp
                                <span class="badge badge-with-dot {{ $cat }}">
                                    {{ \App\Models\Device::states()[$device->status] ?? $device->status }}
                                </span>
                            </td>
                            <td data-label="Empleados" class="num-cell mono">{{ $device->employees_count }}</td>
                            <td data-label="Registros" class="num-cell mono">{{ $device->attendances_count }}</td>
                            <td data-label="">
                                <div class="table-row-actions justify-content-end">
                                    <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-ghost" title="Ver detalle" aria-label="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if (auth()->user()->isAdmin())
                                        <form action="{{ route('devices.sync-attendances', $device) }}" method="POST" class="d-inline" data-sync>
                                            @csrf
                                            <button class="btn btn-sm btn-ghost" title="Sincronizar asistencias" aria-label="Sincronizar asistencias"><i class="bi bi-calendar-plus"></i></button>
                                        </form>
                                        <a href="{{ route('devices.edit', $device) }}" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('devices.destroy', $device) }}" method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-type="ELIMINAR"
                                              data-confirm-title="¿Eliminar dispositivo de la red?"
                                              data-confirm-message="Se eliminará «{{ $device->name }}» junto con sus empleados, huellas y registros asociados. Esta acción no se puede deshacer. Escribe ELIMINAR para confirmar.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('partials.empty-state', [
                                    'icon'     => 'bi-hdd-network',
                                    'title'    => 'No hay checadores registrados aún',
                                    'desc'     => 'Los dispositivos aparecerán aquí cuando se registren en la red.',
                                    'cta'      => auth()->user()->isAdmin()
                                        ? ['label' => 'Registrar primer dispositivo', 'url' => route('devices.create')]
                                        : null,
                                    'ctaLink'  => true,
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $devices->links() }}</div>
@endsection