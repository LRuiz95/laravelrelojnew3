<?php $__env->startSection('title', 'Panel de control'); ?>

<?php $__env->startSection('content'); ?>

<div class="card mb-4" style="border-left:4px solid var(--primary)">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge badge-with-dot cat-green">Activo</span>
                <h2 class="h6 mb-0 text-secondary-token fw-semibold"><?php echo e($todayInfo['label']); ?></h2>
            </div>
            <div class="fw-bold fs-5"><?php echo e($todayInfo['date']); ?></div>
        </div>
        <div class="vr d-none d-md-block" style="height:44px;background:var(--border)"></div>
        <div class="d-flex flex-column gap-1 fs-6">
            <div><i class="bi bi-person-check text-secondary-token me-2"></i><?php echo e($todayInfo['employees']); ?> empleado(s) con chequeo</div>
            <div><i class="bi bi-calendar-check text-secondary-token me-2"></i><?php echo e($todayInfo['checks']); ?> chequeos registrados hoy</div>
            <?php if($todayInfo['hasChecksToday']): ?>
                <div class="small text-secondary-token">
                    <i class="bi bi-sunrise me-2"></i>Primera: <strong><?php echo e($todayInfo['firstCheck']->format('H:i')); ?></strong>
                    <span class="text-tertiary-token mx-1">·</span>
                    Última: <strong><?php echo e($todayInfo['lastCheck']->format('H:i')); ?></strong>
                </div>
            <?php endif; ?>
            <div class="small text-tertiary-token">
                <i class="bi bi-clock-history me-2"></i>
                <?php if($todayInfo['lastCheck']): ?>
                    Última checada de hoy: <strong class="text-secondary-token"><?php echo e($todayInfo['lastCheck']->diffForHumans()); ?></strong>
                <?php elseif($todayInfo['lastActivity']): ?>
                    Aún sin chequeos hoy — última actividad:
                    <strong class="text-secondary-token"><?php echo e($todayInfo['lastActivity']->locale('es')->isoFormat('D MMM · H:i')); ?></strong>
                    (<?php echo e($todayInfo['lastActivity']->diffForHumans()); ?>)
                <?php else: ?>
                    Aún no hay chequeos registrados en el sistema
                <?php endif; ?>
            </div>
        </div>
        <div class="ms-auto">
            <a href="<?php echo e(route('attendances.index')); ?>" class="btn btn-outline-primary">
                Ver detalle del día <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>


<div class="kpi-grid" data-kpis-url="<?php echo e(route('dashboard.kpisJson')); ?>">
    <?php $__currentLoopData = $kpis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => $kpi['icon'],'label' => $kpi['label'],'value' => $kpi['value'],'color' => $kpi['color']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpi['icon']),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpi['label']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpi['value']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpi['color'])]); ?>
            <div class="kpi-trend <?php echo e($kpi['trend']['dir']); ?>" data-kpi-trend>
                <?php if($kpi['trend']['dir'] === 'up'): ?> ▲ <?php elseif($kpi['trend']['dir'] === 'down'): ?> ▼ <?php else: ?> – <?php endif; ?>
                <?php echo e($kpi['trend']['dir'] === 'flat' ? '' : $kpi['trend']['value'] . '%'); ?>

                <?php if(isset($kpi['caption'])): ?>
                    <span class="text-tertiary-token ms-1" style="font-weight:500"><?php echo e($kpi['caption']); ?></span>
                <?php endif; ?>
            </div>
            <div class="kpi-spark">
                <?php echo $__env->make('partials.sparkline', ['points' => $kpi['spark'], 'color' => 'var(--primary)', 'width' => 84, 'height' => 28], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <?php
                    $trendTitle = match ($rango) {
                        'hoy' => 'Chequeos por hora · hoy',
                        '12m' => 'Chequeos por mes · últimos 12 meses',
                        '30d' => 'Chequeos diarios · últimos 30 días',
                        default => 'Chequeos diarios · últimos 7 días',
                    };
                ?>
                <div>
                    <span class="fw-bold"><?php echo e($trendTitle); ?></span>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Rango de la gráfica de tendencia">
                    <?php $__currentLoopData = $rangos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $texto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('dashboard', ['rango' => $key])); ?>"
                           class="btn <?php echo e($rango === $key ? 'btn-primary' : 'btn-ghost'); ?>"
                           <?php if($rango === $key): ?> aria-current="page" <?php endif; ?>><?php echo e($texto); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="card-body">
        <?php
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
        ?>
        <svg class="trend-chart" viewBox="0 0 <?php echo e($w); ?> <?php echo e($h); ?>" preserveAspectRatio="none" role="img" aria-label="Gráfico de chequeos diarios">
            <defs>
                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--primary)" stop-opacity=".28"/>
                    <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <?php if($areaPath): ?> <path class="area-fill" d="<?php echo e($areaPath); ?>"/> <?php endif; ?>
            <path class="area-line" d="<?php echo e($linePath); ?>"/>
            <?php $__currentLoopData = $coords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$x, $y]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <circle class="area-dot" cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="3.5">
                    <title><?php echo e($trend[$i]['label']); ?> · <?php echo e($points[$i]); ?> chequeo(s)</title>
                </circle>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if($n > 0): ?>
                <text x="<?php echo e($first[0]); ?>" y="<?php echo e($h - 4); ?>" font-size="10" fill="var(--text-tertiary)"><?php echo e($trend[0]['label']); ?></text>
                <text x="<?php echo e($last[0]); ?>" y="<?php echo e($h - 4); ?>" font-size="10" fill="var(--text-tertiary)" text-anchor="end"><?php echo e($trend[$n - 1]['label']); ?></text>
            <?php endif; ?>
        </svg>
    </div>
