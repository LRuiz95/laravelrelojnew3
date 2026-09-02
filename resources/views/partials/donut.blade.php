{{-- Donut chart SVG — recibe: $segments [{label, value, color}], $total --}}
@php
    $segments = $segments ?? [];
    $total = $total ?? array_sum(array_column($segments, 'value'));
    $r = 70;
    $C = 2 * M_PI * $r;
    $css = [
        'blue'     => 'var(--cat-blue)',
        'orange'   => 'var(--cat-orange)',
        'purple'   => 'var(--cat-purple)',
        'pink'     => 'var(--cat-pink)',
        'red'      => 'var(--cat-red)',
        'green'    => 'var(--cat-green)',
        'amber'    => 'var(--cat-amber)',
        'lavender' => 'var(--cat-lavender)',
        'gray'     => 'var(--cat-gray)',
    ];
    $offset = 0;
@endphp
<div class="donut-wrap">
    <div class="donut-box">
        <svg viewBox="0 0 180 180">
            <circle cx="90" cy="90" r="{{ $r }}" fill="none"
                    style="stroke:var(--border)" stroke-width="22"/>
            @foreach ($segments as $i => $seg)
                @php
                    $len = $total > 0 ? ($seg['value'] / $total) * $C : 0;
                    $color = $css[$seg['color']] ?? $seg['color'];
                @endphp
                <circle class="donut-seg" cx="90" cy="90" r="{{ $r }}" fill="none"
                        stroke="{{ $color }}" stroke-width="22"
                        stroke-dasharray="{{ round($len, 2) }} {{ round($C - $len, 2) }}"
                        stroke-dashoffset="{{ round(-$offset, 2) }}"
                        data-percent="{{ $total > 0 ? round(($seg['value'] / $total) * 100, 1) : 0 }}">
                    <title>{{ $seg['label'] }} · {{ $seg['value'] }} ({{ $total > 0 ? round(($seg['value'] / $total) * 100, 1) : 0 }}%)</title>
                </circle>
                @php $offset += $len; @endphp
            @endforeach
        </svg>
        <div class="donut-hole">
            <div class="dh-value">{{ $total }}</div>
            <div class="dh-label">Marcados</div>
        </div>
    </div>
    <div class="donut-legend">
        @forelse ($segments as $seg)
            <div class="donut-legend-item">
                <span class="dl-dot" style="background:{{ $css[$seg['color']] ?? $seg['color'] }}"></span>
                <span class="dl-label">{{ $seg['label'] }}</span>
                <span class="dl-value">{{ $seg['value'] }}
                    <span class="text-tertiary-token" style="font-size:11px">({{ $total > 0 ? round(($seg['value'] / $total) * 100) : 0 }}%)</span>
                </span>
            </div>
        @empty
            <div class="text-tertiary-token" style="font-size:13px">{{ $emptyText ?? 'Sin registros todavía.' }}</div>
        @endforelse
    </div>
</div>