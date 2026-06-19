@extends('layouts.app')
@section('title', 'My SSL Certificates')
@section('breadcrumb', 'My SSL Certificates')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My SSL Certificates</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Your SSL/TLS certificates, powered by InterServer</p>
    </div>
</div>

@if($instances->isEmpty())
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No SSL certificates yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Order a certificate from the sidebar to secure your domain.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($instances as $item)
        @php
            $order = $item['order'];
            $live  = $item['live'];
            $badgeClass = match($order->status) {
                'provisioned'     => 'badge-active',
                'paid'            => 'badge-pending',
                'pending_payment' => 'badge-unpaid',
                'failed'          => 'badge-suspended',
                'cancelled'       => 'badge-cancelled',
                default           => 'badge-cancelled',
            };
            $statusLabel = match($order->status) {
                'provisioned'     => $live['status'] ?? 'Active',
                'paid'            => 'Issuing…',
                'pending_payment' => 'Awaiting Payment',
                'failed'          => 'Failed',
                'cancelled'       => 'Cancelled',
                default           => $order->status,
            };
        @endphp
        <a href="{{ route('ssl.show', $order->id) }}"
           class="card hover:shadow-md hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all duration-200 group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                    </svg>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-0.5">
                {{ $order->config['hostname'] ?? 'Certificate #' . $order->id }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">SSL Certificate</p>

            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Approver Email</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5 truncate">{{ $order->config['approver_email'] ?? '—' }}</p>
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
                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium group-hover:underline">Manage Certificate →</span>
            </div>
        </a>
        @endforeach
    </div>
@endif
@endsection
