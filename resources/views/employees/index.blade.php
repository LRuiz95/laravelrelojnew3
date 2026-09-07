@extends('layouts.admin')

@section('title', 'Empleados')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form id="employeeFilters" class="filter-row" method="GET">
        <div class="input-group" style="width:auto">
            <span class="input-group-text bg-transparent"><i class="bi bi-search text-tertiary-token"></i></span>
            <input type="search" name="q" id="employeeSearch" value="{{ request('q') }}" class="form-control" placeholder="Buscar empleado por nombre o ID, ej. 320...">
        </div>
        <select name="device_id" id="employeeDevice" class="form-select" style="width:auto">
            <option value="">Todos los dispositivos</option>
            @foreach ($devices as $device)
                <option value="{{ $device->id }}" @selected(request('device_id') == $device->id)>{{ $device->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-ghost"><i class="bi bi-search"></i> Buscar</button>
        @if (request()->has('q') || request()->has('device_id'))
            <a href="{{ route('employees.index') }}" class="btn btn-sm align-self-end mb-1">Limpiar</a>
        @endif
    </form>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Agregar empleado
        </a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Dispositivo</th>
                        <th>Rol</th>
                        <th>Tarjeta</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            // Datos de hardware del enrolamiento principal;
                            // la persona puede estar en varios checadores.
                            $enrollment = $employee->devices->first();
                            $role = $enrollment?->pivot->role;
                            $roleClass = $role === 14 ? 'cat-purple' : ($role === 13 ? 'cat-blue' : 'text-secondary-token');
                        @endphp
                        <tr>
                            <td data-label="ID"><code>{{ $employee->user_id }}</code></td>
                            <td data-label="Nombre">
                                <span class="avatar is-sm me-2">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                                <span class="fw-semibold">{{ $employee->name }}</span>
                            </td>
                            <td data-label="Dispositivo">
                                @forelse ($employee->devices as $device)
                                    <a href="{{ route('devices.show', $device) }}" class="ref-chip me-1 mb-1" title="UID {{ $device->pivot->device_uid }}">
                                        <i class="bi bi-hdd-network"></i>{{ $device->name }}
                                    </a>
                                @empty
                                    <span class="text-secondary-token">—</span>
                                @endforelse
                            </td>
                            <td data-label="Rol">
                                <span class="{{ $roleClass }}" style="font-weight:600;font-size:12px">{{ $enrollment ? $enrollment->pivot->roleLabel() : '—' }}</span>
                            </td>
                            <td data-label="Tarjeta"><span class="mono text-secondary-token">{{ $enrollment?->pivot->card_number ?? '—' }}</span></td>
                            <td data-label="Estado">
                                @if (! $enrollment)
                                    <span class="badge badge-with-dot cat-gray">Sin enrolar</span>
                                @elseif ($enrollment->pivot->active)
                                    <span class="badge badge-with-dot cat-green">Activo</span>
                                @else
                                    <span class="badge badge-with-dot cat-gray">Inactivo</span>
                                @endif
                            </td>
                            <td data-label="">
                                <div class="table-row-actions justify-content-end">
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('employees.upload-fingerprints', $employee) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-ghost" title="Subir huella al checador" aria-label="Subir huella">
                                                <i class="bi bi-cloud-arrow-up"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                                              data-confirm
                                              data-confirm-danger
                                              data-confirm-title="¿Quitar a {{ $employee->name }}?"
                                              data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar"><i class="bi bi-person-x"></i></button>
                                        </form>
                                    @else
                                        @foreach ($employee->devices as $device)
                                            <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-ghost" title="Ver {{ $device->name }}" aria-label="Ver dispositivo {{ $device->name }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('partials.empty-state', [
                                    'icon'     => 'bi-people',
                                    'title'    => request('q') ? 'Sin resultados para tu búsqueda' : 'No hay empleados',
                                    'desc'     => request('q')
                                        ? 'Prueba con otro nombre, ID o selecciona otro dispositivo.'
                                        : 'Vacía los checadores con «Traer usuarios» o agrega empleados manualmente.',
                                    'cta'      => auth()->user()->isAdmin()
                                        ? ['label' => 'Agregar empleado', 'url' => route('employees.create')]
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

<div class="mt-3">{{ $employees->links() }}</div>

<script>
// Debounce function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Loading state management
function setLoading(isLoading) {
    const tbody = document.querySelector('table tbody');
    const searchInput = document.getElementById('employeeSearch');
    const deviceSelect = document.getElementById('employeeDevice');
    
    if (isLoading) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">Cargando...</span></div>Buscando empleados...</td></tr>';
        searchInput.disabled = true;
        deviceSelect.disabled = true;
    } else {
        searchInput.disabled = false;
        deviceSelect.disabled = false;
    }
}

// Function to render employee rows from JSON data
function renderEmployees(data) {
    const tbody = document.querySelector('table tbody');
    const pagination = document.querySelector('.pagination');
    
    if (!data.employees.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="bi bi-people fs-1 text-secondary"></i><p class="mt-2 mb-0">No hay empleados</p></td></tr>';
        if (pagination) pagination.innerHTML = '';
        return;
    }
    
    const rows = data.employees.map(emp => {
        const devicesHtml = emp.devices.map(d => 
            `<a href="${window.location.origin}/devices/${d.id}" class="ref-chip me-1 mb-1" title="UID ${d.pivot.device_uid}"><i class="bi bi-hdd-network"></i>${d.name}</a>`
        ).join('') || '<span class="text-secondary-token">—</span>';
        
        const firstDevice = emp.devices[0];
        const role = firstDevice?.pivot?.role;
        const roleClass = role === 14 ? 'cat-purple' : (role === 13 ? 'cat-blue' : 'text-secondary-token');
        const roleLabel = firstDevice?.pivot?.role ? (role === 14 ? 'Admin' : (role === 13 ? 'Supervisor' : 'Usuario')) : '—';
        const cardNumber = firstDevice?.pivot?.card_number ?? '—';
        
        let statusHtml = '<span class="badge badge-with-dot cat-gray">Sin enrolar</span>';
        if (firstDevice) {
            statusHtml = firstDevice.pivot.active 
                ? '<span class="badge badge-with-dot cat-green">Activo</span>'
                : '<span class="badge badge-with-dot cat-gray">Inactivo</span>';
        }
        
        const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
        
        let actionsHtml = '';
        if (isAdmin) {
            actionsHtml = `
                <div class="table-row-actions justify-content-end">
                    <a href="${emp.edit_url}" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                    <form action="${emp.upload_url}" method="POST" class="d-inline">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content ?? ''}">
                        <button class="btn btn-sm btn-ghost" title="Subir huella al checador" aria-label="Subir huella"><i class="bi bi-cloud-arrow-up"></i></button>
                    </form>
                    <form action="${emp.destroy_url}" method="POST" class="d-inline" data-confirm data-confirm-danger data-confirm-title="¿Quitar a ${emp.name}?" data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content ?? ''}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-icon-danger" title="Eliminar" aria-label="Eliminar"><i class="bi bi-person-x"></i></button>
                    </form>
                </div>`;
        } else {
            actionsHtml = emp.devices.map(d => 
                `<a href="/devices/${d.id}" class="btn btn-sm btn-ghost" title="Ver ${d.name}" aria-label="Ver dispositivo ${d.name}"><i class="bi bi-eye"></i></a>`
            ).join('');
        }
        
        return `
            <tr>
                <td data-label="ID"><code>${emp.user_id}</code></td>
                <td data-label="Nombre">
                    <span class="avatar is-sm me-2">${emp.name.charAt(0).toUpperCase()}</span>
                    <span class="fw-semibold">${emp.name}</span>
                </td>
                <td data-label="Dispositivo">${devicesHtml}</td>
                <td data-label="Rol"><span class="${roleClass}" style="font-weight:600;font-size:12px">${roleLabel}</span></td>
                <td data-label="Tarjeta"><span class="mono text-secondary-token">${cardNumber}</span></td>
                <td data-label="Estado">${statusHtml}</td>
                <td data-label="" class="text-end">${actionsHtml}</td>
            </tr>
        `;
    }).join('');
    
    tbody.innerHTML = rows;
    
    // Update pagination
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
        
        // Attach pagination click handlers
        pagination.querySelectorAll('a.page-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.dataset.page;
                const search = document.getElementById('employeeSearch').value;
                const deviceId = document.getElementById('employeeDevice').value;
                fetchEmployees(page, search, deviceId);
            });
        });
    }
}

// Function to fetch filtered employees via JSON API
async function fetchEmployees(page = 1, search = null, deviceId = null) {
    if (search === null) search = document.getElementById('employeeSearch').value;
    if (deviceId === null) deviceId = document.getElementById('employeeDevice').value;
    
    setLoading(true);
    
    try {
        const url = new URL('{{ route('employees.search') }}');
        url.searchParams.set('q', search);
        url.searchParams.set('device_id', deviceId);
        url.searchParams.set('page', page);
        
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
        // Show error state
        const tbody = document.querySelector('table tbody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle"></i> Error al cargar empleados. <button class="btn btn-sm btn-link" onclick="fetchEmployees()">Reintentar</button></td></tr>';
    }
}

// Attach debounced search input
const searchDebounced = debounce(() => fetchEmployees(1), 300);
document.getElementById('employeeSearch').addEventListener('input', searchDebounced);

// Attach device filter
document.getElementById('employeeDevice').addEventListener('change', () => fetchEmployees(1));

// Handle pagination clicks (delegated)
document.addEventListener('click', (e) => {
    if (e.target.matches('.pagination a.page-link[data-page]')) {
        e.preventDefault();
        const page = e.target.dataset.page;
        fetchEmployees(page);
    }
});
</script>

@endsection