@props([
    'icon' => 'bi-circle',
    'label' => '',
    'value' => '',
    'color' => 'primary',
])

@php
    // Accept both semantic color keys (success, primary, etc.) and
    // raw CSS classes used by the design tokens (green, blue, teal, etc.).
    $colorMap = [
        'success' => 'green',
        'primary' => 'blue',
        'warning' => 'orange',
        'info'    => 'blue',
        'danger'  => 'red',
    ];
    $rawColors = ['teal','green','blue','purple','orange','pink','red','amber','lavender'];
    if (in_array($color, $rawColors, true)) {
        $kpiColor = $color;
    } else {
        $kpiColor = $colorMap[$color] ?? 'blue';
    }
@endphp

<div class="card kpi-card stat-card-kpi" data-stat-card {{ $attributes }}>
    <span class="kpi-icon {{ $kpiColor }}"><i class="bi {{ $icon }}"></i></span>
    <div>
        <div class="kpi-value" data-stat-value>{{ $value }}</div>
        <div class="kpi-label">{{ $label }}</div>
    </div>
    {{-- allow callers to inject extra content (trend, sparkline) via slot --}} 
    {{ $slot }}
</div>