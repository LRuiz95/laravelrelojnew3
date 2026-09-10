@extends('layouts.admin')

@section('title', 'Sobrantes en Dispositivos')

@section('content')
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
        @if (auth()->user()->isAdmin())
            <form method="GET" action="{{ route('employees.sobrantes') }}">
                <input type="hidden" name="ignored" value="{{ $includeIgnored ? '0' : '1' }}">
                @if ($deviceId)
                    <input type="hidden" name="device_id" value="{{ $deviceId }}">
                @endif
                @if ($type)
                    <input type="hidden" name="type" value="{{ $type }}">
                @endif
                @if ($search)
                    <input type="hidden" name="q" value="{{ $search }}">
                @endif
                <button class="btn btn-sm btn-ghost" type="submit">
                    <i class="bi bi-eye{{ $includeIgnored ? '-slash' : '' }}"></i>
                    {{ $includeIgnored ? 'Ocultar ignorados' : 'Mostrar ignorados' }}
                </button>
            </form>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-ghost">
            <i class="bi bi-arrow-left"></i> Volver a Empleados
        </a>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('employees.sobrantes') }}" class="mb-4">
    <input type="hidden" name="ignored" value="{{ $includeIgnored ? '1' : '' }}">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" style="font-size:0.8rem">Dispositivo</label>
            <select name="device_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}" @selected($deviceId == $device->id)>{{ $device->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:0.8rem">Tipo</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="A" @selected($type === 'A')>A — Sin Catálogo</option>
                <option value="B" @selected($type === 'B')>B — Baja Firebird</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-size:0.8rem">Buscar</label>
            <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nombre o ID de empleado...">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-sm btn-primary" type="submit">
                <i class="bi bi-search"></i> Filtrar
            </button>
            @if ($deviceId || $type || $search)
                <a href="{{ route('employees.sobrantes', $includeIgnored ? ['ignored' => '1'] : []) }}" class="btn btn-sm btn-ghost">Limpiar</a>
            @endif
        </div>
    </div>
</form>

{{-- Stats cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-warning">{{ $stats['total'] }}</div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Total Sobrantes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-danger">{{ $stats['type_a'] }}</div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Tipo A (Sin Catálogo)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-info">{{ $stats['type_b'] }}</div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Tipo B (Baja Firebird)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-secondary">{{ $stats['ignored'] }}</div>
                <div class="text-secondary-token" style="font-size: 0.875rem;">Ignorados</div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
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
                    @forelse ($sobrantes as $row)
                        <tr>
                            <td data-label="Tipo">
                                @if ($row->sobrante_type === 'A')
                                    <span class="badge badge-with-dot cat-red">Tipo A</span>
                                @else
                                    <span class="badge badge-with-dot cat-orange">Tipo B</span>
                                @endif
                            </td>
                            <td data-label="Dispositivo">
                                <a href="{{ route('devices.show', $row->device_id) }}" class="ref-chip">
                                    <i class="bi bi-hdd-network"></i>{{ $row->device_name }}
                                </a>
                            </td>
                            <td data-label="UID"><code>{{ $row->device_uid }}</code></td>
                            <td data-label="Empleado">
                                @if ($row->employee_id)
                                    <span class="avatar is-sm me-2">{{ strtoupper(substr($row->name ?? '?', 0, 1)) }}</span>
                                    <span class="fw-semibold">{{ $row->name ?? '—' }}</span>
                                @else
                                    <span class="text-secondary-token"><i class="bi bi-person-x me-1"></i>Sin catálogo</span>
                                @endif
                            </td>
                            <td data-label="ID Firebird">
                                @if ($row->user_id)
                                    <code>{{ $row->user_id }}</code>
                                @else
                                    <span class="text-secondary-token">—</span>
                                @endif
                            </td>
                            <td data-label="Razón">
                                @if ($row->sobrante_type === 'A')
                                    <span class="text-danger" style="font-size:0.8rem;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $row->sobrante_reason }}
                                    </span>
                                @else
                                    <span class="text-warning" style="font-size:0.8rem;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $row->sobrante_reason }}
                                    </span>
                                @endif
                            </td>
                            <td data-label="Estado">
                                @if ($row->ignored_at)
                                    <span class="badge badge-with-dot cat-gray">Ignorado</span>
                                @elseif ($row->active)
                                    <span class="badge badge-with-dot cat-green">Activo</span>
                                @else
                                    <span class="badge badge-with-dot cat-gray">Inactivo</span>
                                @endif
                            </td>
                            <td data-label="" class="text-end">
                                <div class="table-row-actions justify-content-end">
                                    @if (auth()->user()->isAdmin())
                                        @if ($row->ignored_at)
                                            <form action="{{ route('employees.sobrantes.unignore', [$row->device_id, $row->device_uid]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-ghost" title="Restaurar" aria-label="Restaurar sobrante">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('employees.sobrantes.ignore', [$row->device_id, $row->device_uid]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-ghost" title="Ignorar" aria-label="Ignorar sobrante">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('employees.sobrantes.remove', [$row->device_id, $row->device_uid, $row->sobrante_type]) }}"
                                              method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-title="¿Eliminar sobrante?"
                                              data-confirm-message="Se eliminará del dispositivo{{ $row->sobrante_type === 'B' ? ' y la relación local. Employee se conserva.' : '.' }}">
                                            @csrf
                                            <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar sobrante">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @include('partials.empty-state', [
                                    'icon'  => 'bi-check-circle',
                                    'title' => 'No hay sobrantes',
                                    'desc'  => 'Todos los enrolamientos en dispositivos tienen un empleado válido activo en el catálogo.',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $sobrantes->links() }}</div>
@endsection
