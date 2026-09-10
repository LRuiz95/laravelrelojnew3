@extends('layouts.admin')

@section('title', 'Academia - Dashboard')

@section('content')
{{-- ========== CICLO HEADER — selector prominente + contexto ========== --}}
<section aria-labelledby="ciclo-heading" class="card mb-3" style="border-left:4px solid var(--primary)">
  <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
    <div class="d-flex flex-column gap-1" style="min-width:220px">
      <span id="ciclo-heading" class="text-tertiary-token" style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;font-weight:700">Ciclo activo</span>
      <div class="d-flex align-items-center gap-2">
        <span class="badge badge-with-dot {{ $ciclo->activo ? 'cat-green' : 'cat-gray' }}">{{ $ciclo->activo ? 'Activo' : 'Inactivo' }}</span>
        <h2 class="h6 mb-0 fw-bold" data-cycle-label>{{ $ciclo->label }} &mdash; {{ $ciclo->descripcion ?: 'Sin descripcion' }}</h2>
      </div>
      <div class="small text-secondary-token" style="font-family:'JetBrains Mono',monospace">{{ $ciclo->fechaInicialFormateada }} &mdash; {{ $ciclo->fechaFinalFormateada }}</div>
    </div>

    <div class="vr d-none d-md-block" style="height:44px;background:var(--border)"></div>

    {{-- Selector in-page: SSR ?ciclo_principal= + enhancement JS --}}
    <form method="GET" action="{{ route('academia.dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap" id="ciclo-switcher" aria-label="Cambiar ciclo escolar">
      <label for="ciclo_principal" class="form-label mb-0 small text-secondary-token fw-semibold">Cambiar ciclo</label>
      <select name="ciclo_principal" id="ciclo_principal" class="form-select form-select-sm" style="min-width:220px;max-width:280px"
              aria-label="Seleccionar ciclo escolar" data-cycle-select>
        @foreach ($ciclos as $c)
          <option value="{{ $c->label }}" {{ $c->label === $ciclo->label ? 'selected' : '' }}>
            {{ $c->label }}{{ $c->descripcion ? ' - '.Str::limit($c->descripcion, 28) : '' }} {{ $c->activo ? '' : '(inactivo)' }}
          </option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-sm btn-outline-secondary" data-cycle-submit>Ver</button>
      <span class="small text-tertiary-token d-none d-lg-inline" data-cycle-hint>{{ $ciclos->count() }} ciclos disponibles</span>
    </form>

    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="{{ route('academia.ciclos.show', $ciclo) }}" class="btn btn-outline-primary btn-sm">Ver detalle <i class="bi bi-arrow-right ms-1"></i></a>
      <a href="{{ route('academia.ciclos.index') }}" class="btn btn-ghost btn-sm">Todos los ciclos</a>
    </div>
  </div>
  {{-- hint contextual --}}
  <div class="px-3 pb-2 small text-tertiary-token d-flex align-items-center gap-2">
    <i class="bi bi-info-circle"></i>
    <span>Los conteos con <strong class="text-secondary-token">"en este ciclo"</strong> usan filtro por (inicial,final,periodo). Los valores <em>de ...</em> son totales globales.</span>
  </div>
</section>

{{-- ========== BANNER EMPTY — ciclo sin grupos ni horarios ========== --}}
@if (($kpis['grupos'] ?? 0) === 0 && ($kpis['horarios'] ?? 0) === 0)
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="status">
    <i class="bi bi-exclamation-triangle"></i>
    <span>Este ciclo aun no tiene grupos ni horarios.
      <a href="{{ route('academia.grupos.index', ['ciclo_principal' => $ciclo->label]) }}" class="alert-link">Crear grupo</a>
      o sincronizar desde Firebird.
    </span>
  </div>
@endif

