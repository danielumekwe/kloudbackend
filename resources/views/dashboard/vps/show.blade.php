@extends('layouts.app')
@section('title', $order->config['hostname'] ?? 'VPS #' . $order->id)
@section('breadcrumb')
    <a href="{{ route('vps.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My VPS</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $order->config['hostname'] ?? 'VPS #' . $order->id }}</span>
@endsection

@section('content')
@php
    // The InterServer API nests the live power state, IP, and hostname under
    // `serviceInfo` (and the physical host node under `serviceMaster`) rather
    // than at the top level — `vps_status` at the top level is the billing/
    // account status ("active"), not the power state.
    $svc = $live['serviceInfo'] ?? [];
    $vpsIp = $svc['vps_ip'] ?? ($live['vps_ip'] ?? '');
    $vpsHostname = $svc['vps_hostname'] ?? ($live['vps_hostname'] ?? '');
    $vpsVzid = $svc['vps_vzid'] ?? ($order->interserver_vps_id ? 'vps' . $order->interserver_vps_id : null);
    $vpsComment = trim($svc['vps_comment'] ?? '');
    $hostNodeName = $live['serviceMaster']['vps_name'] ?? '';
    $packageLabel = $live['package'] ?? (config('vps_pricing.categories.' . $order->category . '.label', $order->category) . ' (' . ($order->config['slices'] ?? '—') . ' Slice' . (($order->config['slices'] ?? 1) == 1 ? '' : 's') . ')');
    $vpsStatus = strtolower($svc['vps_server_status'] ?? ($live['vps_status'] ?? ''));
    $vpsStatus = in_array($vpsStatus, ['stop', 'off'], true) ? 'stopped' : $vpsStatus;
