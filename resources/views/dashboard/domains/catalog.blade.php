@extends('layouts.app')
@section('title', 'Register ' . $domain)
@section('breadcrumb')
    <a href="{{ route('domains.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Domains</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Register {{ $domain }}</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Register {{ $domain }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">We'll create an invoice — the domain is registered automatically once it's paid.</p>
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
     x-data="domainOrder({ domain: '{{ $domain }}', basePrice: {{ $price }}, whoisAvailable: {{ $whoisPrivacy ? 'true' : 'false' }} })">

    <form method="POST" action="{{ route('domains.store') }}" class="card lg:col-span-2 space-y-5" @submit="loading = true">
        @csrf
        <input type="hidden" name="domain" value="{{ $domain }}">

        <div>
            <label class="form-label">Registration Length</label>
            <select name="registration_years" x-model.number="years" @change="quote()" class="form-input">
                @for($y = 1; $y <= 10; $y++)
                    <option value="{{ $y }}">{{ $y }} year{{ $y > 1 ? 's' : '' }}</option>
                @endfor
            </select>
        </div>

        @if($whoisPrivacy)
        <div class="flex items-center gap-3">
            <input type="checkbox" name="whois_privacy" id="whois_privacy" value="1" x-model="whoisPrivacy" @change="quote()" class="rounded">
            <label for="whois_privacy" class="text-sm text-slate-700 dark:text-slate-300">Add Whois Privacy Protection</label>
        </div>
        @endif

        <h3 class="font-semibold text-slate-900 dark:text-white pt-2">Registrant Contact</h3>
        <div class="grid grid-cols-2 gap-3">
            <input type="text" name="first_name" value="{{ $fields['firstname']['value'] ?? '' }}" placeholder="First name" class="form-input" required>
            <input type="text" name="last_name" value="{{ $fields['lastname']['value'] ?? '' }}" placeholder="Last name" class="form-input" required>
            <input type="email" name="email" value="{{ $fields['email']['value'] ?? '' }}" placeholder="Email" class="form-input col-span-2" required>
            <input type="text" name="org_name" value="{{ $fields['company']['value'] ?? '' }}" placeholder="Company (optional)" class="form-input col-span-2">
            <input type="text" name="address1" value="{{ $fields['address']['value'] ?? '' }}" placeholder="Address" class="form-input col-span-2" required>
            <input type="text" name="address2" value="{{ $fields['address2']['value'] ?? '' }}" placeholder="Address line 2 (optional)" class="form-input col-span-2">
            <input type="text" name="city" value="{{ $fields['city']['value'] ?? '' }}" placeholder="City" class="form-input" required>
            <input type="text" name="state" value="{{ $fields['state']['value'] ?? '' }}" placeholder="State" class="form-input" required>
            <input type="text" name="postal_code" value="{{ $fields['zip']['value'] ?? '' }}" placeholder="Zip" class="form-input" required>
            <input type="text" name="country" value="{{ $fields['country']['value'] ?? '' }}" placeholder="Country (e.g. US)" class="form-input" required>
            <input type="text" name="phone" value="{{ $fields['phone']['value'] ?? '' }}" placeholder="Phone" class="form-input col-span-2" required>
        </div>

        <div x-show="quoteError" class="text-sm text-red-600 dark:text-red-400" x-text="quoteError"></div>

        <button type="submit" :disabled="loading" class="btn btn-primary w-full justify-center">
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
            <div class="flex justify-between"><span>Domain</span><span class="font-medium text-slate-900 dark:text-white" x-text="domain"></span></div>
            <div class="flex justify-between"><span>Length</span><span class="font-medium text-slate-900 dark:text-white" x-text="years + ' yr'"></span></div>
            <div class="flex justify-between" x-show="whoisPrivacy"><span>Whois Privacy</span><span class="font-medium text-slate-900 dark:text-white">Included</span></div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-white/[0.05] flex items-baseline justify-between">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total due</span>
            <span class="text-2xl font-bold text-slate-900 dark:text-white">
                <template x-if="quoting"><span class="text-sm text-slate-400">calculating…</span></template>
                <template x-if="!quoting && price !== null">{{ $currency }}<span x-text="price.toFixed(2)"></span></template>
            </span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function domainOrder(opts) {
    return {
        domain: opts.domain,
        years: 1,
        whoisPrivacy: false,
        price: opts.basePrice,
        quoting: false,
        quoteError: '',
        loading: false,

        async quote() {
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('domains.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ domain: this.domain, registration_years: this.years, whois_privacy: this.whoisPrivacy }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.price = data.price;
                } else {
                    this.quoteError = data.error || 'Could not calculate price.';
                }
            } catch (e) {
                this.quoteError = 'Could not reach the server. Please try again.';
            } finally {
                this.quoting = false;
            }
        },
    };
}
</script>
@endpush