{{-- ========== KPIs dual (ciclo + total) ========== --}}
<div id="kpi-region" aria-live="polite" aria-busy="false">
  <div class="kpi-grid" data-kpis-url="{{ route('dashboard.kpisJson') }}" data-cycle="{{ $ciclo->label }}">
    {{-- Grupos --}}
    <x-stat-card :icon="'bi-people'" :label="'Grupos'" :value="$kpis['grupos']" :color="'purple'">
      @isset($totales['grupos'])
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['grupos'] }} en total</div>
      @endisset
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>

    {{-- Alumnos inscritos (en ciclo) --}}
    <x-stat-card :icon="'bi-mortarboard'" :label="'Alumnos inscritos'" :value="$kpis['alumnos']" :color="'blue'">
      @isset($totales['alumnos'])
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['alumnos'] }} activos</div>
      @endisset
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>

    {{-- Profesores asignados (distintos en horarios del ciclo) --}}
    <x-stat-card :icon="'bi-person-badge'" :label="'Profesores asignados'" :value="$kpis['profesores_ciclo'] ?? $kpis['profesores']" :color="'green'">
      @isset($totales['profesores'])
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['profesores'] }} registrados</div>
      @endisset
      <button type="button" class="btn btn-sm btn-ghost p-0 ms-1" data-bs-toggle="tooltip" title="Con al menos 1 horario en este ciclo" aria-label="Que significa profesores asignados"><i class="bi bi-question-circle"></i></button>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>

    {{-- Horarios --}}
    <x-stat-card :icon="'bi-calendar-week'" :label="'Horarios programados'" :value="$kpis['horarios']" :color="'orange'">
      @isset($totales['horarios'])
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['horarios'] }} totales</div>
      @endisset
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>

    {{-- Kardex evaluaciones --}}
    <x-stat-card :icon="'bi-file-earmark-text'" :label="'Evaluaciones'" :value="$kpis['kardex'] ?? 0" :color="'pink'">
      <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>en este ciclo</div>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>

    {{-- Cursos --}}
    <x-stat-card :icon="'bi-book'" :label="'Cursos'" :value="$kpis['cursos']" :color="'teal'">
      @isset($totales['cursos'])
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de {{ $totales['cursos'] }} en total</div>
      @endisset
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
    </x-stat-card>
  </div>
  {{-- Skeleton template (hidden, clonado por JS en loading) --}}
  <template id="kpi-skeleton"><div class="kpi-card card h-100"><div class="card-body"><div class="skeleton" style="height:14px;width:60%"></div><div class="skeleton mt-2" style="height:28px;width:40%"></div></div></div></template>
</div>

