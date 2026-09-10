@props([
    'ciclo' => null,
    'ciclos' => collect(),
    'showBadge' => true,
    'totales' => null,
])

@php
    $route = request()->url();
    $params = request()->except(['ciclo_principal']);
@endphp

<form method="GET" action="{{ $route }}" class="d-flex align-items-center gap-2 flex-wrap">
    @foreach ($params as $key => $value)
        @if (is_array($value))
            @foreach ($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="input-group input-group-sm" style="max-width: 280px;">
        <span class="input-group-text">
            <i class="bi bi-calendar3"></i>
        </span>
        <select name="ciclo_principal" class="form-select" required aria-label="Seleccionar ciclo escolar">
            @forelse ($ciclos as $c)
                <option value="{{ $c->label }}" {{ $ciclo && $ciclo->inicial == $c->inicial && $ciclo->final == $c->final && $ciclo->periodo == $c->periodo ? 'selected' : '' }}>
                    {{ $c->label }}
                    @if ($c->activo)
                        ★
                    @endif
                    @if ($c->fecha_inicial && $c->fecha_final)
                        ({{ $c->fecha_inicial->format('d/m') }}–{{ $c->fecha_final->format('d/m/Y') }})
                    @endif
                </option>
            @empty
                <option value="" disabled selected>Sin ciclos disponibles</option>
            @endforelse
        </select>
    </div>

    @if ($showBadge && $ciclo)
        <span class="badge {{ $ciclo->activo ? 'bg-success' : 'bg-secondary' }} rounded-pill">
            {{ $ciclo->activo ? 'Activo' : 'Inactivo' }}
        </span>
    @endif

    @if ($totales !== null)
        <small class="text-muted">
            de {{ number_format($totales) }} en total
        </small>
    @endif

    @if ($ciclos->isNotEmpty())
        <button type="submit" class="btn btn-sm btn-primary" title="Aplicar ciclo seleccionado">
            <i class="bi bi-arrow-repeat"></i>
        </button>
    @endif
</form>
