@props(['headers', 'rows', 'actions', 'emptyMessage' => 'No hay datos', 'striped' => true, 'hover' => true, 'bordered' => true, 'pagination' => null])

<div class="card">
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
                                <th {{ isset($header['width']) ? 'style="width: ' . $header['width'] . '"' : '' }} {{ isset($header['class']) ? 'class="' . $header['class'] . '"' : '' }}>
                                    {{ $header['label'] ?? $header }}
                                </th>
                            @endforeach
                            @if ($actions)
                                <th class="text-end" style="width: 120px;">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($headers as $header)
                                    <td {{ isset($header['class']) ? 'class="' . $header['class'] . '"' : '' }}>
                                        @if (isset($header['render']))
                                            {{ $header['render']($row) }}
                                        @elseif (isset($header['field']))
                                            {{ $row[$header['field']] ?? '' }}
                                        @else
                                            {{ $row->{$header} ?? '' }}
                                        @endif
                                    </td>
                                @endforeach
                                @if ($actions)
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @foreach ($actions as $action)
                                                @if ($action['type'] === 'link')
                                                    <a href="{{ $action['url']($row) }}" class="btn btn-outline-{{ $action['style'] ?? 'primary' }} btn-sm" title="{{ $action['title'] }}">
                                                        <i class="{{ $action['icon'] }}"></i>
                                                    </a>
                                                @elseif ($action['type'] === 'button')
                                                    <button type="button" class="btn btn-outline-{{ $action['style'] ?? 'primary' }} btn-sm" onclick="{{ $action['onclick']($row) }}" title="{{ $action['title'] }}">
                                                        <i class="{{ $action['icon'] }}"></i>
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($pagination)
        <div class="card-footer border-0 bg-transparent pt-0">
            {{ $pagination->links() }}
        </div>
    @endif
</div>