{{-- ========== MODULOS — gallery con conteos ciclo/total ========== --}}
<section aria-labelledby="modulos-heading" class="mb-4">
  <div class="section-heading">
    <h2 id="modulos-heading" class="h6 fw-bold mb-0">Modulos</h2>
    <span class="small text-tertiary-token">Clic para ver lista filtrada por {{ $ciclo->label }}</span>
  </div>

  @php
    $modulos = [
      ['label' => 'Grupos',     'icon' => 'bi-people',          'color' => 'purple',  'ciclo' => $kpis['grupos'],                                     'total' => $totales['grupos'] ?? null,         'href' => route('academia.grupos.index', ['ciclo_principal' => $ciclo->label]),      'desc' => 'Ver y gestionar grupos'],
      ['label' => 'Alumnos',    'icon' => 'bi-mortarboard',     'color' => 'blue',    'ciclo' => $kpis['alumnos'],                                    'total' => $totales['alumnos'] ?? null,        'href' => route('academia.alumnos.index', ['ciclo_principal' => $ciclo->label]),     'desc' => 'Buscar y ver kardex'],
      ['label' => 'Profesores', 'icon' => 'bi-person-badge',   'color' => 'green',   'ciclo' => $kpis['profesores_ciclo'] ?? $kpis['profesores'],     'total' => $totales['profesores'] ?? null,     'href' => route('academia.profesores.index'),                                        'desc' => 'Horarios y contratos (global)'],
      ['label' => 'Horarios',   'icon' => 'bi-calendar-week',  'color' => 'orange',  'ciclo' => $kpis['horarios'],                                   'total' => $totales['horarios'] ?? null,       'href' => route('academia.horarios.clase', ['ciclo_principal' => $ciclo->label]),    'desc' => 'Clases y asistencia'],
      ['label' => 'Kardex',     'icon' => 'bi-file-earmark-text','color' => 'pink',   'ciclo' => $kpis['kardex'] ?? 0,                                'total' => $totales['kardex'] ?? null,         'href' => route('academia.kardex.index', ['ciclo_principal' => $ciclo->label]),      'desc' => 'Evaluaciones del ciclo'],
      ['label' => 'Cursos',     'icon' => 'bi-book',            'color' => 'teal',    'ciclo' => $kpis['cursos'],                                     'total' => $totales['cursos'] ?? null,         'href' => route('academia.cursos.index', ['ciclo_principal' => $ciclo->label]),      'desc' => 'Oferta por ciclo'],
      ['label' => 'Materias',   'icon' => 'bi-journal-bookmark','color' => 'amber',   'ciclo' => $kpis['materias_ciclo'] ?? null,                     'total' => $totales['materias'] ?? null,        'href' => route('academia.planes.index'),                                            'desc' => 'Catalogo global'],
      ['label' => 'Planes',     'icon' => 'bi-collection',     'color' => 'lavender','ciclo' => null,                                                'total' => $totales['planes'] ?? null,         'href' => route('academia.planes.index'),                                            'desc' => 'Planes de estudio'],
    ];
  @endphp

  <div class="module-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
    @foreach($modulos as $m)
      <a href="{{ $m['href'] }}" class="card card-link h-100 p-3 text-decoration-none" style="border-left:3px solid var(--cat-{{ $m['color'] }});"
         aria-label="{{ $m['label'] }}: {{ $m['ciclo'] ?? $m['total'] ?? 0 }} registros, {{ $m['desc'] }}">
        <div class="d-flex align-items-start gap-3">
          <span class="kpi-icon {{ $m['color'] }}" style="width:42px;height:42px;flex:0 0 42px"><i class="bi {{ $m['icon'] }}"></i></span>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold" style="font-size:13px">{{ $m['label'] }}</div>
            <div class="d-flex align-items-baseline gap-2">
              <span class="fw-bold" style="font-family:'JetBrains Mono',monospace;font-size:22px" data-mod-ciclo="{{ $m['label'] }}">{{ $m['ciclo'] !== null ? $m['ciclo'] : '&mdash;' }}</span>
              @if($m['ciclo'] !== null)
                <span class="small text-tertiary-token">en este ciclo</span>
              @endif
            </div>
            @if($m['total'] !== null)
              <div class="small text-tertiary-token">de {{ $m['total'] }} en total</div>
            @endif
            <div class="small text-secondary-token mt-1">{{ $m['desc'] }} <i class="bi bi-arrow-right ms-1"></i></div>
          </div>
        </div>
      </a>
    @endforeach
  </div>
</section>

