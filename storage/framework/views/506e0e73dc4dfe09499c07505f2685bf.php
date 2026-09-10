<?php $__env->startSection('title', 'Academia - Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<section aria-labelledby="ciclo-heading" class="card mb-3" style="border-left:4px solid var(--primary)">
  <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
    <div class="d-flex flex-column gap-1" style="min-width:220px">
      <span id="ciclo-heading" class="text-tertiary-token" style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;font-weight:700">Ciclo activo</span>
      <div class="d-flex align-items-center gap-2">
        <span class="badge badge-with-dot <?php echo e($ciclo->activo ? 'cat-green' : 'cat-gray'); ?>"><?php echo e($ciclo->activo ? 'Activo' : 'Inactivo'); ?></span>
        <h2 class="h6 mb-0 fw-bold" data-cycle-label><?php echo e($ciclo->label); ?> &mdash; <?php echo e($ciclo->descripcion ?: 'Sin descripcion'); ?></h2>
      </div>
      <div class="small text-secondary-token" style="font-family:'JetBrains Mono',monospace"><?php echo e($ciclo->fechaInicialFormateada); ?> &mdash; <?php echo e($ciclo->fechaFinalFormateada); ?></div>
    </div>

    <div class="vr d-none d-md-block" style="height:44px;background:var(--border)"></div>

    
    <form method="GET" action="<?php echo e(route('academia.dashboard')); ?>" class="d-flex align-items-center gap-2 flex-wrap" id="ciclo-switcher" aria-label="Cambiar ciclo escolar">
      <label for="ciclo_principal" class="form-label mb-0 small text-secondary-token fw-semibold">Cambiar ciclo</label>
      <select name="ciclo_principal" id="ciclo_principal" class="form-select form-select-sm" style="min-width:220px;max-width:280px"
              aria-label="Seleccionar ciclo escolar" data-cycle-select>
        <?php $__currentLoopData = $ciclos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($c->label); ?>" <?php echo e($c->label === $ciclo->label ? 'selected' : ''); ?>>
            <?php echo e($c->label); ?><?php echo e($c->descripcion ? ' - '.Str::limit($c->descripcion, 28) : ''); ?> <?php echo e($c->activo ? '' : '(inactivo)'); ?>

          </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <button type="submit" class="btn btn-sm btn-outline-secondary" data-cycle-submit>Ver</button>
      <span class="small text-tertiary-token d-none d-lg-inline" data-cycle-hint><?php echo e($ciclos->count()); ?> ciclos disponibles</span>
    </form>

    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="<?php echo e(route('academia.ciclos.show', $ciclo)); ?>" class="btn btn-outline-primary btn-sm">Ver detalle <i class="bi bi-arrow-right ms-1"></i></a>
      <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-ghost btn-sm">Todos los ciclos</a>
    </div>
  </div>
  
  <div class="px-3 pb-2 small text-tertiary-token d-flex align-items-center gap-2">
    <i class="bi bi-info-circle"></i>
    <span>Los conteos con <strong class="text-secondary-token">"en este ciclo"</strong> usan filtro por (inicial,final,periodo). Los valores <em>de ...</em> son totales globales.</span>
  </div>
</section>


<?php if(($kpis['grupos'] ?? 0) === 0 && ($kpis['horarios'] ?? 0) === 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="status">
    <i class="bi bi-exclamation-triangle"></i>
    <span>Este ciclo aun no tiene grupos ni horarios.
      <a href="<?php echo e(route('academia.grupos.index', ['ciclo_principal' => $ciclo->label])); ?>" class="alert-link">Crear grupo</a>
      o sincronizar desde Firebird.
    </span>
  </div>
<?php endif; ?>


<div id="kpi-region" aria-live="polite" aria-busy="false">
  <div class="kpi-grid" data-kpis-url="<?php echo e(route('dashboard.kpisJson')); ?>" data-cycle="<?php echo e($ciclo->label); ?>">
    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-people','label' => 'Grupos','value' => $kpis['grupos'],'color' => 'purple']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-people'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Grupos'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['grupos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('purple')]); ?>
      <?php if(isset($totales['grupos'])): ?>
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de <?php echo e($totales['grupos']); ?> en total</div>
      <?php endif; ?>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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

    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-mortarboard','label' => 'Alumnos inscritos','value' => $kpis['alumnos'],'color' => 'blue']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-mortarboard'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Alumnos inscritos'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['alumnos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('blue')]); ?>
      <?php if(isset($totales['alumnos'])): ?>
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de <?php echo e($totales['alumnos']); ?> activos</div>
      <?php endif; ?>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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

    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-person-badge','label' => 'Profesores asignados','value' => $kpis['profesores_ciclo'] ?? $kpis['profesores'],'color' => 'green']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-person-badge'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Profesores asignados'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['profesores_ciclo'] ?? $kpis['profesores']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('green')]); ?>
      <?php if(isset($totales['profesores'])): ?>
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de <?php echo e($totales['profesores']); ?> registrados</div>
      <?php endif; ?>
      <button type="button" class="btn btn-sm btn-ghost p-0 ms-1" data-bs-toggle="tooltip" title="Con al menos 1 horario en este ciclo" aria-label="Que significa profesores asignados"><i class="bi bi-question-circle"></i></button>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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

    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-calendar-week','label' => 'Horarios programados','value' => $kpis['horarios'],'color' => 'orange']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-calendar-week'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Horarios programados'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['horarios']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('orange')]); ?>
      <?php if(isset($totales['horarios'])): ?>
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de <?php echo e($totales['horarios']); ?> totales</div>
      <?php endif; ?>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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

    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-file-earmark-text','label' => 'Evaluaciones','value' => $kpis['kardex'] ?? 0,'color' => 'pink']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-file-earmark-text'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Evaluaciones'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['kardex'] ?? 0),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('pink')]); ?>
      <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>en este ciclo</div>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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

    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => 'bi-book','label' => 'Cursos','value' => $kpis['cursos'],'color' => 'teal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('bi-book'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Cursos'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['cursos']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('teal')]); ?>
      <?php if(isset($totales['cursos'])): ?>
        <div class="kpi-trend flat small text-tertiary-token" data-kpi-caption>de <?php echo e($totales['cursos']); ?> en total</div>
      <?php endif; ?>
      <div class="kpi-trend flat" data-kpi-trend>&ndash;</div>
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
  </div>
  
  <template id="kpi-skeleton"><div class="kpi-card card h-100"><div class="card-body"><div class="skeleton" style="height:14px;width:60%"></div><div class="skeleton mt-2" style="height:28px;width:40%"></div></div></div></template>
