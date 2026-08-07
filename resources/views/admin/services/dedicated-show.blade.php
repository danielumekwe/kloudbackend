@extends('layouts.admin')
@section('title', 'Dedicated Server #' . $order->id)
@section('breadcrumb')
    <a href="{{ route('admin.services.index', ['type' => 'dedicated']) }}" class="hover:text-slate-700 dark:hover:text-slate-200">Services</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Dedicated Server #{{ $order->id }}</span>
@endsection

@section('content')

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
</div>
@endif

@php
    $hostname = $order->config['hostname'] ?? 'Dedicated Server #' . $order->id;
    $ip       = $instance->ipv4 ?? $liveData['ip'] ?? $liveData['main_ip'] ?? null;
@endphp

{{-- Header --}}
<div class="card mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $hostname }}</h1>
                <span class="badge px-2.5 py-1 rounded-full text-xs font-medium {{ \App\Support\OrderStatusBadge::classes($order->status) }}">
                    {{ \App\Support\OrderStatusBadge::label($order->status) }}
                </span>
            </div>
            @if($order->client)
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                <a href="{{ route('admin.clients.show', $order->client) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                    {{ $order->client->firstname }} {{ $order->client->lastname }}
                </a>
                &middot; {{ $order->client->email }}
            </p>
            @endif
        </div>
        @if($order->invoice_id)
        <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            Invoice #{{ $order->invoice_id }} →
        </a>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06] text-sm">
        <div>
            <p class="text-xs text-slate-400">InterServer ID</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-mono">{{ $order->interserver_server_id ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">IP Address</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-mono">{{ $ip ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Price</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">${{ number_format((float)$order->price, 2) }}/{{ $order->billing_cycle }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Ordered</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->created_at->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Next Renewal</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $instance?->renewal_at?->format('M j, Y') ?? '—' }}</p>
        </div>
        @if($instance?->decryptedRootPassword())
        <div x-data="{ show: false }">
            <p class="text-xs text-slate-400">Root Password</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-mono flex items-center gap-2">
                <span x-show="!show">••••••••••••</span>
                <span x-show="show" x-cloak>{{ $instance->decryptedRootPassword() }}</span>
                <button type="button" @click="show = !show" class="text-xs text-blue-600 dark:text-blue-400 hover:underline" x-text="show ? 'Hide' : 'Reveal'"></button>
            </p>
        </div>
        @endif
    </div>
</div>

<div class="p-3 rounded-lg bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.06] mb-6">
    <p class="text-sm text-slate-500 dark:text-slate-400">
        InterServer's API does not currently expose power/reinstall/rescue controls for bare-metal Dedicated Servers
        (only ordering, cancellation and invoice history) — those controls are disabled below until that changes.
    </p>
</div>

{{-- Power / System (disabled — no InterServer lifecycle endpoints for dedicated) --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Power &amp; System</h3>
    <div class="flex flex-wrap gap-3">
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Start</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Stop</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Restart</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Hard Reboot</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Reset Root Password</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Reinstall OS</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Boot Rescue Mode</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Open Console</button>
    </div>
</div>

{{-- Network (disabled — no InterServer endpoints for dedicated) --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Network</h3>
    <div class="flex flex-wrap gap-3">
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Manage Reverse DNS</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Additional IPs</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">Firewall Status</button>
        <button type="button" disabled title="Not available via InterServer API" class="btn btn-secondary text-sm opacity-50 cursor-not-allowed">DDoS Protection</button>
    </div>
</div>

{{-- Billing (real — local status + InterServer cancel) --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Billing</h3>
    <div class="flex flex-wrap gap-3">
        @if($order->status === 'provisioned')
        <form method="POST" action="{{ route('admin.services.dedicated.suspend', $order) }}"
              x-data="{ confirm: false }" @submit.prevent="confirm ? $el.submit() : (confirm = true)">@csrf
            <button type="submit" x-text="confirm ? 'Click again to confirm' : 'Suspend'" class="btn btn-danger text-sm"></button>
        </form>
        @elseif($order->status === 'suspended')
        <form method="POST" action="{{ route('admin.services.dedicated.unsuspend', $order) }}">@csrf
            <button type="submit" class="btn btn-success text-sm">Unsuspend</button>
        </form>
        @endif

        <form method="POST" action="{{ route('admin.services.renew', ['orderType' => 'dedicated', 'orderId' => $order->id]) }}">@csrf
            <button type="submit" class="btn btn-secondary text-sm">Renew</button>
        </form>

        @if($order->status !== 'cancelled')
        <form method="POST" action="{{ route('admin.services.dedicated.cancel', $order) }}"
              x-data="{ confirm: false }" @submit.prevent="confirm ? $el.submit() : (confirm = true)">@csrf
            <button type="submit" x-text="confirm ? 'Click again — this is permanent' : 'Cancel / Terminate'" class="btn btn-danger text-sm ml-auto"></button>
        </form>
        @endif
    </div>
</div>

{{-- Activity --}}
<div class="card">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Activity</h3>
    @include('partials.activity-timeline', ['activity' => $activity])
</div>

@endsection