{{-- ========== DIAGNOSTICO — graficos con estados ========== --}}
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100" data-chart="horarios-dia" data-cycle="{{ $ciclo->label }}">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Horarios por dia &middot; {{ $ciclo->label }}</span>
        <span class="small text-tertiary-token" data-chart-total>{{ array_sum($horariosPorDia) }} horarios</span>
      </div>
      <div class="card-body position-relative" style="min-height:140px">
        @php
          $dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mie', 4 => 'Jue', 5 => 'Vie', 6 => 'Sab', 7 => 'Dom'];
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

        {{-- Empty overlay --}}
        @if(array_sum($horariosPorDia) === 0)
          <div class="text-center py-4 text-tertiary-token">
            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
            <div>Sin horarios en este ciclo</div>
            <a href="{{ route('academia.grupos.index', ['ciclo_principal' => $ciclo->label]) }}" class="btn btn-sm btn-outline-primary mt-2">Ver grupos</a>
          </div>
        @else
          <svg class="trend-chart" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img"
               aria-label="Horarios por dia, {{ $ciclo->label }}: @foreach($data as $d){{ $d['label'] }} {{ $d['value'] }}, @endforeach">
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
                <title>{{ $data[$i]['label'] }} &middot; {{ $data[$i]['value'] }} horarios</title>
              </circle>
            @endforeach
            @if ($n > 0)
              <text x="{{ $first[0] }}" y="{{ $h - 4 }}" font-size="11" fill="var(--text-tertiary)">{{ $data[0]['label'] }}</text>
              <text x="{{ $last[0] }}" y="{{ $h - 4 }}" font-size="11" fill="var(--text-tertiary)" text-anchor="end">{{ $data[$n - 1]['label'] }}</text>
            @endif
          </svg>
          {{-- tabla sr-only para accesibilidad --}}
          <table class="visually-hidden">
            <caption>Horarios por dia</caption>
            <thead><tr><th scope="col">Dia</th><th scope="col">Horarios</th></tr></thead>
            <tbody>@foreach($data as $d)<tr><td>{{ $d['label'] }}</td><td>{{ $d['value'] }}</td></tr>@endforeach</tbody>
          </table>
        @endif
        <div class="chart-loading d-none position-absolute top-50 start-50 translate-middle"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><span class="fw-bold">Tipo de horario</span></div>
      <div class="card-body">
        @if(empty($porOrigen))
          <div class="text-center text-tertiary-token py-3">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            Sin datos de origen
          </div>
        @else
          <div class="row g-3">
            @foreach($porOrigen as $origen => $total)
              <div class="col-6 col-lg-12">
                <div class="card text-center h-100">
                  <div class="card-body">
                    <div class="h4 mb-1 mono" data-origen="{{ $origen }}">{{ $total }}</div>
                    <div class="small text-muted">
                      @switch($origen)
                        @case('HD') Hora Docente (PTC) @break
                        @case('CA') Carga Asignada (PA) @break
                        @default {{ $origen }}
                      @endswitch
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- ========== CICLOS DISPONIBLES ========== --}}
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-bold">Ciclos disponibles</span>
    <a href="{{ route('academia.ciclos.index') }}" class="btn btn-sm btn-ghost">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Ciclo</th>
            <th scope="col">Descripcion</th>
            <th scope="col">Fechas</th>
            <th scope="col">Estado</th>
            <th scope="col" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($ciclos as $c)
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
          @empty
            <tr>
              <td colspan="5" class="text-center text-tertiary-token py-4">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                No hay ciclos configurados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

{{-- ========== CSS inline ========== --}}
@push('styles')
<style>
  /* Module grid responsive */
  @media (max-width:1100px) { .module-grid { grid-template-columns:repeat(3,1fr)!important } }
  @media (max-width:768px)  { .module-grid { grid-template-columns:repeat(2,1fr)!important } }
  @media (max-width:480px)  { .module-grid { grid-template-columns:1fr!important } }

  /* Card link hover */
  .card-link { transition: border-color .15s, transform .15s, box-shadow .15s; }
  .card-link:hover { border-color:var(--primary)!important; transform:translateY(-1px); box-shadow:0 4px 16px rgba(0,0,0,.08); }
  html[data-theme="dark"] .card-link:hover { box-shadow:0 8px 24px rgba(0,0,0,.22); }
  .card-link:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }

  /* Skeleton animation */
  .skeleton { background:var(--border); border-radius:8px; animation:skeleton-pulse 1.2s ease-in-out infinite; }
  @keyframes skeleton-pulse { 0%,100%{opacity:.6} 50%{opacity:1} }

  /* KPI flash on update */
  .kpi-value.kpi-flash { animation:kpiFlash .9s ease-out 1; }

  /* Header stacking on mobile */
  @media (max-width:576px) {
    .card-body.d-flex { flex-direction:column; align-items:stretch!important; }
    .card-body.d-flex .ms-auto { margin-left:0!important; justify-content:stretch; }
    .card-body.d-flex .ms-auto .btn { flex:1; }
  }

  /* KPI value override small screens */
  @media (max-width:640px) {
    .kpi-value { font-size:22px; }
  }
