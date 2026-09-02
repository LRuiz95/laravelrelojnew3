@extends('layouts.admin')

@section('title', 'Panel de control')

@section('content')
{{-- Banner de contexto activo --}}
<div class="card mb-4" style="border-left:4px solid var(--primary)">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge badge-with-dot cat-green">Activo</span>
                <h2 class="h6 mb-0 text-secondary-token fw-semibold">{{ $todayInfo['label'] }}</h2>
            </div>
            <div class="fw-bold fs-5">{{ $todayInfo['date'] }}</div>
        </div>
        <div class="vr d-none d-md-block" style="height:44px;background:var(--border)"></div>
        <div class="d-flex flex-column gap-1 fs-6">
            <div><i class="bi bi-person-check text-secondary-token me-2"></i>{{ $todayInfo['employees'] }} empleado(s) con chequeo</div>
            <div><i class="bi bi-calendar-check text-secondary-token me-2"></i>{{ $todayInfo['checks'] }} chequeos registrados hoy</div>
            @if ($todayInfo['hasChecksToday'])
                <div class="small text-secondary-token">
                    <i class="bi bi-sunrise me-2"></i>Primera: <strong>{{ $todayInfo['firstCheck']->format('H:i') }}</strong>
                    <span class="text-tertiary-token mx-1">·</span>
                    Última: <strong>{{ $todayInfo['lastCheck']->format('H:i') }}</strong>
                </div>
            @endif
            <div class="small text-tertiary-token">
                <i class="bi bi-clock-history me-2"></i>
                @if ($todayInfo['lastCheck'])
                    Última checada de hoy: <strong class="text-secondary-token">{{ $todayInfo['lastCheck']->diffForHumans() }}</strong>
                @elseif ($todayInfo['lastActivity'])
                    Aún sin chequeos hoy — última actividad:
                    <strong class="text-secondary-token">{{ $todayInfo['lastActivity']->locale('es')->isoFormat('D MMM · H:i') }}</strong>
                    ({{ $todayInfo['lastActivity']->diffForHumans() }})
                @else
                    Aún no hay chequeos registrados en el sistema
                @endif
            </div>
        </div>
        <div class="ms-auto">
            <a href="{{ route('attendances.index') }}" class="btn btn-outline-primary">
                Ver detalle del día <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

{{-- KPIs globales (data-kpis-url: fuente de la actualización periódica) --}}
<div class="kpi-grid" data-kpis-url="{{ route('dashboard.kpisJson') }}">
    @foreach ($kpis as $kpi)
        <x-stat-card :icon="$kpi['icon']" :label="$kpi['label']" :value="$kpi['value']" :color="$kpi['color']">
            <div class="kpi-trend {{ $kpi['trend']['dir'] }}" data-kpi-trend>
                @if ($kpi['trend']['dir'] === 'up') ▲ @elseif ($kpi['trend']['dir'] === 'down') ▼ @else – @endif
                {{ $kpi['trend']['dir'] === 'flat' ? '' : $kpi['trend']['value'] . '%' }}
                @isset($kpi['caption'])
                    <span class="text-tertiary-token ms-1" style="font-weight:500">{{ $kpi['caption'] }}</span>
                @endisset
            </div>
            <div class="kpi-spark">
                @include('partials.sparkline', ['points' => $kpi['spark'], 'color' => 'var(--primary)', 'width' => 84, 'height' => 28])
            </div>
        </x-stat-card>
    @endforeach
