@extends('layouts.admin')

@section('title', $curso->nombre_curso . ' - ' . $ciclo->label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $curso->nombre_curso }}</h1>
        <p class="text-muted mb-0">
            {{ $curso->clave_curso }} | {{ $curso->nivel }} · {{ $curso->turno }}
            <span class="ms-2 badge bg-secondary">{{ $curso->materias_count }} materias</span>
        </p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('academia.cursos.edit', $curso) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid mb-4">
    <x-stat-card :icon="'bi-book'" :label="'Materias'" :value="$curso->materias_count" :color="'blue'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-clock'" :label="'Horas Teoría'" :value="$curso->materias->sum('horas_teoria')" :color="'purple'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-gear'" :label="'Horas Práctica'" :value="$curso->materias->sum('horas_practica')" :color="'orange'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
    <x-stat-card :icon="'bi-mortarboard'" :label="'Créditos Totales'" :value="$curso->materias->sum('creditos')" :color="'teal'">
        <div class="kpi-trend flat">–</div>
    </x-stat-card>
</div>

{{-- Materias --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Materias del Plan</span>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarMateria">
            <i class="bi bi-plus-lg me-1"></i> Agregar Materia
        </button>
    </div>
    <div class="card-body p-0">
        @if ($materias->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-book fs-1 mb-2"></i>
                <p>No hay materias asignadas a este curso</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Materia</th>
                            <th>Semestre</th>
                            <th class="text-center">Teoría</th>
                            <th class="text-center">Práctica</th>
                            <th class="text-center">Créditos</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materias as $m)
                            <tr>
                                <td class="fw-semibold">{{ $m->clave_asignatura }}</td>
                                <td>{{ $m->materia->nombre_asignatura }}</td>
                                <td>{{ $m->semestre ?? '—' }}</td>
                                <td class="text-center">{{ $m->horas_teoria }}</td>
                                <td class="text-center">{{ $m->horas_practica }}</td>
                                <td class="text-center">{{ $m->creditos }}</td>
                                <td>
                                    <span class="badge {{ $m->tipo === 'obligatoria' ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $m->tipo }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--status {{ $m->activo ? 'badge--active' : 'badge--inactive' }}">
                                        {{ $m->activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger" onclick="eliminarMateria({{ $m->id }})" title="Quitar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal Agregar Materia --}}
<div class="modal fade" id="modalAgregarMateria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAgregarMateria">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Materia al Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Materia <span class="text-danger">*</span></label>
                            <select name="clave_asignatura" class="form-select" required id="materiaSelect">
                                <option value="">-- Seleccionar --</option>
                                @foreach (\App\Models\Academia\Materia::activa()->where('id_plan', $curso->id_plan)->get() as $m)
                                    <option value="{{ $m->clave_asignatura }}">{{ $m->nombre_asignatura }} ({{ $m->clave_asignatura }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semestre</label>
                            <input type="number" name="semestre" class="form-control" min="1" max="12">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Teoría</label>
                            <input type="number" name="horas_teoria" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Práctica</label>
                            <input type="number" name="horas_practica" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="obligatoria">Obligatoria</option>
                                <option value="optativa">Optativa</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formAgregarMateria').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/academia/cursos/{{ $curso->id }}/materia', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
});

function eliminarMateria(id) {
    if (confirm('¿Quitar esta materia del curso?')) {
        fetch('/academia/cursos/{{ $curso->id }}/materia/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}
</script>
@endsection