</div>


<section aria-labelledby="modulos-heading" class="mb-4">
  <div class="section-heading">
    <h2 id="modulos-heading" class="h6 fw-bold mb-0">Modulos</h2>
    <span class="small text-tertiary-token">Clic para ver lista filtrada por <?php echo e($ciclo->label); ?></span>
  </div>

  <?php
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
  ?>

  <div class="module-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
    <?php $__currentLoopData = $modulos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e($m['href']); ?>" class="card card-link h-100 p-3 text-decoration-none" style="border-left:3px solid var(--cat-<?php echo e($m['color']); ?>);"
         aria-label="<?php echo e($m['label']); ?>: <?php echo e($m['ciclo'] ?? $m['total'] ?? 0); ?> registros, <?php echo e($m['desc']); ?>">
        <div class="d-flex align-items-start gap-3">
          <span class="kpi-icon <?php echo e($m['color']); ?>" style="width:42px;height:42px;flex:0 0 42px"><i class="bi <?php echo e($m['icon']); ?>"></i></span>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold" style="font-size:13px"><?php echo e($m['label']); ?></div>
            <div class="d-flex align-items-baseline gap-2">
              <span class="fw-bold" style="font-family:'JetBrains Mono',monospace;font-size:22px" data-mod-ciclo="<?php echo e($m['label']); ?>"><?php echo e($m['ciclo'] !== null ? $m['ciclo'] : '&mdash;'); ?></span>
              <?php if($m['ciclo'] !== null): ?>
                <span class="small text-tertiary-token">en este ciclo</span>
              <?php endif; ?>
            </div>
            <?php if($m['total'] !== null): ?>
              <div class="small text-tertiary-token">de <?php echo e($m['total']); ?> en total</div>
            <?php endif; ?>
            <div class="small text-secondary-token mt-1"><?php echo e($m['desc']); ?> <i class="bi bi-arrow-right ms-1"></i></div>
          </div>
        </div>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</section>