</div>

        {{-- Fila de tendencia temporal con selector de rango --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                @php
                    $trendTitle = match ($rango) {
                        'hoy' => 'Chequeos por hora · hoy',
                        '12m' => 'Chequeos por mes · últimos 12 meses',
                        '30d' => 'Chequeos diarios · últimos 30 días',
                        default => 'Chequeos diarios · últimos 7 días',
                    };
                @endphp
                <div>
                    <span class="fw-bold">{{ $trendTitle }}</span>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Rango de la gráfica de tendencia">
                    @foreach ($rangos as $key => $texto)
                        <a href="{{ route('dashboard', ['rango' => $key]) }}"
                           class="btn {{ $rango === $key ? 'btn-primary' : 'btn-ghost' }}"
                           @if ($rango === $key) aria-current="page" @endif>{{ $texto }}</a>
                    @endforeach
                </div>
            </div>
            <div class="card-body">
        @php
            $points = array_column($trend, 'value');
            $max = max(1, ...$points);
            $w = 700; $h = 100; $padX = 10; $padT = 12;
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
        <svg class="trend-chart" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="Gráfico de chequeos diarios">
            <defs>
                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--primary)" stop-opacity=".28"/>
                    <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                </linearGradient>
            </defs>
            @if ($areaPath) <path class="area-fill" d="{{ $areaPath }}"/> @endif
            <path class="area-line" d="{{ $linePath }}"/>
            @foreach ($coords as $i => [$x, $y])
                <circle class="area-dot" cx="{{ $x }}" cy="{{ $y }}" r="3.5">
                    <title>{{ $trend[$i]['label'] }} · {{ $points[$i] }} chequeo(s)</title>
                </circle>
            @endforeach
            @if ($n > 0)
                <text x="{{ $first[0] }}" y="{{ $h - 4 }}" font-size="10" fill="var(--text-tertiary)">{{ $trend[0]['label'] }}</text>
                <text x="{{ $last[0] }}" y="{{ $h - 4 }}" font-size="10" fill="var(--text-tertiary)" text-anchor="end">{{ $trend[$n - 1]['label'] }}</text>
            @endif
        </svg>
    </div>
</div>

{{-- Pipeline de estado (6 categorías fijas) --}}
<div class="section-heading">
    <h2>Estado de la operación</h2>
    <span class="text-tertiary-token small">Distribución actual del sistema</span>
</div>
<div class="pipeline">
    @foreach ($pipeline as $i => $step)
        <div class="pipeline-step" style="--ps-color: var(--cat-{{ $step['color'] }})">
            @unless ($loop->last)
                <span class="pipeline-connector"><i class="bi bi-chevron-right"></i></span>
            @endunless
            <div class="ps-top">
                <span class="ps-value">{{ $step['value'] }}</span>
                <i class="bi {{ $step['icon'] }} ps-icon"></i>
            </div>
            <div class="ps-label">{{ $step['label'] }}</div>
            <div class="ps-bar"><i style="width:{{ $step['bar'] }}%"></i></div>
        </div>
    @endforeach
</div>

{{-- Panel dual: donut + registros recientes --}}
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Marcados por tipo <span class="text-tertiary-token small ms-1">· hoy</span></span>
                <a href="{{ route('attendances.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-sm btn-ghost">Ver día <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body">
                @include('partials.donut', ['segments' => $donut['segments'], 'total' => $donut['total'], 'emptyText' => 'Sin chequeos hoy todavía.'])
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">Registros recientes</span>
                <a href="{{ route('attendances.index') }}" class="btn btn-sm btn-ghost">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Empleado</th>
                                <th>ID</th>
                                <th>Marcado</th>
                                <th class="text-end">Dispositivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $attendance)
                                <tr>
                                    <td class="mono text-secondary-token" style="font-size:12px">
                                        {{ $attendance->recorded_at->locale('es')->isoFormat('D MMM · HH:mm:ss') }}
                                    </td>
                                    <td class="fw-semibold">
                                        <span class="avatar is-sm me-2">{{ strtoupper(substr($attendance->employee->name ?? '?', 0, 1)) }}</span>
                                        {{ $attendance->employee->name ?? 'Sin asignar' }}
                                    </td>
                                    <td><code>{{ $attendance->user_id }}</code></td>
                                    <td><span class="badge {{ $attendance->stateColorClass() }}">{{ $attendance->stateLabel() }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('devices.show', $attendance->device) }}" class="ref-chip">
                                            <i class="bi bi-hdd-network"></i>{{ $attendance->device->name }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        @include('partials.empty-state', [
                                            'icon'  => 'bi-calendar-x',
                                            'title' => 'Sin chequeos aún',
                                            'desc'  => 'Los registros aparecerán aquí cuando se sincronicen desde los checadores.',
                                            'cta'   => ['label' => 'Ir a checadores', 'url' => route('devices.index')],
                                            'ctaLink' => true,
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
 // Function to fetch updated KPIs
 function fetchDashboardKPIs() {
     fetch('{{ route('dashboard.kpisJson') }}', {
         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
         credentials: 'same-origin'
     })
     .then(response => response.json())
     .then(data => {
         // Update KPI values
         const kpiGrid = document.querySelector('.kpi-grid');
         if (!kpiGrid) return;
         
         const kpiCards = kpiGrid.querySelectorAll('.kpi-card');
         kpiCards.forEach((card, index) => {
             const valueElem = card.querySelector('.kpi-value');
             const labelElem = card.querySelector('.kpi-label');
             const trendElem = card.querySelector('.kpi-trend');
             
             if (data.kpis && data.kpis[index]) {
                 const kpi = data.kpis[index];
                 if (valueElem) valueElem.textContent = kpi.value;
                 if (labelElem) labelElem.textContent = kpi.label;
                 if (trendElem && kpi.trend) {
                     // Update trend class and text using JavaScript
                     trendElem.className = `kpi-trend ${kpi.trend.dir}`;
                     
                     // Set arrow symbol based on direction
                     const dir = kpi.trend.dir;
                     let arrowHtml = '';
                     if (dir === 'up') arrowHtml = '▲';
                     else if (dir === 'down') arrowHtml = '▼';
                     else arrowHtml = '–';
                     
                     trendElem.innerHTML = arrowHtml + ' ' + kpi.trend.value + '%';
                 }
             }
         });
         
         // Update the existing timestamp instead of adding a new row each time.
         const banner = document.querySelector('.card.mb-4');
         if (banner) {
             let lastUpdate = banner.querySelector('.last-update');
             if (!lastUpdate) {
                 lastUpdate = document.createElement('div');
                 lastUpdate.className = 'last-update';
                 banner.insertBefore(lastUpdate, banner.firstElementChild);
             }
             lastUpdate.textContent = 'Última actualización: ' + new Date().toLocaleString('es-MX');
         }
         
         // Show toast notification on successful update
         if (window.dashToast) {
             window.dashToast({ type: 'success', title: 'Actualización completada', message: 'Los KPIs del dashboard se actualizaron correctamente' });
         }
     })
     .catch(error => {
         console.error('Error fetching dashboard KPIs:', error);
         if (window.dashToast) {
             window.dashToast({ type: 'error', title: 'Error', message: 'No se pudieron actualizar los KPIs' });
         }
     });
 }

 // Start polling every 30 seconds when page loads
 document.addEventListener('DOMContentLoaded', () => {
     fetchDashboardKPIs();
     setInterval(fetchDashboardKPIs, 30000);
 });
</script>
@endpush