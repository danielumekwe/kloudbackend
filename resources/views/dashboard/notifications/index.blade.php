@extends('layouts.app')
@section('title', 'Notifications')
@section('breadcrumb', 'Notifications')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn btn-secondary text-sm">Mark all read</button>
    </form>
</div>

@if($notifications->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No notifications</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">You're all caught up.</p>
</div>
@else
<div class="space-y-2">
    @foreach($notifications as $n)
    <div class="card flex items-start justify-between gap-4 {{ $n->read_at ? '' : 'border-blue-300 dark:border-blue-500/40' }}">
        <div>
            <p class="font-medium text-slate-900 dark:text-white">{{ $n->data['title'] ?? 'Notification' }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $n->data['message'] ?? '' }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $n->created_at->format('M j, Y g:i A') }}</p>
        </div>
        @if(! $n->read_at)
        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
            @csrf
            <button type="submit" class="text-xs text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">Mark read</button>
        </form>
        @endif
    </div>
    @endforeach
</div>
<div class="mt-6">
    {{ $notifications->links() }}
</div>
@endif
@endsection
