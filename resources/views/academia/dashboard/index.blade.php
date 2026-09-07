@extends('layouts.admin')

@section('title', 'Academia - Dashboard')

@section('content')
<div class="card mb-4" style="border-left:4px solid var(--primary)">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge badge-with-dot cat-green">Activo</span>
                <h2 class="h6 mb-0 text-secondary-token fw-semibold">{{ $ciclo->label }} - {{ $ciclo->descripcion }}</h2>
            </div>
            <div class="fw-bold fs-5">{{ $ciclo->fechaInicialFormateada }} - {{ $ciclo->fechaFinalFormateada }}</div>
        </div>
        <div class="vr d-none d-md-block" style="height:44px;background:var(--border)"></div>
        <div class="d-flex flex-column gap-1 fs-6">
            <div><i class="bi bi-people text-secondary-token me-2"></i>{{ $kpis['grupos'] }} grupos | {{ $kpis['alumnos'] }} alumnos</div>
            <div><i class="bi bi-person-check text-secondary-token me-2"></i>{{ $kpis['profesores'] }} profesores | {{ $kpis['horarios'] }} horarios</div>
            <div class="small text-tertiary-token">
                <i class="bi bi-clock-history me-2"></i>Última actualización: <strong class="text-secondary-token">{{ now()->diffForHumans() }}</strong>
            </div>
        </div>
        <div class="ms-auto">
            <a href="{{ route('academia.ciclos.show', $ciclo) }}" class="btn btn-outline-primary">
                Ver detalle <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid" data-kpis-url="{{ route('dashboard.kpisJson') }}">
    <x-stat-card :icon="'bi-people'" :label="'Grupos'" :value="$kpis['grupos']" :color="'purple'">
        <div class="kpi-trend flat" data-kpi-trend>–</div>
    </x-stat-card>

    <x-stat-card :icon="'bi-people-fill'" :label="'Alumnos inscritos'" :value="$kpis['alumnos']" :color="'blue'">
        <div class="kpi-trend flat" data-kpi-trend>–</div>
    </x-stat-card>

    <x-stat-card :icon="'bi-person-badge'" :label="'Profesores activos'" :value="$kpis['profesores']" :color="'green'">
        <div class="kpi-trend flat" data-kpi-trend>–</div>
    </x-stat-card>

    <x-stat-card :icon="'bi-calendar-week'" :label="'Horarios programados'" :value="$kpis['horarios']" :color="'orange'">
        <div class="kpi-trend flat" data-kpi-trend>–</div>
    </x-stat-card>

    <x-stat-card :icon="'bi-book'" :label="'Cursos disponibles'" :value="$kpis['cursos']" :color="'teal'">
        <div class="kpi-trend flat" data-kpi-trend>–</div>
    </x-stat-card>
</div>

{{-- Gráfica horarios por día --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-bold">Horarios por día · Ciclo {{ $ciclo->label }}</span>
        </div>
    </div>
    <div class="card-body">
        @php
            $dias = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
            $data = [];
            foreach ($dias as $d => $label) {
                $data[] = ['label' => $label, 'value' => $horariosPorDia[$d] ?? 0];
            }
            $points = array_column($data, 'value');
            $max = max(1, ...$points);
            $w = 700; $h = 120; $padX = 10; $padT = 12;
            $n = count($points);
            $stepX = $n > 1 ? ($w - $padX * 2) / ($n - 1) : 0;
            $coords = [];
            foreach ($points as $i => $v) {
                $x = $n > 1 ? round($padX + $i * $stepX, 1) : $w / 2;
                $y = round($padT + ($h - $padT) * (1 - ($v / $max)), 1);
                $coords[] = [$x, $y];
            }
            $linePath = 'M ' . implode(' L ', array_map(fn ($c) => $c[0] . ',' . $c[1], $coords));
            $first = $coords[0];
            $last = $coords[$n - 1];
            $areaPath = $n > 1
                ? "M {$first[0]},{$first[1]} " . implode(' ', array_map(fn ($c) => "L {$c[0]},{$c[1]}", array_slice($coords, 1))) . " L {$last[0]},$h L {$first[0]},$h Z"
                : '';
        @endphp
        <svg class="trend-chart" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="Horarios por día de la semana">
            <defs>
                <linearGradient id="trendGradientAcademia" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--primary)" stop-opacity=".28"/>
                    <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                </linearGradient>
            </defs>
            @if ($areaPath) <path class="area-fill" d="{{ $areaPath }}" fill="url(#trendGradientAcademia)"/> @endif
            <path class="area-line" d="{{ $linePath }}" stroke="var(--primary)" stroke-width="2" fill="none"/>
            @foreach ($coords as $i => [$x, $y])
                <circle class="area-dot" cx="{{ $x }}" cy="{{ $y }}" r="4" fill="var(--primary)">
                    <title>{{ $data[$i]['label'] }} · {{ $data[$i]['value'] }} horarios</title>
                </circle>
            @endforeach
            @if ($n > 0)
                <text x="{{ $first[0] }}" y="{{ $h - 4 }}" font-size="11" fill="var(--text-tertiary)">{{ $data[0]['label'] }}</text>
                <text x="{{ $last[0] }}" y="{{ $h - 4 }}" font-size="11" fill="var(--text-tertiary)" text-anchor="end">{{ $data[$n - 1]['label'] }}</text>
            @endif
        </svg>
    </div>
</div>

{{-- Distribución por origen --}}
<div class="card mb-4">
    <div class="card-header">
        <span class="fw-bold">Distribución por tipo de horario</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach ($porOrigen as $origen => $total)
                <div class="col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="h3 mb-1">{{ $total }}</div>
                            <div class="text-muted small">
                                @switch($origen)
                                    @case('HD')
                                        Hora Docente (PTC)
                                        @break
                                    @case('CA')
                                        Carga Asignada (PA)
                                        @break
                                    @default
                                        {{ $origen }}
                                @endswitch
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Accesos rápidos --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="{{ route('academia.grupos.index') }}" class="card card-link h-100 text-center p-4">
            <i class="bi bi-people fs-1 text-primary mb-2"></i>
            <h5>Grupos</h5>
            <p class="text-muted small">Ver y gestionar grupos</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('academia.alumnos.index') }}" class="card card-link h-100 text-center p-4">
            <i class="bi bi-mortarboard fs-1 text-success mb-2"></i>
            <h5>Alumnos</h5>
            <p class="text-muted small">Buscar y ver kardex</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('academia.profesores.index') }}" class="card card-link h-100 text-center p-4">
            <i class="bi bi-person-badge fs-1 text-warning mb-2"></i>
            <h5>Profesores</h5>
            <p class="text-muted small">Horarios y contratos</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('academia.horarios.clase') }}" class="card card-link h-100 text-center p-4">
            <i class="bi bi-calendar-check fs-1 text-info mb-2"></i>
            <h5>Asistencia clases</h5>
            <p class="text-muted small">Captura por sesión</p>
        </a>
    </div>
</div>

{{-- Ciclos recientes --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Ciclos disponibles</span>
        <a href="{{ route('academia.ciclos.index') }}" class="btn btn-sm btn-ghost">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ciclo</th>
                        <th>Descripción</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ciclos as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->label }}</td>
                            <td>{{ $c->descripcion }}</td>
                            <td class="small text-muted">{{ $c->fechaInicialFormateada }} - {{ $c->fechaFinalFormateada }}</td>
                            <td>
                                <span class="badge badge--status {{ $c->activo ? 'badge--active' : 'badge--inactive' }}">
                                    {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('academia.ciclos.show', $c) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection