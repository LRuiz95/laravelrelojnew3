@props(['headers', 'rows', 'actions', 'emptyMessage' => 'No hay datos', 'striped' => true, 'hover' => true, 'bordered' => true, 'pagination' => null, 'perPageOptions' => [10, 25, 50, 100]])

@php
    $hasHiddenHeaders = collect($headers)->contains(fn ($header) => isset($header['hideOn']));
    $extraColumnCount = ($actions || $hasHiddenHeaders) ? 1 : 0;
    $currentPerPage = method_exists($rows, 'perPage') ? (int) $rows->perPage() : 25;
@endphp

<div class="card data-table-shell">
    <div class="card-body p-0">
        @if ($rows->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-table fs-1 mb-2"></i>
                <p>{{ $emptyMessage }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table {{ $striped ? 'table-striped' : '' }} {{ $hover ? 'table-hover' : '' }} {{ $bordered ? 'table-bordered' : '' }} align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach ($headers as $header)
                                @php
                                    $headerLabel = $header['label'] ?? $header;
                                    $headerClass = isset($header['class']) ? $header['class'] : '';
                                    $headerWidth = isset($header['width']) ? 'width: ' . $header['width'] . ';' : '';
                                    if (isset($header['hideOn'])) {
                                        $headerClass = trim($headerClass . ' d-none d-' . $header['hideOn'] . '-table-cell');
                                    }
                                @endphp
                                <th style="{{ $headerWidth }}" class="{{ $headerClass }}">
                                    {{ $headerLabel }}
                                </th>
                            @endforeach
                            @if ($actions)
                                <th class="text-end" style="width: {{ $hasHiddenHeaders ? '150px' : '120px' }};">Acciones</th>
                            @elseif ($hasHiddenHeaders)
                                <th class="text-end" style="width: 92px;">Más</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $rowId = 'datatable-row-' . $loop->index;
                            @endphp
                            <tr>
                                @foreach ($headers as $header)
                                    @php
                                        $cellClass = isset($header['class']) ? $header['class'] : '';
                                        if (isset($header['hideOn'])) {
                                            $cellClass = trim($cellClass . ' d-none d-' . $header['hideOn'] . '-table-cell');
                                        }
                                    @endphp
                                    <td class="{{ $cellClass }}">
                                        @if (isset($header['render']))
                                            {!! $header['render']($row) !!}
                                        @elseif (isset($header['field']))
                                            {{ $row[$header['field']] ?? '' }}
                                        @else
                                            {{ $row->{$header} ?? '' }}
                                        @endif
                                    </td>
                                @endforeach
                                @if ($actions)
                                    <td class="text-end">
                                        @if ($hasHiddenHeaders)
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm me-2"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $rowId }}"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $rowId }}"
                                                    title="Ver más">
                                                <i class="bi bi-list"></i>
                                            </button>
                                        @endif
                                        <div class="btn-group btn-group-sm">
                                            @foreach ($actions as $action)
                                                @php
                                                    $actionType = $action['type'] ?? 'link';
                                                    $actionStyle = is_callable($action['style'] ?? null) ? ($action['style']($row)) : ($action['style'] ?? 'primary');
                                                    $actionTitle = is_callable($action['title'] ?? null) ? ($action['title']($row)) : ($action['title'] ?? '');
                                                    $actionIcon = is_callable($action['icon'] ?? null) ? ($action['icon']($row)) : ($action['icon'] ?? 'bi bi-eye');
                                                    $actionUrl = is_callable($action['url'] ?? null) ? ($action['url']($row)) : ($action['url'] ?? '#');
                                                    $actionOnclick = is_callable($action['onclick'] ?? null) ? ($action['onclick']($row)) : ($action['onclick'] ?? '');
                                                @endphp
                                                @if ($actionType === 'link')
                                                    <a href="{{ $actionUrl }}" class="btn btn-outline-{{ $actionStyle }} btn-sm" title="{{ $actionTitle }}">
                                                        <i class="{{ $actionIcon }}"></i>
                                                    </a>
                                                @elseif ($actionType === 'button')
                                                    <button type="button" class="btn btn-outline-{{ $actionStyle }} btn-sm" onclick="{{ $actionOnclick }}" title="{{ $actionTitle }}">
                                                        <i class="{{ $actionIcon }}"></i>
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                @elseif ($hasHiddenHeaders)
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $rowId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $rowId }}"
                                                title="Ver más">
                                            <i class="bi bi-list"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>

                            @if ($hasHiddenHeaders)
                                <tr class="collapse" id="{{ $rowId }}">
                                    <td colspan="{{ count($headers) + $extraColumnCount }}" class="bg-body-tertiary">
                                        <div class="row g-2 py-2">
                                            @foreach ($headers as $header)
                                                @if (isset($header['hideOn']))
                                                    <div class="col-6 col-md-4">
                                                        <div class="small text-muted">{{ $header['label'] ?? $header }}</div>
                                                        <div class="small fw-semibold">
                                                            @if (isset($header['render']))
                                                                {!! $header['render']($row) !!}
                                                            @elseif (isset($header['field']))
                                                                {{ $row[$header['field']] ?? '' }}
                                                            @else
                                                                {{ $row->{$header} ?? '' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($pagination)
        <div class="data-table-footer">
            <div class="data-table-summary">
                @php
                    $fromItem = $rows->total() > 0 ? $rows->firstItem() : 0;
                    $toItem = $rows->total() > 0 ? $rows->lastItem() : 0;
                @endphp
                Mostrando {{ $fromItem }}–{{ $toItem }} de {{ $rows->total() }} registros
            </div>

            <div class="data-table-controls">
                <span class="small text-tertiary-token">Filas</span>
                <div class="data-table-per-page">
                    @foreach ($perPageOptions as $option)
                        @php
                            $query = request()->query();
                            unset($query['page']);
                            $query['per_page'] = $option;
                            $url = request()->url() . '?' . http_build_query($query);
                        @endphp
                        <a href="{{ $url }}" class="data-table-per-page-link {{ $currentPerPage == $option ? 'active' : '' }}">{{ $option }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-footer border-0 bg-transparent pt-0 pagination-footer">
            {{ $pagination->links() }}
        </div>
    @endif
</div>