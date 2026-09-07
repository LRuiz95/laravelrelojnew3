@props(['icon', 'label', 'value', 'color'])

<div class="kpi-card card h-100 border-0 shadow-sm" style="--bs-card-border-color: var(--bs-{{ $color }}-subtle);">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: var(--bs-{{ $color }}-subtle);">
                        <i class="{{ $icon }} fs-4 text-{{ $color }}"></i>
                    </div>
                    <h6 class="mb-0 text-muted fw-semibold">{{ $label }}</h6>
                </div>
                <div class="kpi-value fs-2 fw-bold text-{{ $color }}">{{ $value }}</div>
            </div>
            {{ $slot ?? '' }}
        </div>
    </div>
</div>