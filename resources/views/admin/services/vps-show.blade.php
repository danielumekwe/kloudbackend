@extends('layouts.admin')
@section('title', 'VPS #' . $order->id)
@section('breadcrumb')
    <a href="{{ route('admin.services.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Services</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">VPS #{{ $order->id }}</span>
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
    $badgeClass = match($order->status) {
        'provisioned'  => 'badge-active',
        'suspended'    => 'badge-answered',
        'failed'       => 'badge-suspended',
        'cancelled'    => 'badge-closed',
        default        => 'badge-open',
    };
    $hostname = $order->config['hostname'] ?? 'VPS #' . $order->id;
    $ip       = $liveData['ip'] ?? $liveData['main_ip'] ?? null;
@endphp

{{-- Header --}}
<div class="card mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $hostname }}</h1>
                <span class="badge {{ $badgeClass }}">{{ $order->status }}</span>
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
            <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-mono">{{ $order->interserver_vps_id ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">IP Address</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-mono">{{ $ip ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Plan</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->config['plan_name'] ?? $order->config['platform'] ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Price</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">${{ number_format((float)$order->price, 2) }}/{{ $order->billing_cycle }}</p>
        </div>
        @if(!empty($liveData['os']))
        <div>
            <p class="text-xs text-slate-400">OS</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $liveData['os'] }}</p>
        </div>
        @endif
        @if(!empty($liveData['hdd']))
        <div>
            <p class="text-xs text-slate-400">Disk</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $liveData['hdd'] }} GB</p>
        </div>
        @endif
        @if(!empty($liveData['ram']))
        <div>
            <p class="text-xs text-slate-400">RAM</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $liveData['ram'] }} MB</p>
        </div>
        @endif
        <div>
            <p class="text-xs text-slate-400">Ordered</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $order->created_at->format('M j, Y') }}</p>
        </div>
    </div>
</div>

@if($order->interserver_vps_id)
{{-- Power controls --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Power &amp; Status</h3>
    <div class="flex flex-wrap gap-3">
        @if($order->status === 'provisioned')
        <form method="POST" action="{{ route('admin.services.vps.restart', $order) }}">@csrf
            <button type="submit" class="btn btn-secondary text-sm">Restart</button>
        </form>
        <form method="POST" action="{{ route('admin.services.vps.suspend', $order) }}"
              x-data="{ confirm: false }" @submit.prevent="confirm ? $el.submit() : (confirm = true)">@csrf
            <button type="submit" x-text="confirm ? 'Click again to confirm' : 'Suspend'"
                    class="btn btn-danger text-sm"></button>
        </form>
        @elseif($order->status === 'suspended')
        <form method="POST" action="{{ route('admin.services.vps.unsuspend', $order) }}">@csrf
            <button type="submit" class="btn btn-success text-sm">Unsuspend (Start)</button>
        </form>
        @elseif(in_array($order->status, ['failed', 'pending_payment']))
        <form method="POST" action="{{ route('admin.services.vps.start', $order) }}">@csrf
            <button type="submit" class="btn btn-success text-sm">Start</button>
        </form>
        @endif

        {{-- Console access --}}
        <form method="POST" action="{{ route('admin.services.vps.console', $order) }}">@csrf
            <button type="submit" class="btn btn-secondary text-sm">Open Console (No Password)</button>
        </form>

        {{-- Cancel --}}
        @if($order->status !== 'cancelled')
        <form method="POST" action="{{ route('admin.services.vps.cancel', $order) }}"
              x-data="{ confirm: false }" @submit.prevent="confirm ? $el.submit() : (confirm = true)">@csrf
            <button type="submit" x-text="confirm ? 'Click again — this is permanent' : 'Cancel / Terminate'"
                    class="btn btn-danger text-sm ml-auto"></button>
        </form>
        @endif
    </div>
</div>

{{-- Change root password --}}
<div class="card mb-6" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between text-left">
        <h3 class="font-semibold text-slate-900 dark:text-white">Change Root Password</h3>
        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" x-transition class="mt-4">
        <form method="POST" action="{{ route('admin.services.vps.change-password', $order) }}" class="flex gap-3">
            @csrf
            <input type="password" name="password" placeholder="New root password (min 8 chars)"
                   class="form-input flex-1" required minlength="8" autocomplete="new-password">
            <button type="submit" class="btn btn-primary text-sm flex-shrink-0">Set Password</button>
        </form>
        <p class="text-xs text-slate-400 mt-2">This changes the root password directly on the InterServer VPS via API.</p>
    </div>
</div>

{{-- Reinstall OS --}}
@if(count($templates) > 0)
<div class="card mb-6" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between text-left">
        <h3 class="font-semibold text-slate-900 dark:text-white">Reinstall OS</h3>
        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" x-transition class="mt-4">
        <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 mb-4">
            <p class="text-sm text-amber-700 dark:text-amber-400">⚠️ This will wipe all data on the VPS and reinstall the OS from scratch.</p>
        </div>
        <form method="POST" action="{{ route('admin.services.vps.reinstall-os', $order) }}" class="space-y-3">
            @csrf
            <div>
                <label class="form-label">Operating System</label>
                <select name="template" class="form-input" required>
                    <option value="">Select OS template...</option>
                    @foreach($templates as $tpl)
                    <option value="{{ $tpl['id'] ?? $tpl }}">{{ $tpl['name'] ?? $tpl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">New Root Password</label>
                <input type="password" name="localPassword" class="form-input" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-danger text-sm">Reinstall OS</button>
        </form>
    </div>
</div>
@endif
@endif

@endsection