</div>


<div class="section-heading">
    <h2>Estado de la operación</h2>
    <span class="text-tertiary-token small">Distribución actual del sistema</span>
</div>
<div class="pipeline">
    <?php $__currentLoopData = $pipeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="pipeline-step" style="--ps-color: var(--cat-<?php echo e($step['color']); ?>)">
            <?php if (! ($loop->last)): ?>
                <span class="pipeline-connector"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>
            <div class="ps-top">
                <span class="ps-value"><?php echo e($step['value']); ?></span>
                <i class="bi <?php echo e($step['icon']); ?> ps-icon"></i>
            </div>
            <div class="ps-label"><?php echo e($step['label']); ?></div>
            <div class="ps-bar"><i style="width:<?php echo e($step['bar']); ?>%"></i></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Marcados por tipo <span class="text-tertiary-token small ms-1">· hoy</span></span>
                <a href="<?php echo e(route('attendances.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])); ?>" class="btn btn-sm btn-ghost">Ver día <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body">
                <?php echo $__env->make('partials.donut', ['segments' => $donut['segments'], 'total' => $donut['total'], 'emptyText' => 'Sin chequeos hoy todavía.'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">Registros recientes</span>
                <a href="<?php echo e(route('attendances.index')); ?>" class="btn btn-sm btn-ghost">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
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
                            <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="mono text-secondary-token" style="font-size:12px">
                                        <?php echo e($attendance->recorded_at->locale('es')->isoFormat('D MMM · HH:mm:ss')); ?>

                                    </td>
                                    <td class="fw-semibold">
                                        <span class="avatar is-sm me-2"><?php echo e(strtoupper(substr($attendance->employee->name ?? '?', 0, 1))); ?></span>
                                        <?php echo e($attendance->employee->name ?? 'Sin asignar'); ?>

                                    </td>
                                    <td><code><?php echo e($attendance->user_id); ?></code></td>
                                    <td><span class="badge <?php echo e($attendance->stateColorClass()); ?>"><?php echo e($attendance->stateLabel()); ?></span></td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('devices.show', $attendance->device)); ?>" class="ref-chip">
                                            <i class="bi bi-hdd-network"></i><?php echo e($attendance->device->name); ?>

                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5">
                                        <?php echo $__env->make('partials.empty-state', [
                                            'icon'  => 'bi-calendar-x',
                                            'title' => 'Sin chequeos aún',
                                            'desc'  => 'Los registros aparecerán aquí cuando se sincronicen desde los checadores.',
                                            'cta'   => ['label' => 'Ir a checadores', 'url' => route('devices.index')],
                                            'ctaLink' => true,
                                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
 // Function to fetch updated KPIs
 function fetchDashboardKPIs() {
     fetch('<?php echo e(route('dashboard.kpisJson')); ?>', {
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

 // Force SVG charts to repaint on theme change (CSS variables update)
 function forceChartsRepaint() {
     const trendChart = document.querySelector('.trend-chart');
     const donutChart = document.querySelector('.donut-wrap svg');
     
     [trendChart, donutChart].forEach(chart => {
         if (!chart) return;
         // Force reflow by temporarily removing and re-adding
         const parent = chart.parentNode;
         const nextSibling = chart.nextSibling;
         parent.removeChild(chart);
         parent.insertBefore(chart, nextSibling);
     });
 }

 // Start polling every 30 seconds when page loads
 document.addEventListener('DOMContentLoaded', () => {
     fetchDashboardKPIs();
     setInterval(fetchDashboardKPIs, 30000);
     
     // Listen for theme changes and force SVG repaint
     document.addEventListener('themechange', forceChartsRepaint);
 });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views/dashboard.blade.php ENDPATH**/ ?>