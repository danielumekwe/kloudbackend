@extends('layouts.app')
@section('title', $plan['label'])
@section('breadcrumb')
    <a href="{{ route('vps.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My VPS</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order {{ $plan['label'] }}</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order {{ $plan['label'] }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Configure your VPS and we'll create an invoice — your server is provisioned automatically once it's paid.</p>
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
     x-data="vpsOrder({
        templates: {{ json_encode($templates) }},
        osNames: {{ json_encode($osNames) }},
        locations: {{ json_encode($locations) }},
        stock: {{ json_encode($stock) }},
        periods: {{ json_encode($periods) }},
        controlpanelOptions: {{ json_encode($controlpanelOptions) }},
        currency: {{ json_encode($currency) }},
        platform: '{{ $plan['platform'] }}',
        category: '{{ $category }}',
        minSlices: {{ $minSlices }},
        recommendedMinSlices: {{ $recommendedMinSlices }},
     })">

    {{-- Form --}}
    <form method="POST" action="{{ route('vps.store', $category) }}" class="card lg:col-span-2 space-y-5" @submit="loading = true">
        @csrf
        <input type="hidden" name="osDistro" :value="osDistro">
        <input type="hidden" name="osVersion" :value="osVersion">
        <input type="hidden" name="location" :value="location">
        <input type="hidden" name="period" :value="period">
        <input type="hidden" name="controlpanel" :value="controlpanel">

        <div>
            <label class="form-label">Operating System</label>
            <select x-model="osDistro" @change="onOsChange()" class="form-input">
                <template x-for="os in Object.keys(templates)" :key="os">
                    <option :value="os" x-text="osNames[os] || os"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Version</label>
            <select x-model="osVersion" @change="quote()" class="form-input">
                <template x-for="(version, file) in (templates[osDistro] || {})" :key="file">
                    <option :value="file" x-text="version"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Datacenter Location</label>
            <select x-model="location" @change="quote()" class="form-input">
                <template x-for="locId in availableLocations()" :key="locId">
                    <option :value="locId" x-text="locations[locId]"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Slices <span class="text-slate-400">(1 slice = 2GB RAM / 40GB disk)</span></label>
            <div class="flex items-center gap-3">
                <button type="button" @click="slices = Math.max(minSlices, slices - 1); quote()" class="btn btn-secondary px-3">−</button>
                <input type="number" name="slices" x-model.number="slices" @change="quote()" :min="minSlices" max="32" class="form-input text-center w-20">
                <button type="button" @click="slices = Math.min(32, slices + 1); quote()" class="btn btn-secondary px-3">+</button>
            </div>
            <p class="text-xs text-slate-400 mt-1" x-show="minSlices > 1">Minimum <span x-text="minSlices"></span> slices for this plan.</p>
            <div x-show="minSlices === 1 && recommendedMinSlices > minSlices" class="mt-2 flex items-start gap-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30">
                <svg class="w-4.5 h-4.5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Managed Windows VPS starts from <span x-text="recommendedMinSlices"></span> slices upwards — fewer slices may have limited availability.</p>
            </div>
        </div>

        <div x-show="hasControlpanelOptions()">
            <label class="form-label">Control Panel / License</label>
            <select x-model="controlpanel" @change="quote()" class="form-input">
                <template x-for="key in Object.keys(controlpanelOptions)" :key="key">
                    <option :value="key" x-text="controlpanelOptions[key].label + ' — ' + formatPrice(controlpanelOptions[key].price) + '/mo'"></option>
                </template>
            </select>
            <ul class="text-xs text-slate-400 mt-2 list-disc list-inside" x-show="(controlpanelOptions[controlpanel]?.features || []).length">
                <template x-for="feature in (controlpanelOptions[controlpanel]?.features || [])" :key="feature">
                    <li x-text="feature"></li>
                </template>
            </ul>
        </div>

        <div>
            <label class="form-label">Billing Cycle</label>
            <select x-model.number="period" @change="quote()" class="form-input">
                <template x-for="(p, months) in periods" :key="months">
                    <option :value="parseInt(months)" x-text="p.label"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Hostname</label>
            <input type="text" name="hostname" x-model="hostname" @blur="quote()" placeholder="server.example.com" class="form-input">
            <p class="text-xs text-slate-400 mt-1">Must be a full hostname with at least two dots, e.g. server.example.com</p>
        </div>

        <div>
            <label class="form-label">Root Password</label>
            <input type="password" name="rootpass" x-model="rootpass" placeholder="At least 8 characters, mixed case, number, symbol" class="form-input">
        </div>

        <div x-show="quoteError" class="text-sm text-red-600 dark:text-red-400" x-text="quoteError"></div>

        <button type="submit" :disabled="loading || !priceReady" class="btn btn-primary w-full justify-center">
            <span x-show="!loading">Create Invoice</span>
            <span x-show="loading" class="flex items-center gap-2">
                <div class="spinner"></div>
                Creating invoice…
            </span>
        </button>
    </form>

    {{-- Summary --}}
    <div class="card h-fit">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Order Summary</h3>
        <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400 mb-4">
            <div class="flex justify-between"><span>Plan</span><span class="font-medium text-slate-900 dark:text-white">{{ $plan['label'] }}</span></div>
            <div class="flex justify-between"><span>Slices</span><span class="font-medium text-slate-900 dark:text-white" x-text="slices"></span></div>
            <div class="flex justify-between" x-show="hasControlpanelOptions()"><span>Control Panel</span><span class="font-medium text-slate-900 dark:text-white" x-text="controlpanelOptions[controlpanel]?.label"></span></div>
            <div class="flex justify-between"><span>Billing cycle</span><span class="font-medium text-slate-900 dark:text-white" x-text="periods[period]?.label"></span></div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-baseline justify-between">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total due</span>
            <span class="text-2xl font-bold text-slate-900 dark:text-white">
                <template x-if="quoting"><span class="text-sm text-slate-400">calculating…</span></template>
                <template x-if="!quoting && price !== null"><span x-text="formatPrice(price)"></span></template>
                <template x-if="!quoting && price === null">—</template>
            </span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vpsOrder(opts) {
    return {
        templates: opts.templates,
        osNames: opts.osNames,
        locations: opts.locations,
        stock: opts.stock,
        periods: opts.periods,
        controlpanelOptions: opts.controlpanelOptions,
        currency: opts.currency,
        platform: opts.platform,
        category: opts.category,
        minSlices: opts.minSlices,
        recommendedMinSlices: opts.recommendedMinSlices,

        osDistro: Object.keys(opts.templates)[0] || '',
        osVersion: '',
        location: '1',
        slices: opts.minSlices,
        period: 1,
        hostname: '',
        rootpass: '',
        controlpanel: Object.keys(opts.controlpanelOptions)[0] || '',

        price: null,
        quoting: false,
        quoteError: '',
        loading: false,

        get priceReady() { return this.price !== null && !this.quoteError; },

        init() {
            this.osVersion = Object.keys(this.templates[this.osDistro] || {})[0] || '';
            const avail = this.availableLocations();
            if (avail.length) this.location = avail[0];
        },

        onOsChange() {
            this.osVersion = Object.keys(this.templates[this.osDistro] || {})[0] || '';
            this.quote();
        },

        availableLocations() {
            return Object.keys(this.locations).filter(id => this.stock[id]?.[this.platform]);
        },

        hasControlpanelOptions() {
            return Object.keys(this.controlpanelOptions).length > 0;
        },

        formatPrice(amount) {
            if (this.currency.prefix) return this.currency.prefix + amount.toFixed(2);
            if (this.currency.suffix) return amount.toFixed(2) + ' ' + this.currency.suffix;
            return this.currency.code + ' ' + amount.toFixed(2);
        },

        async quote() {
            if (!this.hostname || !/^.*\..*\..*$/.test(this.hostname)) {
                this.price = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('vps.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        category: this.category,
                        osDistro: this.osDistro,
                        osVersion: this.osVersion,
                        slices: this.slices,
                        location: this.location,
                        period: this.period,
                        hostname: this.hostname,
                        controlpanel: this.controlpanel,
                    }),
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