@endphp
<div x-data="vpsManage({{ $order->id }})">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $order->config['hostname'] ?? 'VPS #' . $order->id }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                {{ config('vps_pricing.categories.' . $order->category . '.label', $order->category) }}
                @if($order->status === 'provisioned' && $vpsIp)
                    · {{ $vpsIp }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($order->status === 'provisioned' && $vpsIp)
            <button type="button"
                    onclick="navigator.clipboard.writeText('ssh {{ $instance->root_username ?? 'root' }}@{{ $vpsIp }}{{ ($instance->ssh_port ?? 22) != 22 ? ' -p ' . ($instance->ssh_port ?? 22) : '' }}'); this.dataset.copied=1; this.querySelector('span').textContent='Copied!'; setTimeout(() => this.querySelector('span').textContent='Copy SSH Login', 2000);"
                    class="btn btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Copy SSH Login</span>
            </button>
            @endif
            @unless($order->status === 'provisioned')
            <span class="badge {{ $order->status === 'failed' ? 'badge-suspended' : 'badge-pending' }}">
                {{ str_replace('_', ' ', $order->status) }}
            </span>
            @endunless
        </div>
    </div>

    @if($order->status === 'pending_payment')
        <div class="card mb-6 flex items-start gap-3 bg-yellow-50 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Awaiting payment</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Your VPS will be provisioned automatically as soon as this invoice is paid.</p>
                <a href="{{ route('billing.show', $order->invoice_id) }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">View invoice →</a>
            </div>
        </div>
    @elseif($order->status === 'failed')
        <div class="card mb-6 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Provisioning failed</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $order->failure_reason ?? 'Please contact support.' }}</p>
                <a href="{{ route('support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">Contact support →</a>
            </div>
        </div>
    @endif

    @if($order->status === 'provisioned')

    {{-- Result banner (quick-action results outside a modal) --}}
    <div x-show="message && !modal" x-cloak class="mb-6 text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl p-5 text-white bg-gradient-to-br from-blue-600 to-blue-500 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
            <p class="text-xs font-semibold tracking-wide uppercase text-white/70 mt-4">Package</p>
            <p class="text-base font-bold mt-0.5 truncate">{{ $packageLabel }}</p>
            <div class="mt-3 space-y-1 text-sm text-white/80">
                <p class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg> {{ $vpsVzid ?: ($vpsHostname ?: '—') }}</p>
                <p class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Ordered {{ $order->created_at->format('F j, Y') }}</p>
            </div>
        </div>

        <div class="rounded-2xl p-5 text-white bg-gradient-to-br from-emerald-600 to-emerald-500 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
            <p class="text-xs font-semibold tracking-wide uppercase text-white/70 mt-4">Billing</p>
            <p class="mt-0.5"><span class="text-2xl font-bold">{{ \App\Support\CurrencyConverter::format((float)$order->price, $order->config['currency'] ?? 'NGN') }}</span> <span class="text-sm text-white/80">/ {{ $order->billing_cycle == 1 ? 'Monthly' : 'Every ' . $order->billing_cycle . ' mo' }}</span></p>
            <span class="inline-flex items-center gap-1.5 mt-3 px-2.5 py-1 rounded-full bg-white/15 text-xs font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-white"></span> Active
            </span>
        </div>

        <div class="rounded-2xl p-5 text-white bg-gradient-to-br from-violet-600 to-purple-500 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
            <p class="text-xs font-semibold tracking-wide uppercase text-white/70 mt-4">Host Server</p>
            <p class="text-base font-bold mt-0.5 truncate">{{ $hostNodeName ?: '—' }}</p>
            <div class="mt-3 space-y-1 text-sm text-white/80">
                <p class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg> {{ $vpsIp ?: '—' }}</p>
                <p class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg> Vzid {{ $vpsVzid ?: '—' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 items-start">
        {{-- VPS Information --}}
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    VPS Information
                </h3>
                <button @click="infoCollapsed = !infoCollapsed" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4 transition-transform" :class="infoCollapsed && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </button>
            </div>

            <div x-show="!infoCollapsed" x-cloak>
                <div class="flex justify-center mb-5">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold"
                          :class="{
                              'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400': status === 'running',
                              'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400': status === 'stopped',
                              'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400': status !== 'running' && status !== 'stopped',
                          }">
                        <span class="w-2 h-2 rounded-full" :class="{ 'bg-green-500': status === 'running', 'bg-red-500': status === 'stopped', 'bg-amber-500': status !== 'running' && status !== 'stopped' }"></span>
                        <span x-text="status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'"></span>
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-5">
                    <button @click="run('start')" :disabled="busy || status === 'running'" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white shadow-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4l14 8-14 8V4z"/></svg>
                        </span>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Start</span>
                    </button>
                    <button @click="run('restart')" :disabled="busy" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Restart</span>
                    </button>
                    <button @click="run('stop')" :disabled="busy || status === 'stopped'" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white shadow-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                        </span>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Stop</span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 dark:bg-white/5">
                    <div class="min-w-0">
                        <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Comment</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300 truncate">{{ $vpsComment !== '' ? $vpsComment : 'No comment added' }}</p>
                    </div>
                    <button type="button" disabled title="Coming soon" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 dark:text-slate-600 flex-shrink-0 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card lg:col-span-2">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </h3>
            <div class="flex flex-wrap gap-2.5">
                <template x-for="tile in primaryTiles" :key="tile.key">
                    <button @click="tile.action()" :disabled="busy"
                            class="inline-flex items-center gap-2 pl-1.5 pr-4 py-1.5 rounded-full border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-white"
                              :class="tile.danger ? 'bg-red-500' : 'bg-blue-500'"
                              x-html="tile.icon"></span>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap" x-text="tile.label"></span>
                    </button>
                </template>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open" type="button" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5 transition text-sm font-medium text-slate-700 dark:text-slate-200">
                        More
                        <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition class="absolute right-0 z-10 mt-2 w-60 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-800 shadow-lg p-1.5">
                        <template x-for="tile in moreTiles" :key="tile.key">
                            <button @click="tile.action(); open = false" :disabled="busy"
                                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 text-left disabled:opacity-40 disabled:cursor-not-allowed">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
                                      :class="tile.danger ? 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400'"
                                      x-html="tile.icon"></span>
                                <span class="text-sm text-slate-700 dark:text-slate-200" x-text="tile.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <pre x-show="lastData" x-cloak class="mt-4 text-xs bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 overflow-x-auto" x-text="lastData"></pre>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 items-start">
        {{-- Resource usage --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Resource Usage</h3>
            @if($metrics->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No usage data collected yet — snapshots are taken hourly.</p>
            @else
            <canvas id="usageChart" height="120"></canvas>
            @endif
        </div>

        {{-- Billing history --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Billing History</h3>
            <div class="flex items-center justify-between text-sm p-3 rounded-lg bg-slate-50 dark:bg-white/5 mb-3">
                <span class="text-slate-600 dark:text-slate-300">Invoice #{{ $order->invoice_id }}</span>
                <a href="{{ route('billing.show', $order->invoice_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">View →</a>
            </div>
            <a href="{{ route('billing.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all billing history →</a>
        </div>
    </div>

    {{-- Activity timeline --}}
    <div class="card mb-6">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Activity</h3>
        @include('partials.activity-timeline', ['activity' => $activity])
    </div>

    {{-- Danger zone --}}
    <div class="card border-red-200 dark:border-red-500/20">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Danger Zone</h3>
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">Cancelling permanently deletes this VPS, including all data and backups. There is no rollback, and billing stops once cancelled.</p>
        <button @click="confirmCancel()" :disabled="busy" class="btn btn-danger">Cancel VPS</button>
    </div>

    {{-- Shared modal for parameterized actions --}}
    <div x-show="modal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeModal()" @keydown.escape.window="closeModal()">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6" x-transition>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg text-slate-900 dark:text-white" x-text="modalTitle()"></h3>
                <button @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="message" x-cloak class="mb-4 text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

            <div class="space-y-3">
                <template x-if="modal === 'password'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">New root password</label>
                            <input type="password" x-model="newPassword" class="form-input" placeholder="At least 8 characters">
                        </div>
                        <button @click="run('changepassword', { password: newPassword })" :disabled="busy || newPassword.length < 8" class="btn btn-primary w-full justify-center">Set Password</button>
                    </div>
                </template>

                <template x-if="modal === 'reinstall'">
                    <div class="space-y-3">
                        <p class="text-sm text-red-600 dark:text-red-400">Warning: this destroys all data on the VPS. There is no rollback.</p>
                        <div>
                            <label class="form-label">Template</label>
                            <input type="text" x-model="reinstallTemplate" class="form-input" placeholder="e.g. ubuntu24">
                        </div>
                        <div>
                            <label class="form-label">Your InterServer account password</label>
                            <input type="password" x-model="localPassword" class="form-input" placeholder="Required to confirm this destructive action">
                        </div>
                        <button @click="confirmReinstall()" :disabled="busy || !reinstallTemplate || !localPassword" class="btn btn-danger w-full justify-center">Reinstall</button>
                    </div>
                </template>

                <template x-if="modal === 'hostname'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Hostname</label>
                            <input type="text" x-model="hostname" class="form-input" placeholder="server.example.com">
                        </div>
                        <button @click="run('changehostname', { hostname: hostname })" :disabled="busy || !hostname" class="btn btn-primary w-full justify-center">Update Hostname</button>
                    </div>
                </template>

                <template x-if="modal === 'rdns'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">IP Address</label>
                            <input type="text" x-model="rdnsIp" class="form-input" placeholder="{{ $vpsIp ?: 'e.g. 203.0.113.10' }}">
                        </div>
                        <div>
                            <label class="form-label">PTR Hostname</label>
                            <input type="text" x-model="rdnsHostname" class="form-input" placeholder="server.example.com">
                        </div>
                        <button @click="run('reversedns', { ips: { [rdnsIp]: rdnsHostname } })" :disabled="busy || !rdnsIp || !rdnsHostname" class="btn btn-primary w-full justify-center">Update PTR Record</button>
                    </div>
                </template>

                <template x-if="modal === 'vnc'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Allowed source IP for VNC</label>
                            <input type="text" x-model="vncIp" class="form-input">
                        </div>
                        <button @click="run('setupvnc', { vnc: vncIp })" :disabled="busy || !vncIp" class="btn btn-primary w-full justify-center">Configure VNC</button>
                    </div>
                </template>

                <template x-if="modal === 'slices'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Target slice count</label>
                            <input type="number" min="1" max="32" x-model.number="sliceCount" class="form-input">
                        </div>
                        <button @click="run('resizeslices', { slices: sliceCount })" :disabled="busy || !sliceCount" class="btn btn-primary w-full justify-center">Resize Slices</button>
                    </div>
                </template>

                <template x-if="modal === 'disk'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Additional disk (GB)</label>
                            <input type="number" min="1" max="100" x-model.number="hdSize" class="form-input">
                        </div>
                        <button @click="run('buyhdspace', { size: hdSize })" :disabled="busy || !hdSize" class="btn btn-primary w-full justify-center">Buy / Resize Disk</button>
                    </div>
                </template>

                <template x-if="modal === 'timezone'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Timezone</label>
                            <input type="text" x-model="timezone" class="form-input" placeholder="e.g. America/New_York">
                        </div>
                        <button @click="run('changetimezone', { timezone: timezone })" :disabled="busy || !timezone" class="btn btn-primary w-full justify-center">Update Timezone</button>
                    </div>
                </template>

                <template x-if="modal === 'webuzo'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">New Webuzo panel password</label>
                            <input type="password" x-model="webuzoPassword" class="form-input" placeholder="At least 8 characters">
                        </div>
                        <button @click="run('changewebuzopassword', { password: webuzoPassword })" :disabled="busy || webuzoPassword.length < 8" class="btn btn-primary w-full justify-center">Set Password</button>
                    </div>
                </template>

                <template x-if="modal === 'iso'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">ISO URL</label>
                            <input type="text" x-model="isoUrl" class="form-input" placeholder="https://...">
                        </div>
                        <button @click="run('insertcd', { url: isoUrl })" :disabled="busy || !isoUrl" class="btn btn-primary w-full justify-center">Mount ISO</button>
                    </div>
                </template>

                <template x-if="modal === 'backup'">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Backup filename</label>
                            <input type="text" x-model="backupFile" class="form-input" placeholder="e.g. backup-2026-06-17.tar.gz">
                        </div>
                        <div class="flex gap-3">
                            <button @click="run('downloadbackup', { file: backupFile })" :disabled="busy || !backupFile" class="btn btn-secondary flex-1 justify-center">Download Link</button>
                            <button @click="confirmDeleteBackup()" :disabled="busy || !backupFile" class="btn btn-danger flex-1 justify-center">Delete</button>
                        </div>
                    </div>
                </template>

                <template x-if="modal === 'restore'">
                    <div class="space-y-3">
                        <p class="text-sm text-red-600 dark:text-red-400">Warning: this overwrites the current disk. There is no rollback.</p>
                        <div>
                            <label class="form-label">Backup filename</label>
                            <input type="text" x-model="restoreBackup" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Your InterServer account password</label>
                            <input type="password" x-model="restorePassword" class="form-input">
                        </div>
                        <button @click="confirmRestore()" :disabled="busy || !restoreBackup || !restorePassword" class="btn btn-danger w-full justify-center">Restore</button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection

@push('scripts')
@if($metrics->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('usageChart');
    if (!canvas || !window.Chart) return;
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($metrics->pluck('recorded_at')->map(fn($d) => $d->format('M j g:iA'))),
            datasets: [{
                label: 'Bandwidth (GB)',
                data: @json($metrics->pluck('bandwidth_usage_gb')),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.3,
            }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
});
</script>
@endif
<script>
function vpsManage(orderId) {
    return {
        busy: false,
        message: '',
        success: false,
        lastData: '',
        modal: null,

        newPassword: '',
        reinstallTemplate: '',
        localPassword: '',
        backupFile: '',
        restoreBackup: '',
        restorePassword: '',
        hostname: '',
        rdnsIp: '{{ $vpsIp }}',
        rdnsHostname: '',
        timezone: '',
        webuzoPassword: '',
        isoUrl: '',
        vncIp: '',
        hdSize: null,
        sliceCount: null,
        status: '{{ $vpsStatus ?? '' }}',
        infoCollapsed: false,

        get tiles() {
            const icon = (path, extra = '') => `<svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">${extra}<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${path}"/></svg>`;

            return [
                { key: 'password',  label: 'Root Password',    icon: icon('M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'), action: () => this.openModal('password') },
                { key: 'reinstall', label: 'Reinstall OS',     icon: icon('M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'), danger: true, action: () => this.openModal('reinstall') },
                { key: 'hostname',  label: 'Change Hostname',  icon: icon('M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'), action: () => this.openModal('hostname') },
                { key: 'rdns',      label: 'Reverse DNS',      icon: icon('M13.828 10.172a4 4 0 010 5.656l-2.829 2.829a4 4 0 01-5.656-5.657l1.415-1.414M10.172 13.828a4 4 0 010-5.656l2.829-2.829a4 4 0 015.656 5.657l-1.415 1.414'), action: () => this.openModal('rdns') },
                { key: 'vnc',       label: 'Setup VNC',        icon: icon('M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25'), action: () => this.openModal('vnc') },
                { key: 'desktop',   label: 'View Desktop',     icon: icon('M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'), action: () => this.run('viewdesktop') },
                { key: 'slices',    label: 'Up/Downgrade VPS Slices', icon: icon('M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'), action: () => this.openModal('slices') },
                { key: 'disk',      label: 'Buy / Resize Disk', icon: icon('M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4s8 1.79 8 4'), action: () => this.openModal('disk') },
                { key: 'buyip',     label: 'Buy IP',           icon: icon('M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'), action: () => this.run('buyip') },
                { key: 'timezone',  label: 'Change Timezone',  icon: icon('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'), action: () => this.openModal('timezone') },
                { key: 'webuzo',    label: 'Webuzo Password',  icon: icon('M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z'), action: () => this.openModal('webuzo') },
                { key: 'iso',       label: 'Insert CD/DVD',    icon: icon('M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 17H9m4 0h2m0 0V6a1 1 0 00-1-1H6a1 1 0 00-1 1v11m14 0V9a1 1 0 00-1-1h-3'), action: () => this.openModal('iso') },
                { key: 'ejectcd',   label: 'Eject CD/DVD',     icon: icon('M5 15l7-7 7 7M5 19h14'), action: () => this.run('ejectcd') },
                { key: 'disablecd', label: 'Disable CD Device', icon: icon('M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM6 6l12 12'), action: () => this.run('disablecd') },
                { key: 'snapshot',  label: 'Backup VPS',       icon: icon('M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z', '<circle cx="12" cy="13" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/>'), action: () => this.run('backup') },
                { key: 'backups',   label: 'Download/Manage Backups', icon: icon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'), action: () => this.openModal('backup') },
                { key: 'restore',   label: 'Restore VPS',      icon: icon('M4 4v5h.582m0 0a8.001 8.001 0 0115.356-2M20 20v-5h-.581m0 0a8.001 8.001 0 01-15.357 2'), danger: true, action: () => this.openModal('restore') },
                { key: 'enablequota', label: 'Enable Quota',   icon: icon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'), action: () => this.run('enablequota') },
                { key: 'disablequota', label: 'Disable Quota', icon: icon('M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'), action: () => this.run('disablequota') },
                { key: 'smtp',      label: 'Block Outbound SMTP', icon: icon('M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z'), action: () => this.run('blocksmtp') },
                { key: 'traffic',   label: 'Bandwidth/Traffic Usage', icon: icon('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2'), action: () => this.run('trafficusage') },
            ];
        },

        get primaryTiles() {
            const order = ['reinstall', 'rdns', 'traffic', 'vnc', 'slices', 'buyip', 'desktop', 'timezone', 'iso', 'ejectcd', 'snapshot', 'restore', 'backups'];
            const all = this.tiles;
            return order.map(key => all.find(t => t.key === key)).filter(Boolean);
        },

        get moreTiles() {
            const primaryKeys = ['reinstall', 'rdns', 'traffic', 'vnc', 'slices', 'buyip', 'desktop', 'timezone', 'iso', 'ejectcd', 'snapshot', 'restore', 'backups'];
            return this.tiles.filter(t => !primaryKeys.includes(t.key));
        },

        openModal(name) {
            this.modal = name;
            this.message = '';
        },

        closeModal() {
            this.modal = null;
        },

        modalTitle() {
            const titles = {
                password: 'Change Root Password', reinstall: 'Reinstall Operating System',
                hostname: 'Change Hostname', rdns: 'Reverse DNS', vnc: 'Setup VNC',
                slices: 'Resize Slices', disk: 'Buy / Resize Disk', timezone: 'Change Timezone',
                webuzo: 'Webuzo Control Panel Password', iso: 'Mount ISO', backup: 'Manage Backups',
                restore: 'Restore From Backup',
            };
            return titles[this.modal] || '';
        },

        async run(command, extra = {}) {
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(`/vps/${orderId}/action`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ command, ...extra }),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
                this.lastData = data.data ? JSON.stringify(data.data, null, 2) : '';
                if (data.success && (command === 'changepassword' || command === 'changewebuzopassword')) this.newPassword = this.webuzoPassword = '';
                if (data.success && (command === 'start' || command === 'restart')) this.status = 'running';
                if (data.success && command === 'stop') this.status = 'stopped';
                if (data.success && this.modal) this.closeModal();
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        confirmReinstall() {
            if (!confirm('This will permanently erase all data on this VPS and reinstall the OS. This cannot be undone. Continue?')) return;
            this.run('reinstall', { template: this.reinstallTemplate, localPassword: this.localPassword });
        },

        confirmRestore() {
            if (!confirm('This will overwrite the current disk with the chosen backup. This cannot be undone. Continue?')) return;
            this.run('restore', { backup: this.restoreBackup, password: this.restorePassword });
        },

        confirmDeleteBackup() {
            if (!confirm(`Permanently delete backup "${this.backupFile}"? This cannot be undone.`)) return;
            this.run('deletebackup', { file: this.backupFile });
        },

        async confirmCancel() {
            if (!confirm('This will permanently cancel and delete this VPS, including all data and backups. This cannot be undone. Continue?')) return;
            await this.run('cancel');
            if (this.success) window.location.href = '{{ route('vps.index') }}';
        },
    };
}
</script>
@endpush
