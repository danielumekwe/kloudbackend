@extends('layouts.app')
@section('title', 'My Dedicated Servers')
@section('breadcrumb', 'My Dedicated Servers')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My Dedicated Servers</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Your bare-metal servers, powered by InterServer</p>
    </div>
    <a href="{{ route('dedicated.catalog') }}" class="btn btn-primary text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Order a Dedicated Server
    </a>
</div>

@if($instances->isEmpty())
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No Dedicated Servers yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Browse InterServer's live inventory to order your first bare-metal server.</p>
        <a href="{{ route('dedicated.catalog') }}" class="btn btn-primary">Browse Dedicated Servers</a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($instances as $item)
        @php
            $order = $item['order'];
            $live  = $item['live'];
            $badgeClass = match($order->status) {
                'provisioned'     => 'badge-active',
                'paid', 'provisioning' => 'badge-pending',
                'pending_payment' => 'badge-unpaid',
                'failed'          => 'badge-suspended',
                default           => 'badge-cancelled',
            };
            $statusLabel = match($order->status) {
                'provisioned'     => $live['serviceInfo']['server_status'] ?? 'Active',
                'paid', 'provisioning' => 'Provisioning…',
                'pending_payment' => 'Awaiting Payment',
                'failed'          => 'Failed',
                default           => ucfirst($order->status),
            };
            $cpuLabel = is_array($order->config['listing']['cpu'] ?? null) ? ($order->config['listing']['cpu'][0] ?? 'Dedicated Server') : 'Dedicated Server';
        @endphp
        <a href="{{ route('dedicated.show', $order->id) }}"
           class="card hover:shadow-md hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all duration-200 group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-0.5">
                {{ $order->config['hostname'] ?? 'Dedicated Server #' . $order->id }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ $cpuLabel }}</p>

            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Memory</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->config['listing']['memory'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Location</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->config['listing']['location'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Price</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">${{ number_format($order->price, 2) }}/mo</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500">Ordered</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->created_at->format('M j, Y') }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-center justify-between">
                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium group-hover:underline">Manage Server →</span>
            </div>
        </a>
        @endforeach
    </div>
@endif
@endsection
