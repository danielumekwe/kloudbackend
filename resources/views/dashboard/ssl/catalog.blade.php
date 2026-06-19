@extends('layouts.app')
@section('title', 'Order an SSL Certificate')
@section('breadcrumb')
    <a href="{{ route('ssl.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My SSL Certificates</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order an SSL Certificate</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order an SSL Certificate</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Choose a certificate type, tell us the hostname to secure, and we'll create an invoice — the certificate is issued automatically once it's paid.</p>
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
     x-data="sslOrder({
        packages: {{ $packages->keyBy('services_id')->toJson() }},
        prices: {{ $prices->toJson() }},
        periods: {{ json_encode($periods) }},
     })">

    <form method="POST" action="{{ route('ssl.store') }}" class="card lg:col-span-2 space-y-5" @submit="loading = true">
        @csrf
        <input type="hidden" name="package_id" :value="packageId">

        <div>
            <label class="form-label">Certificate Type</label>
            <select x-model="packageId" @change="quote()" class="form-input">
                <template x-for="id in Object.keys(packages)" :key="id">
                    <option :value="id" x-text="packages[id].services_name"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">Hostname to Secure</label>
            <input type="text" name="hostname" x-model="hostname" @blur="quote()" placeholder="www.example.com" class="form-input">
        </div>

        <div>
            <label class="form-label">Approver Email</label>
            <input type="email" name="approver_email" x-model="approverEmail" @blur="quote()" placeholder="admin@example.com" class="form-input">
            <p class="text-xs text-slate-400 mt-1">Domain-control validation email will be sent here.</p>
        </div>

        <div>
            <label class="form-label">Billing Term</label>
            <select x-model.number="frequency" @change="quote()" class="form-input">
                <template x-for="(p, months) in periods" :key="months">
                    <option :value="parseInt(months)" x-text="p.label"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="form-label">CSR</label>
            <div class="flex gap-4 mb-2">
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="csr_type" value="generated" x-model="csrType"> Generate for me</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="csr_type" value="provided" x-model="csrType"> I'll provide my own</label>
            </div>
            <textarea x-show="csrType === 'provided'" name="csr" x-model="csr" rows="5" class="form-input font-mono text-xs" placeholder="-----BEGIN CERTIFICATE REQUEST-----"></textarea>
        </div>

        <button type="button" @click="showContact = !showContact" class="text-sm text-blue-600 dark:text-blue-400 font-medium" x-text="showContact ? 'Hide contact override' : 'Use different contact info'"></button>

        <div x-show="showContact" class="grid grid-cols-2 gap-3">
            <input type="text" name="firstname" x-model="firstname" placeholder="First name" class="form-input">
            <input type="text" name="lastname" x-model="lastname" placeholder="Last name" class="form-input">
            <input type="email" name="email" x-model="email" placeholder="Contact email" class="form-input col-span-2">
            <input type="text" name="company" x-model="company" placeholder="Company" class="form-input col-span-2">
            <input type="text" name="address" x-model="address" placeholder="Address" class="form-input col-span-2">
            <input type="text" name="city" x-model="city" placeholder="City" class="form-input">
            <input type="text" name="state" x-model="state" placeholder="State" class="form-input">
            <input type="text" name="zip" x-model="zip" placeholder="Zip" class="form-input">
            <input type="text" name="country" x-model="country" placeholder="Country (e.g. US)" class="form-input">
            <input type="text" name="phone" x-model="phone" placeholder="Phone" class="form-input col-span-2">
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

    <div class="card h-fit">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Order Summary</h3>
        <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400 mb-4">
            <div class="flex justify-between"><span>Certificate</span><span class="font-medium text-slate-900 dark:text-white" x-text="packages[packageId]?.services_name"></span></div>
            <div class="flex justify-between"><span>Billing term</span><span class="font-medium text-slate-900 dark:text-white" x-text="periods[frequency]?.label"></span></div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-baseline justify-between">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total due</span>
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
function sslOrder(opts) {
    return {
        packages: opts.packages,
        prices: opts.prices,
        periods: opts.periods,

        packageId: Object.keys(opts.packages)[0] || '',
        hostname: '',
        approverEmail: '',
        frequency: 12,
        csrType: 'generated',
        csr: '',
        showContact: false,
        firstname: '', lastname: '', email: '', company: '', address: '',
        city: '', state: '', zip: '', country: '', phone: '',

        price: null,
        quoting: false,
        quoteError: '',
        loading: false,

        get priceReady() { return this.price !== null && !this.quoteError; },

        init() {
            this.price = this.prices[this.packageId] ?? null;
        },

        async quote() {
            if (!this.hostname || !this.approverEmail) {
                this.price = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('ssl.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        package_id: this.packageId,
                        hostname: this.hostname,
                        approver_email: this.approverEmail,
                        frequency: this.frequency,
                        csr_type: this.csrType,
                        csr: this.csr,
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
