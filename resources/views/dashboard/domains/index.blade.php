@extends('layouts.app')
@section('title', 'My Domains')
@section('breadcrumb', 'My Domains')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My Domains</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Your domain registrations, powered by InterServer</p>
    </div>
</div>

@if($orders->isEmpty())
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No domains yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Search for a domain from the sidebar to register one.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($orders as $order)
        @php
            $badgeClass = match($order->status) {
                'provisioned'     => 'badge-active',
                'paid'            => 'badge-pending',
                'pending_payment' => 'badge-unpaid',
                'failed'          => 'badge-suspended',
                'cancelled'       => 'badge-cancelled',
                default           => 'badge-cancelled',
            };
            $statusLabel = match($order->status) {
                'provisioned'     => 'Active',
                'paid'            => 'Registering…',
                'pending_payment' => 'Awaiting Payment',
                'failed'          => 'Failed',
                'cancelled'       => 'Cancelled',
                default           => $order->status,
            };
        @endphp
        <a href="{{ route('domains.show', $order->id) }}"
           class="card hover:shadow-md hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all duration-200 group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"/>
                    </svg>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-0.5">
                {{ $order->domain_name }}.{{ $order->tld }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ ucfirst($order->order_type) }} · {{ $order->registration_years }} yr</p>

            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Whois Privacy</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->whois_privacy ? 'Enabled' : 'Disabled' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Price</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">${{ number_format($order->price, 2) }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Ordered</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->created_at->format('M j, Y') }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-center justify-between">
                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium group-hover:underline">Manage Domain →</span>
            </div>
        </a>
        @endforeach
    </div>
@endif
@endsection
