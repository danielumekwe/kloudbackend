@extends('layouts.admin')
@section('title', 'Ticket ' . ($ticket['code'] ?? $ticket['tid'] ?? $ticket['ticketid'] ?? ''))
@section('breadcrumb')
    <a href="{{ route('admin.tickets.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Support Tickets</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Ticket {{ $ticket['code'] ?? $ticket['tid'] ?? $ticket['ticketid'] ?? '' }}</span>
@endsection

@section('content')
@php
    $status     = $ticket['status'] ?? 'Open';
    $st         = strtolower($status);
    $badgeClass = match(true) {
        $st === 'open'              => 'badge-open',
        str_contains($st, 'answer') => 'badge-answered',
        str_contains($st, 'reply')  => 'badge-answered',
        $st === 'closed'            => 'badge-closed',
        default                     => 'badge-open',
    };
    $isClosed = $st === 'closed';

    $replies = [];
    if (!empty($ticket['replies']['reply'])) {
        $replies = isset($ticket['replies']['reply'][0])
            ? $ticket['replies']['reply']
            : [$ticket['replies']['reply']];
    }
@endphp

{{-- Header --}}
<div class="flex items-start justify-between mb-6 flex-wrap gap-4">
    <div>
        <div class="flex items-center gap-3 mb-1 flex-wrap">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                {{ $ticket['subject'] ?? '(No subject)' }}
            </h1>
            <span class="badge {{ $badgeClass }}">{{ $status }}</span>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Ticket {{ $ticket['code'] ?? $ticket['tid'] ?? $ticket['ticketid'] ?? '' }}
            &middot; {{ $ticket['deptname'] ?? $ticket['department'] ?? '' }}
            @if(!empty($ticket['priority']))
                &middot; Priority: {{ $ticket['priority'] }}
            @endif
            &middot; Opened {{ $ticket['date'] ?? '' }}
        </p>
        @if(!empty($ticket['service_label']))
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Re: {{ $ticket['service_label'] }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if(!$isClosed)
        <form method="POST" action="{{ route('admin.tickets.close', $ticket['ticketid']) }}"
              x-data="{ confirm: false }"
              @submit.prevent="confirm ? $el.submit() : (confirm = true)">
            @csrf
            <button type="submit"
                    x-text="confirm ? 'Click again to confirm' : 'Close Ticket'"
                    class="btn btn-secondary text-sm text-slate-600 dark:text-slate-400
                           hover:border-red-300 dark:hover:border-red-500/30 hover:text-red-600 dark:hover:text-red-400">
            </button>
        </form>
        @endif
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary text-sm">← Back</a>
    </div>
</div>

{{-- Thread --}}
<div class="space-y-4 mb-6">

    {{-- Original message --}}
    <div class="card">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ strtoupper(substr($ticket['client_name'] ?? 'C', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $ticket['client_name'] ?? 'Client' }}
                        </span>
                        @if(!empty($ticket['client_email']))
                            <span class="ml-2 text-xs text-slate-400 dark:text-slate-500">{{ $ticket['client_email'] }}</span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $ticket['date'] ?? '' }}</span>
                </div>
                <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $ticket['message'] ?? '' }}</div>
            </div>
        </div>
    </div>

    {{-- Replies --}}
    @foreach($replies as $reply)
    @php
        $isStaff    = ($reply['type'] ?? '') === 'reply' || ($reply['admin'] ?? '') !== '';
        $authorName = $isStaff ? ($reply['name'] ?? 'Support Team') : ($ticket['client_name'] ?? 'Client');
        $initials   = strtoupper(substr($authorName, 0, 1));
        $bgColor    = $isStaff ? 'bg-purple-600' : 'bg-blue-600';
    @endphp
    <div class="card {{ $isStaff ? 'border-purple-200/60 dark:border-purple-500/20 bg-purple-50/40 dark:bg-purple-500/5' : '' }}">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-full {{ $bgColor }} flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $authorName }}</span>
                        @if($isStaff)
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-400">Staff</span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $reply['date'] ?? '' }}</span>
                </div>
                <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $reply['message'] ?? '' }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Reply form --}}
@if(!$isClosed)
<div class="card">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Reply to client</h3>

    @if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-sm text-red-700 dark:text-red-400">
        {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.tickets.reply', $ticket['ticketid']) }}"
          x-data="{ loading: false, charCount: 0 }"
          @submit="loading = true">
        @csrf
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="message" class="form-label !mb-0">Message</label>
                    <span class="text-xs text-slate-400" x-text="charCount + ' chars'"></span>
                </div>
                <textarea id="message" name="message" rows="6" required
                          @input="charCount = $event.target.value.length"
                          class="form-input resize-y"
                          placeholder="Type your reply…"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="loading" class="btn btn-primary">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Reply
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="spinner"></div>
                        Sending…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
@else
<div class="card text-center py-8 border-dashed">
    <p class="text-sm text-slate-500 dark:text-slate-400">This ticket is closed.</p>
</div>
@endif
@endsection
