{{-- Sparkline SVG minimalista — recibe: $points (array), $color (CSS var), $width, $height, $label --}}
@php
    $points = $points ?? [];
    $w = $width ?? 96;
    $h = $height ?? 30;
    $pad = 2;
    $color = $color ?? 'var(--primary)';
    $max = max(1, ...$points);
    $n = count($points);
    $stepX = $n > 1 ? ($w - $pad * 2) / ($n - 1) : 0;
    $line = [];
    foreach ($points as $i => $v) {
        $x = $n > 1 ? round($pad + $i * $stepX, 1) : round($w / 2, 1);
        $y = round($pad + ($h - $pad * 2) * (1 - ($v / $max)), 1);
        $line[] = "$x,$y";
    }
    $area = $line ? implode(' ', [...$line, "$w,$h", "0,$h", $line[array_key_first($line)]]) : '';
    $uid = 'sl' . \Illuminate\Support\Str::random(6);
@endphp
<svg class="sparkline-svg" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $w }} {{ $h }}"
     aria-hidden="true" @isset($label) role="img" aria-label="{{ $label }}" @endisset>
    @if ($area)
        <defs>
            <linearGradient id="{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $color }}"/>
                <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <polygon class="sl-fill" style="fill:url(#{{ $uid }})" points="{{ $area }}"/>
        <polyline class="sl-line" style="stroke:{{ $color }}" points="{{ implode(' ', $line) }}"/>
    @endif
</svg>