@props(['variant' => 'default', 'size' => 'md', 'label', 'dot' => false, 'class' => ''])

@php
    $variantClasses = [
        'default' => 'bg-secondary text-white',
        'primary' => 'bg-primary',
        'success' => 'bg-success',
        'warning' => 'bg-warning text-dark',
        'danger' => 'bg-danger',
        'info' => 'bg-info text-dark',
        'purple' => 'bg-purple',
        'pink' => 'bg-pink',
        'teal' => 'bg-teal',
        'orange' => 'bg-warning',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-sm',
        'lg' => 'px-3 py-1 text-base',
    ];

    $baseClass = 'badge rounded-pill d-inline-flex align-items-center gap-1';
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $dotClass = $dot ? 'badge-with-dot' : '';
@endphp

<span class="{{ $baseClass }} {{ $variantClass }} {{ $sizeClass }} {{ $dotClass }} {{ $class }}">
    {{ $label }}
</span>