{{-- Empty state — recibe: $icon, $title, $desc, $cta (['label','url']), $ctaLink (bool) --}}
<div class="empty-state">
    <i class="bi {{ $icon ?? 'bi-folder2-open' }}"></i>
    <div class="es-title">{{ $title ?? 'Sin datos' }}</div>
    @isset($desc)
        <div class="es-desc">{{ $desc }}</div>
    @endisset
    @if (!empty($cta))
        @if (!empty($ctaLink))
            <a href="{{ $cta['url'] }}" class="btn btn-outline-secondary">{{ $cta['label'] }}</a>
        @else
            <button type="button" class="btn btn-outline-secondary" {{ $cta['attrs'] ?? '' }}>{{ $cta['label'] }}</button>
        @endif
    @endif
</div>