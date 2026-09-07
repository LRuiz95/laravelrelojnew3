@extends('layouts.admin')

@section('title', 'Horario Base')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Horario Base por Nivel/Turno</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label">Nivel</label>
        <select class="form-select" id="nivelSelect" onchange="cargarHorarioBase()">
            <option value="">-- Seleccionar --</option>
            @foreach (\App\Models\Academia\Nivel::activo()->get() as $n)
                <option value="{{ $n->nivel }}">{{ $n->descripcion }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Turno</label>
        <select class="form-select" id="turnoSelect" onchange="cargarHorarioBase()">
            <option value="">-- Seleccionar --</option>
            @foreach (\App\Models\Academia\Turno::activo()->get() as $t)
                <option value="{{ $t->turno }}">{{ $t->descripcion }}</option>
            @endforeach
        </select>
    </div>
</div>

<div id="horarioBaseContainer" class="d-none">
    <h4 class="mb-3">Horario Base: <span id="nivelTurnoLabel" class="fw-semibold"></span></h4>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Sesión</th>
                    @foreach ([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $d => $label)
                        <th class="text-center">
                            <span class="badge {{ in_array($d, [6,7]) ? 'bg-purple' : 'bg-primary' }} me-1">{{ $label }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="horarioBaseBody"></tbody>
        </table>
    </div>
</div>

<script>
function cargarHorarioBase() {
    const nivel = document.getElementById('nivelSelect').value;
    const turno = document.getElementById('turnoSelect').value;
    const container = document.getElementById('horarioBaseContainer');
    const body = document.getElementById('horarioBaseBody');
    const label = document.getElementById('nivelTurnoLabel');
    
    if (!nivel || !turno) {
        container.classList.add('d-none');
        return;
    }
    
    const nivelNombre = document.getElementById('nivelSelect').options[document.getElementById('nivelSelect').selectedIndex].text;
    const turnoNombre = document.getElementById('turnoSelect').options[document.getElementById('turnoSelect').selectedIndex].text;
    label.textContent = nivelNombre + ' - ' + turnoNombre;
    container.classList.remove('d-none');
    
    fetch('/api/academia/horario-base?nivel=' + nivel + '&turno=' + turno)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderHorarioBase(data.data);
        });
}

function renderHorarioBase(horario) {
    const body = document.getElementById('horarioBaseBody');
    body.innerHTML = '';
    
    const sesiones = Object.keys(horario).sort((a,b) => parseInt(a) - parseInt(b));
    
    sesiones.forEach(sesion => {
        const h = horario[sesion];
        let row = '<tr><td class="text-nowrap small text-muted"><div class="fw-semibold">Ses. ' + sesion + '</div></td>';
        
        for (let d = 1; d <= 7; d++) {
            row += '<td class="align-middle text-center">';
            if (h.receso) {
                row += '<span class="badge bg-warning text-dark">RECESO</span>';
            } else {
                row += '<div class="small">' + h.inicio + ' - ' + h.fin + '</div>';
            }
            row += '</td>';
        }
        row += '</tr>';
        document.getElementById('horarioBaseBody').innerHTML += row;
    });
}
</script>
@endsection