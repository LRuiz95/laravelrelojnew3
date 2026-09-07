@extends('layouts.admin')

@section('title', 'Horario por Persona')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Horario por Persona</h1>
    <a href="{{ route('academia.ciclos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar me-1"></i> Cambiar ciclo
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Tipo</label>
                <select class="form-select" id="tipoSelect">
                    <option value="profesor">Profesor</option>
                    <option value="alumno">Alumno</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="buscarInput" placeholder="Clave o nombre...">
                    <button class="btn btn-primary" type="button" onclick="buscarPersona()">Buscar</button>
                </div>
            </div>
        </div>

        <div id="resultadoContainer" class="d-none">
            <h4 class="mb-3">Horario de <span id="personaNombre" class="fw-semibold"></span> ({{ $ciclo->label }})</h4>
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
                    <tbody id="personaHorarioBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('buscarInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') buscarPersona();
});

function buscarPersona() {
    const tipo = document.getElementById('tipoSelect').value;
    const buscar = document.getElementById('buscarInput').value.trim();
    const container = document.getElementById('resultadoContainer');
    const nombreEl = document.getElementById('personaNombre');
    const body = document.getElementById('personaHorarioBody');
    
    if (!buscar) return;
    
    const url = tipo === 'profesor' 
        ? '/api/academia/grupo-detalle?profesor=' + encodeURIComponent(buscar) + '&ciclo={{ $ciclo->label }}'
        : '/api/academia/grupo-detalle?alumno=' + encodeURIComponent(buscar) + '&ciclo={{ $ciclo->label }}';
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) {
                container.classList.add('d-none');
                return;
            }
            nombreEl.textContent = data.data.nombre_completo || buscar;
            container.classList.remove('d-none');
            renderHorarioPersona(data.data.horarios || data.data);
        });
}

function renderHorarioPersona(horarios) {
    const grid = {};
    (horarios || []).forEach(h => {
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
                    row += "<div class='fw-semibold small'>" + (c.materia || c.materia_nombre || '') + "</div>";
                    row += "<div class='small text-muted'>" + (c.grupo || c.grupoLabel || '') + ' · ' + (c.aula || '') + "</div>";
                    row += "<span class='badge " + (c.tipo === 'PTC' ? 'bg-purple' : 'bg-info') + "'>" + (c.tipo || '') + "</span>";
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