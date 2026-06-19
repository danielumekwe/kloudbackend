@extends('layouts.app')
@section('title', 'My Servers')
@section('breadcrumb', 'My Servers')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My Servers</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage all your cloud infrastructure</p>
    </div>
    <a href="{{ route('servers.order') }}" class="btn btn-primary gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Order a Server
    </a>
</div>

@if(empty($services))
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No servers yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">You don't have any active services in your account.</p>
        <a href="{{ route('servers.order') }}" class="btn btn-primary">
            Order a Server
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($services as $service)
        @php
            $status = strtolower($service['status'] ?? '');
            $badgeClass = match($status) {
                'active'    => 'badge-active',
                'suspended' => 'badge-suspended',
                'pending'   => 'badge-pending',
                default     => 'badge-cancelled',
            };
        @endphp
        <a href="{{ route('servers.show', $service['id']) }}"
           class="card hover:shadow-md hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all duration-200 group">

            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $service['status'] ?? 'Unknown' }}</span>
            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-0.5">
                {{ $service['name'] ?? 'Service #' . $service['id'] }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                {{ $service['domain'] ?? $service['groupname'] ?? '' }}
            </p>

            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Billing cycle</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $service['billingcycle'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Next due</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $service['nextduedate'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Price</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">
                        ${{ number_format((float)($service['recurringamount'] ?? 0), 2) }}/mo
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Since</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $service['regdate'] ?? '—' }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-center justify-between">
                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium group-hover:underline">Manage server →</span>
            </div>
        </a>
        @endforeach
    </div>
@endif
@endsection
