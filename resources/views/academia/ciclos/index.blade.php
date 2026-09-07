@extends('layouts.admin')

@section('title', 'Ciclos Escolares')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Ciclos Escolares</h1>
    <a href="{{ route('academia.ciclos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Ciclo
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ciclo</th>
                        <th>Descripción</th>
                        <th>Fechas</th>
                        <th>Estadísticas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ciclos as $ciclo)
                        <tr>
                            <td class="fw-semibold">{{ $ciclo->label }}</td>
                            <td>{{ $ciclo->descripcion }}</td>
                            <td class="small text-muted">
                                {{ $ciclo->fechaInicialFormateada }} - {{ $ciclo->fechaFinalFormateada }}
                            </td>
                            <td>
                                <div class="d-flex gap-2 small">
                                    <span class="badge bg-primary-subtle text-primary">{{ $ciclo->grupos_count }} grupos</span>
                                    <span class="badge bg-success-subtle text-success">{{ $ciclo->alumnos_count ?? 0 }} alumnos</span>
                                    <span class="badge bg-warning-subtle text-warning">{{ $ciclo->horarios_count }} horarios</span>
                                    <span class="badge bg-info-subtle text-info">{{ $ciclo->cursos_count }} cursos</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge--status {{ $ciclo->activo ? 'badge--active' : 'badge--inactive' }}">
                                    {{ $ciclo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academia.ciclos.show', $ciclo) }}" class="btn btn-outline-primary" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('academia.ciclos.edit', $ciclo) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-{{ $ciclo->activo ? 'warning' : 'success' }}" 
                                            onclick="toggleCicloActivo('{{ $ciclo->id }}', {{ $ciclo->activo ? 'false' : 'true' }})" 
                                            title="{{ $ciclo->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="bi bi-{{ $ciclo->activo ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <form action="{{ route('academia.ciclos.destroy', $ciclo) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este ciclo?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Paginación --}}
{{ $ciclos->links() }}

{{-- Modal crear ciclo --}}
<div class="modal fade" id="modalCrearCiclo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('academia.ciclos.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ciclo Escolar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Año Inicial *</label>
                            <input type="number" name="inicial" class="form-control" required min="2000" max="2100" value="{{ now()->year }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Año Final *</label>
                            <input type="number" name="final" class="form-control" required min="2000" max="2100" value="{{ now()->year }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Periodo *</label>
                            <select name="periodo" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="1">Semestral</option>
                                <option value="2">Cuatrimestral</option>
                                <option value="3" selected>Anual</option>
                                <option value="4">Otro</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" placeholder="Ej: Ciclo 2025-2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicial</label>
                            <input type="date" name="fecha_inicial" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Final</label>
                            <input type="date" name="fecha_final" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Ciclo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCicloActivo(id, activo) {
    fetch(`/academia/ciclos/${id}/activo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ activo })
    }).then(response => {
        if (response.ok) location.reload();
    });
}
</script>
@endsection