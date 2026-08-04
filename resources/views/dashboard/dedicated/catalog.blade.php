@extends('layouts.app')
@section('title', 'Order a Dedicated Server')
@section('breadcrumb')
    <a href="{{ route('dedicated.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Dedicated Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order a Dedicated Server</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order a Dedicated Server</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Pick from InterServer's live Rapid Deploy inventory — we'll create an invoice and provision automatically once it's paid.</p>
</div>

@if($errors->any())
<div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        @foreach($errors->all() as $error)
            <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
        @endforeach
    </div>
</div>
@endif

@if(empty($servers))
<div class="card text-center py-16">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No servers in stock right now</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">InterServer's Rapid Deploy inventory is temporarily empty. Please check back shortly.</p>
</div>
@else
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="dedicatedOrder({
        servers: {{ json_encode($servers) }},
        prices: {{ json_encode($prices) }},
     })">

    <form method="POST" action="{{ route('dedicated.store') }}" class="card lg:col-span-2 space-y-5" @submit="loading = true">
        @csrf
        <input type="hidden" name="asset_id" :value="assetId">
        <input type="hidden" name="os" :value="os">
        <input type="hidden" name="bandwidth" :value="bandwidth">
        <input type="hidden" name="ips" :value="ips">
        <input type="hidden" name="cp" :value="cp">
        <input type="hidden" name="raid" :value="raid">

        <div>
            <label class="form-label">Server</label>
            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                <template x-for="id in Object.keys(servers)" :key="id">
                    <label class="flex items-center justify-between gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                           :class="assetId === id ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10' : 'border-slate-200 dark:border-white/[0.08]'">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="asset_radio" :value="id" x-model="assetId" @change="quote()">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="cpuLabel(servers[id])"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"
                                   x-text="servers[id].memory + ' RAM · ' + diskLabel(servers[id]) + ' · ' + servers[id].location"></p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white" x-text="'{{ $currency }}' + (prices[id]?.toFixed(2) ?? '—') + '/mo'"></span>
                    </label>
                </template>
            </div>
        </div>

        <div x-show="assetId" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Operating System</label>
                <select x-model="os" class="form-input">
                    <template x-for="opt in options.os" :key="opt.id">
                        <option :value="opt.id" x-text="opt.short_desc"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="form-label">Bandwidth</label>
                <select x-model="bandwidth" class="form-input">
                    <template x-for="opt in options.bandwidth" :key="opt.id">
                        <option :value="opt.id" x-text="opt.short_desc"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="form-label">IP Block</label>
                <select x-model="ips" class="form-input">
                    <template x-for="opt in options.ips" :key="opt.id">
                        <option :value="opt.id" x-text="opt.short_desc"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="form-label">Control Panel</label>
                <select x-model="cp" class="form-input">
                    <template x-for="opt in options.cp" :key="opt.id">
                        <option :value="opt.id" x-text="opt.short_desc"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">RAID</label>
                <select x-model="raid" class="form-input">
                    <template x-for="opt in options.raid" :key="opt.id">
                        <option :value="opt.id" x-text="opt.short_desc"></option>
                    </template>
                </select>
            </div>
        </div>

        <div>
            <label class="form-label">Hostname</label>
            <input type="text" name="hostname" x-model="hostname" placeholder="server.yourdomain.com" class="form-input">
        </div>

        <div>
            <label class="form-label">Root Password</label>
            <div class="flex gap-2">
                <input :type="showPassword ? 'text' : 'password'" name="rootpass" x-model="rootpass" placeholder="At least 8 characters, mixed case, number, symbol" class="form-input flex-1">
                <button type="button" @click="showPassword = !showPassword" class="btn btn-secondary px-3" x-text="showPassword ? 'Hide' : 'Show'"></button>
                <button type="button" @click="rootpass = generateStrongPassword(); showPassword = true" class="btn btn-secondary px-3 whitespace-nowrap">Generate</button>
            </div>
        </div>

        <div>
            <label class="form-label">Comment <span class="text-slate-400">(optional)</span></label>
            <input type="text" name="comment" x-model="comment" placeholder="Internal note for this server" class="form-input">
        </div>

        <div x-show="quoteError" class="text-sm text-red-600 dark:text-red-400" x-text="quoteError"></div>

        <button type="submit" :disabled="loading || !priceReady || rootpass.length < 8 || !hostname" class="btn btn-primary w-full justify-center">
            <span x-show="!loading">Create Invoice</span>
            <span x-show="loading" class="flex items-center gap-2">
                <div class="spinner"></div>
                Creating invoice…
            </span>
        </button>
    </form>

    <div class="card h-fit">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Order Summary</h3>
        <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400 mb-4">
            <div class="flex justify-between"><span>Server</span><span class="font-medium text-slate-900 dark:text-white" x-text="assetId ? cpuLabel(servers[assetId]) : '—'"></span></div>
            <div class="flex justify-between"><span>Billing cycle</span><span class="font-medium text-slate-900 dark:text-white">Monthly</span></div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-baseline justify-between">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total due / mo</span>
            <span class="text-2xl font-bold text-slate-900 dark:text-white">
                <template x-if="quoting"><span class="text-sm text-slate-400">calculating…</span></template>
                <template x-if="!quoting && price !== null"><span>{{ $currency }}<span x-text="price.toFixed(2)"></span></span></template>
                <template x-if="!quoting && price === null"><span>—</span></template>
            </span>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function dedicatedOrder(opts) {
    return {
        servers: opts.servers,
        prices: opts.prices,

        assetId: '',
        options: { os: [], bandwidth: [], ips: [], cp: [], raid: [] },
        os: '',
        bandwidth: '',
        ips: '',
        cp: '',
        raid: '',
        hostname: '',
        rootpass: '',
        showPassword: false,
        comment: '',

        price: null,
        quoting: false,
        quoteError: '',
        loading: false,

        get priceReady() { return this.price !== null && !this.quoteError; },

        cpuLabel(server) {
            if (!server) return '';
            return Array.isArray(server.cpu) ? server.cpu[0] : server.cpu;
        },

        diskLabel(server) {
            if (!server || !server.disk) return '—';
            return typeof server.disk === 'object' ? Object.values(server.disk).join(', ') : server.disk;
        },

        async quote() {
            if (!this.assetId) {
                this.price = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('dedicated.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ asset_id: this.assetId }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.price = data.price;
                    this.options = { os: data.os, bandwidth: data.bandwidth, ips: data.ips, cp: data.cp, raid: data.raid };
                    this.os = (data.os[0] || {}).id ?? '';
                    this.bandwidth = (data.bandwidth[0] || {}).id ?? '';
                    this.ips = (data.ips[0] || {}).id ?? '';
                    this.cp = (data.cp[0] || {}).id ?? '';
                    this.raid = (data.raid[0] || {}).id ?? '';
                } else {
                    this.price = null;
                    this.quoteError = data.error || 'This configuration is not available.';
                }
            } catch (e) {
                this.price = null;
                this.quoteError = 'Could not reach the server. Please try again.';
            } finally {
                this.quoting = false;
            }
        },
    };
}
</script>
@endpush
