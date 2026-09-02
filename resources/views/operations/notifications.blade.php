@extends('layouts.admin')

@section('title', 'Notificaciones')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="h5 mb-1">Centro de notificaciones</h2>
        <p class="text-muted mb-0">Avisos importantes sobre la red, sincronizaciones y asistencias.</p>
    </div>
    <a href="{{ route('operations.queue') }}" class="btn btn-outline-secondary"><i class="bi bi-list-task me-1"></i> Ver cola</a>
</div>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <a href="{{ $notification['url'] ?? '#' }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3">
                <span class="avatar is-sm">{{ $notification['initials'] }}</span>
                <span class="flex-grow-1"><strong class="d-block">{{ $notification['title'] }}</strong><span class="text-muted small d-block mt-1">{{ $notification['desc'] }}</span><span class="text-tertiary-token small">{{ $notification['category'] }} · {{ $notification['time'] }}</span></span>
                @if (!$notification['read'])<span class="notify-unread-dot mt-2"></span>@endif
            </a>
        @empty
            <div class="notify-empty"><i class="bi bi-bell"></i><strong>No hay notificaciones</strong><span class="notify-empty-sub">Estás al día.</span></div>
        @endforelse
    </div>
</div>
@endsection