</style>
@endpush

{{-- ========== JS enhancement (progresivo, no bloquea SSR) ========== --}}
@push('scripts')
<script>
(function() {
  var sel = document.querySelector('[data-cycle-select]');
  var form = document.getElementById('ciclo-switcher');
  var kpiGrid = document.querySelector('.kpi-grid');
  var region = document.getElementById('kpi-region');

  if (!sel || !kpiGrid) return;

  // Intercept change for AJAX; if fetch unavailable, SSR handles it
  sel.addEventListener('change', function(e) {
    var label = e.target.value;

    // If no fetch support, fall through to SSR form submit
    if (!window.fetch) return;

    var url = kpiGrid.getAttribute('data-kpis-url') + '?ciclo=' + encodeURIComponent(label);

    // Loading state
    region.setAttribute('aria-busy', 'true');
    kpiGrid.style.opacity = '.6';
    document.querySelectorAll('[data-chart] .chart-loading').forEach(function(el) { el.classList.remove('d-none'); });

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function(res) {
        if (!res.ok) throw new Error('kpis fetch ' + res.status);
        return res.json();
      })
      .then(function(data) {
        if (!data || !data.kpis) throw new Error('invalid response');

        // Update KPI values
        var map = { 'Grupos': 'grupos', 'Alumnos inscritos': 'alumnos', 'Profesores asignados': 'profesores_ciclo', 'Horarios programados': 'horarios', 'Evaluaciones': 'kardex', 'Cursos': 'cursos' };
        kpiGrid.querySelectorAll('.kpi-card').forEach(function(card) {
          var lab = card.querySelector('.kpi-label, h6');
          if (!lab) return;
          var key = map[lab.textContent.trim()];
          if (!key) return;
          var val = data.kpis[key];
          if (val == null) return;
          var valEl = card.querySelector('[data-stat-value], .kpi-value');
          if (valEl) {
            valEl.textContent = val;
            valEl.classList.remove('kpi-flash');
            void valEl.offsetWidth; // reflow to restart animation
            valEl.classList.add('kpi-flash');
          }
          // Update caption if totals available
          var cap = card.querySelector('[data-kpi-caption]');
          if (cap && data.totales && data.totales[key] != null) {
            cap.textContent = 'de ' + data.totales[key] + ' en total';
          }
        });

        // Update module counts
        Object.keys(map).forEach(function(label) {
          var key = map[label];
          var el = document.querySelector('[data-mod-ciclo="' + label + '"]');
          if (el && data.kpis[key] != null) el.textContent = data.kpis[key];
        });

        // Update header
        var headerLabel = document.querySelector('[data-cycle-label]');
        if (headerLabel) {
          headerLabel.textContent = (data.ciclo ? data.ciclo.label : label) + ' \u2014 ' + (data.ciclo && data.ciclo.descripcion ? data.ciclo.descripcion : '');
        }

        // Update URL without reload
        history.replaceState(null, '', '?ciclo_principal=' + encodeURIComponent(label));

        // Sync session in background (non-blocking)
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
          fetch('/academia/set-ciclo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMeta.content },
            body: JSON.stringify({ ciclo_label: label })
          }).catch(function() { /* silent */ });
        }

        // Toast
        if (typeof window.showToast === 'function') {
          window.showToast('success', 'Ciclo ' + label + ' cargado');
        }
      })
      .catch(function(err) {
        console.warn(err);
        if (typeof window.showToast === 'function') {
          window.showToast('error', 'No se pudo actualizar. Se mantiene el ciclo anterior.', '');
        }
        // Fallback: SSR reload
        form.submit();
        return;
      })
      .finally(function() {
        region.setAttribute('aria-busy', 'false');
        kpiGrid.style.opacity = '';
        document.querySelectorAll('[data-chart] .chart-loading').forEach(function(el) { el.classList.add('d-none'); });
      });
  });

  // Prevent default form submit if JS already handled it
  form.addEventListener('submit', function(e) {
    if (window.fetch) e.preventDefault();
  });
})();
</script>
@endpush