<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100" data-chart="horarios-dia" data-cycle="<?php echo e($ciclo->label); ?>">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Horarios por dia &middot; <?php echo e($ciclo->label); ?></span>
        <span class="small text-tertiary-token" data-chart-total><?php echo e(array_sum($horariosPorDia)); ?> horarios</span>
      </div>
      <div class="card-body position-relative" style="min-height:140px">
        <?php
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
        ?>

        
        <?php if(array_sum($horariosPorDia) === 0): ?>
          <div class="text-center py-4 text-tertiary-token">
            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
            <div>Sin horarios en este ciclo</div>
            <a href="<?php echo e(route('academia.grupos.index', ['ciclo_principal' => $ciclo->label])); ?>" class="btn btn-sm btn-outline-primary mt-2">Ver grupos</a>
          </div>
        <?php else: ?>
          <svg class="trend-chart" viewBox="0 0 <?php echo e($w); ?> <?php echo e($h); ?>" preserveAspectRatio="none" role="img"
               aria-label="Horarios por dia, <?php echo e($ciclo->label); ?>: <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($d['label']); ?> <?php echo e($d['value']); ?>, <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>">
            <defs>
              <linearGradient id="trendGradientAcademia" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="var(--primary)" stop-opacity=".28"/>
                <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <?php if($areaPath): ?> <path class="area-fill" d="<?php echo e($areaPath); ?>" fill="url(#trendGradientAcademia)"/> <?php endif; ?>
            <path class="area-line" d="<?php echo e($linePath); ?>" stroke="var(--primary)" stroke-width="2" fill="none"/>
            <?php $__currentLoopData = $coords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$x, $y]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <circle class="area-dot" cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="4" fill="var(--primary)">
                <title><?php echo e($data[$i]['label']); ?> &middot; <?php echo e($data[$i]['value']); ?> horarios</title>
              </circle>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if($n > 0): ?>
              <text x="<?php echo e($first[0]); ?>" y="<?php echo e($h - 4); ?>" font-size="11" fill="var(--text-tertiary)"><?php echo e($data[0]['label']); ?></text>
              <text x="<?php echo e($last[0]); ?>" y="<?php echo e($h - 4); ?>" font-size="11" fill="var(--text-tertiary)" text-anchor="end"><?php echo e($data[$n - 1]['label']); ?></text>
            <?php endif; ?>
          </svg>
          
          <table class="visually-hidden">
            <caption>Horarios por dia</caption>
            <thead><tr><th scope="col">Dia</th><th scope="col">Horarios</th></tr></thead>
            <tbody><?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($d['label']); ?></td><td><?php echo e($d['value']); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
          </table>
        <?php endif; ?>
        <div class="chart-loading d-none position-absolute top-50 start-50 translate-middle"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><span class="fw-bold">Tipo de horario</span></div>
      <div class="card-body">
        <?php if(empty($porOrigen)): ?>
          <div class="text-center text-tertiary-token py-3">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            Sin datos de origen
          </div>
        <?php else: ?>
          <div class="row g-3">
            <?php $__currentLoopData = $porOrigen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $origen => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="col-6 col-lg-12">
                <div class="card text-center h-100">
                  <div class="card-body">
                    <div class="h4 mb-1 mono" data-origen="<?php echo e($origen); ?>"><?php echo e($total); ?></div>
                    <div class="small text-muted">
                      <?php switch($origen):
                        case ('HD'): ?> Hora Docente (PTC) <?php break; ?>
                        <?php case ('CA'): ?> Carga Asignada (PA) <?php break; ?>
                        <?php default: ?> <?php echo e($origen); ?>

                      <?php endswitch; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-bold">Ciclos disponibles</span>
    <a href="<?php echo e(route('academia.ciclos.index')); ?>" class="btn btn-sm btn-ghost">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
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
          <?php $__empty_1 = true; $__currentLoopData = $ciclos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td class="fw-semibold"><?php echo e($c->label); ?></td>
              <td><?php echo e($c->descripcion); ?></td>
              <td class="small text-muted"><?php echo e($c->fechaInicialFormateada); ?> - <?php echo e($c->fechaFinalFormateada); ?></td>
              <td>
                <span class="badge badge--status <?php echo e($c->activo ? 'badge--active' : 'badge--inactive'); ?>">
                  <?php echo e($c->activo ? 'Activo' : 'Inactivo'); ?>

                </span>
              </td>
              <td class="text-end">
                <a href="<?php echo e(route('academia.ciclos.show', $c)); ?>" class="btn btn-sm btn-outline-primary">Ver</a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="5" class="text-center text-tertiary-token py-4">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                No hay ciclos configurados.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>


<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>


<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelrelojnew\resources\views\academia\dashboard\index.blade.php ENDPATH**/ ?>