@props(['id', 'title', 'size' => 'md', 'closeable' => true])

@php
    $sizeClasses = [
        'sm' => 'modal-sm',
        'md' => '',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        'full' => 'modal-fullscreen',
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
@endphp

<div class="offcanvas offcanvas-end" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}Label">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="{{ $id }}Label">{{ $title }}</h5>
        @if ($closeable)
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        @endif
    </div>
    <div class="offcanvas-body">
        {{ $slot }}
    </div>
</div>