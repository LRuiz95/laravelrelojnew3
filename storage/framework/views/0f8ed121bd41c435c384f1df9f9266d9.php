<?php $__env->startSection('title', 'Ciclos Escolares'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Ciclos Escolares</h1>
    <a href="<?php echo e(route('academia.ciclos.create')); ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Ciclo
    </a>
</div>

<x-data-table
    :headers="[
        ['label' => 'Ciclo', 'field' => 'label', 'class' => 'fw-semibold'],
        ['label' => 'Descripción', 'field' => 'descripcion'],
        ['label' => 'Fechas', 'render' => fn($ciclo) => '<span class=\"small text-muted\">' . e($ciclo->fechaInicialFormateada) . ' - ' . e($ciclo->fechaFinalFormateada) . '</span>'],
        ['label' => 'Estadísticas', 'render' => function ($ciclo) { $html = '<div class=\"d-flex gap-2 small\">'; $html .= '<span class=\"badge bg-primary-subtle text-primary\">' . $ciclo->grupos_count . ' grupos</span>'; $html .= '<span class=\"badge bg-success-subtle text-success\">' . ($ciclo->alumnos_count ?? 0) . ' alumnos</span>'; $html .= '<span class=\"badge bg-warning-subtle text-warning\">' . $ciclo->horarios_count . ' horarios</span>'; $html .= '<span class=\"badge bg-info-subtle text-info\">' . $ciclo->cursos_count . ' cursos</span>'; $html .= '</div>'; return $html; }],
        ['label' => 'Estado', 'render' => fn($ciclo) => '<span class=\"badge badge--status ' . ($ciclo->activo ? 'badge--active' : 'badge--inactive') . '\">' . ($ciclo->activo ? 'Activo' : 'Inactivo') . '</span>'],
    ]"
    :rows="$ciclos"
    :actions="[
        ['type' => 'link', 'url' => fn($ciclo) => route('academia.ciclos.show', $ciclo), 'style' => 'primary', 'title' => 'Ver', 'icon' => 'bi bi-eye'],
        ['type' => 'link', 'url' => fn($ciclo) => route('academia.ciclos.edit', $ciclo), 'style' => 'secondary', 'title' => 'Editar', 'icon' => 'bi bi-pencil'],
        ['type' => 'button', 'style' => fn($ciclo) => $ciclo->activo ? 'warning' : 'success', 'onclick' => fn($ciclo) => "toggleCicloActivo('{$ciclo->id}', " . ($ciclo->activo ? 'false' : 'true') . ")", 'title' => fn($ciclo) => $ciclo->activo ? 'Desactivar' : 'Activar', 'icon' => fn($ciclo) => 'bi bi-' . ($ciclo->activo ? 'pause' : 'play')],
    ]"
    :pagination="$ciclos"
    empty-message="No hay ciclos registrados"
/>


<div class="modal fade" id="modalCrearCiclo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo e(route('academia.ciclos.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ciclo Escolar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Año Inicial *</label>
                            <input type="number" name="inicial" class="form-control" required min="2000" max="2100" value="<?php echo e(now()->year); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Año Final *</label>
                            <input type="number" name="final" class="form-control" required min="2000" max="2100" value="<?php echo e(now()->year); ?>">
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/academia/ciclos/index.blade.php ENDPATH**/ ?>