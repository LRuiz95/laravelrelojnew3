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

 // Function to fetch filtered employees
 async function fetchEmployees() {
     const search = document.getElementById('employeeSearch').value;
     const deviceId = document.getElementById('employeeDevice').value;
     
     try {
         const url = new URL('{{ route('employees.index') }}');
         url.searchParams.set('q', search);
         url.searchParams.set('device_id', deviceId);
         
         const response = await fetch(url, {
             headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
             credentials: 'same-origin'
         });
         
         if (!response.ok) throw new Error('Error en la petición');
         
         const html = await response.text();
         const parser = new DOMParser();
         const doc = parser.parseFromString(html, 'text/html');
         
         // Reemplazar la tabla
         const newTable = doc.querySelector('table tbody').innerHTML;
         document.querySelector('table tbody').innerHTML = newTable;
     } catch (error) {
         console.error('Error fetching employees:', error);
         // Re-intentar después de 2 segundos
         setTimeout(fetchEmployees, 2000);
     }
 }

// Attach debounced search input
 const searchDebounced = debounce(fetchEmployees, 300);
 document.getElementById('employeeSearch').addEventListener('input', searchDebounced);

// Attach device filter
 document.getElementById('employeeDevice').addEventListener('change', fetchEmployees);
</script>

@endsection