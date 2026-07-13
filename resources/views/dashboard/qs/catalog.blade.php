@extends('layouts.app')
@section('title', 'Order a Quick Server')
@section('breadcrumb')
    <a href="{{ route('qs.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Quick Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order a Quick Server</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order a Quick Server</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Pick from InterServer's live rapid-deploy inventory — we'll create an invoice and provision automatically once it's paid.</p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="qsOrder({
        servers: {{ json_encode($servers) }},
        templates: {{ json_encode($templates) }},
        prices: {{ json_encode($prices) }},
     })">

    <form method="POST" action="{{ route('qs.store') }}" class="card lg:col-span-2 space-y-5" @submit="loading = true">
        @csrf
        <input type="hidden" name="server" :value="server">
        <input type="hidden" name="os" :value="os">

        <div>
            <label class="form-label">Server</label>
            <div class="space-y-2">
                <template x-for="id in Object.keys(servers)" :key="id">
                    <label class="flex items-center justify-between gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                           :class="server === id ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10' : 'border-slate-200 dark:border-white/[0.08]'">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="server_radio" :value="id" x-model="server" @change="quote()">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="servers[id].cpu"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="servers[id].cores + ' cores · ' + servers[id].ram + ' RAM · ' + servers[id].hd + ' disk'"></p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white" x-text="'{{ $currency }}' + (prices[id]?.toFixed(2) ?? '—') + '/mo'"></span>
                    </label>
                </template>
            </div>
        </div>

        <div>
            <label class="form-label">Operating System</label>
            <select x-model="distro" @change="onDistroChange()" class="form-input">
                <template x-for="d in Object.keys(templates)" :key="d">
                    <option :value="d" x-text="d"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Version</label>
            <select x-model="os" @change="quote()" class="form-input">
                <template x-for="entry in distroVersions()" :key="entry[1]">
                    <option :value="entry[1]" x-text="entry[0]"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Root Password</label>
            <div class="flex gap-2">
                <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" placeholder="At least 8 characters, mixed case, number, symbol" class="form-input flex-1">
                <button type="button" @click="showPassword = !showPassword" class="btn btn-secondary px-3" x-text="showPassword ? 'Hide' : 'Show'"></button>
                <button type="button" @click="password = generateStrongPassword(); showPassword = true" class="btn btn-secondary px-3 whitespace-nowrap">Generate</button>
            </div>
        </div>

        <div>
            <label class="form-label">Comment <span class="text-slate-400">(optional)</span></label>
            <input type="text" name="comment" x-model="comment" placeholder="Internal note for this server" class="form-input">
        </div>

        <div x-show="quoteError" class="text-sm text-red-600 dark:text-red-400" x-text="quoteError"></div>

        <button type="submit" :disabled="loading || !priceReady || password.length < 8" class="btn btn-primary w-full justify-center">
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
            <div class="flex justify-between"><span>Server</span><span class="font-medium text-slate-900 dark:text-white" x-text="servers[server]?.cpu"></span></div>
            <div class="flex justify-between"><span>Billing cycle</span><span class="font-medium text-slate-900 dark:text-white">Monthly</span></div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-baseline justify-between">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total due / mo</span>
            <span class="text-2xl font-bold text-slate-900 dark:text-white">
                <template x-if="quoting"><span class="text-sm text-slate-400">calculating…</span></template>
                <template x-if="!quoting && price !== null">{{ $currency }}<span x-text="price.toFixed(2)"></span></template>
                <template x-if="!quoting && price === null">—</template>
            </span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function qsOrder(opts) {
    return {
        servers: opts.servers,
        templates: opts.templates,
        prices: opts.prices,

        server: Object.keys(opts.servers)[0] || '',
        distro: Object.keys(opts.templates)[0] || '',
        os: '',
        password: '',
        showPassword: false,
        comment: '',

        price: null,
        quoting: false,
        quoteError: '',
        loading: false,

        get priceReady() { return this.price !== null && !this.quoteError; },

        init() {
            this.price = this.prices[this.server] ?? null;
            this.os = (this.distroVersions()[0] || [])[1] || '';
        },

        distroVersions() {
            const archs = this.templates[this.distro] || {};
            return Object.values(archs)[0] || [];
        },

        onDistroChange() {
            this.os = (this.distroVersions()[0] || [])[1] || '';
            this.quote();
        },

        async quote() {
            if (!this.server || !this.os) {
                this.price = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('qs.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ server: this.server, os: this.os }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.price = data.price;
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
