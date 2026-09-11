@props([
    'action' => null,
    'method' => 'GET',
    'clearUrl' => null,
    'clearLabel' => 'Limpiar',
])

<form method="{{ $method }}" action="{{ $action }}" class="row g-3 align-items-end">
    {{ $slot }}

    @if ($clearUrl)
        <div class="col-auto">
            <a href="{{ $clearUrl }}" class="btn btn-outline-secondary">
                {{ $clearLabel }}
            </a>
        </div>
    @endif
</form>
