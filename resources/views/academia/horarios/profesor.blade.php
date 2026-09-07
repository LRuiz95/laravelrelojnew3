@extends('layouts.admin')

@section('title', 'Horarios por Profesor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Horarios por Profesor</h1>
    <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar me-1"></i> Cambiar ciclo
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Buscar profesor</label>
                <select class="form-select" id="profesorSelect">
                    <option value="">-- Seleccionar profesor --</option>
                    @foreach (\App\Models\Academia\Profesor::activo()
                        ->whereHas('horarios', fn ($q) => $q->where('inicial', $ciclo->inicial)
                            ->where('final', $ciclo->final)
                            ->where('periodo', $ciclo->periodo))
                        ->orderBy('paterno')->orderBy('materno')->orderBy('nombre_profesor')
                        ->get() as $p)
                        <option value="{{ $p->clave_profesor }}">{{ $p->nombre_completo }} ({{ $p->clave_profesor }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="horarioContainer" class="d-none">
            <h4 class="mb-3">Horario de <span id="profesorNombre" class="fw-semibold"></span> ({{ $ciclo->label }})</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Sesión</th>
                            @foreach ([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $d => $label)
                                <th class="text-center">
                                    <span class="badge {{ in_array($d, [6,7]) ? 'bg-purple' : 'bg-primary' }} me-1">{{ $label }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="horarioBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const select = document.getElementById('profesorSelect');
const container = document.getElementById('horarioContainer');
const body = document.getElementById('horarioBody');
const nombreEl = document.getElementById('profesorNombre');

select.addEventListener('change', function() {
    const clave = this.value;
    if (!clave) {
        container.classList.add('d-none');
        return;
    }
    nombreEl.textContent = this.options[this.selectedIndex].text;
    container.classList.remove('d-none');
    
    fetch('/api/academia/grupo-detalle?profesor=' + clave + '&ciclo={{ $ciclo->label }}')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderHorario(data.data.horarios);
        });
});

function renderHorario(horarios) {
    const grid = {};
    horarios.forEach(h => {
        const key = h.dia + '-' + h.sesion;
        if (!grid[key]) grid[key] = [];
        grid[key].push(h);
    });
    
    let html = '';
    for (let sesion = 1; sesion <= 12; sesion++) {
        let row = '<tr><td class="text-nowrap small text-muted"><div class="fw-semibold">Ses. ' + sesion + '</div></td>';
        for (let d = 1; d <= 7; d++) {
            const key = d + '-' + sesion;
            if (grid[key]) {
                row += '<td class="align-middle">';
                grid[key].forEach(c => {
                    row += "<div class='mb-2 p-2 rounded bg-light border'>";
                    row += "<div class='fw-semibold small'>" + (c.materia?.label || '') + "</div>";
                    row += "<div class='small text-muted'>" + c.grupo + ' · ' + c.aula + "</div>";
                    row += "<span class='badge " + (c.tipo === 'PTC' ? 'bg-purple' : 'bg-info') + "'>" + c.tipo + "</span>";
                    row += "</div>";
                });
                row += '</td>';
            } else {
                row += '<td class="align-middle"><span class="text-muted">—</span></td>';
            }
        }
        row += '</tr>';
        body.innerHTML += row;
    }
}
</script>
@endsection