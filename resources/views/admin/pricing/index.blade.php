@extends('layouts.admin')
@section('title', 'Pricing')
@section('breadcrumb', 'Pricing')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Pricing</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Changes apply immediately to new quotes and orders.</p>

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

<form method="POST" action="{{ route('admin.pricing.update') }}" class="space-y-6">
    @csrf

    {{-- VPS --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">VPS</h2>
        <div class="space-y-4">
            @foreach($vpsCategories as $cat)
            <div class="flex flex-wrap items-end gap-3 pb-4 border-b border-slate-100 dark:border-white/[0.05] last:border-0 last:pb-0">
                <div class="flex-1 min-w-[140px]">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $cat['label'] }}</p>
                    <p class="text-xs text-slate-400">{{ $cat['key'] }}</p>
                </div>
                <div>
                    <label class="form-label">Price / slice</label>
                    <input type="number" step="0.01" min="0" name="vps[{{ $cat['key'] }}][price_per_slice]" value="{{ old("vps.{$cat['key']}.price_per_slice", $cat['price_per_slice']) }}" class="form-input w-32">
                </div>
                @if($cat['has_controlpanel'])
                <div>
                    <label class="form-label">Control panel</label>
                    <input type="number" step="0.01" min="0" name="vps[{{ $cat['key'] }}][controlpanel_price]" value="{{ old("vps.{$cat['key']}.controlpanel_price", $cat['controlpanel_price']) }}" class="form-input w-32">
                </div>
                @endif
                @foreach($cat['controlpanel_options'] as $optionKey => $option)
                <div>
                    <label class="form-label">{{ $option['label'] }}</label>
                    <input type="number" step="0.01" min="0" name="vps[{{ $cat['key'] }}][controlpanel_options][{{ $optionKey }}]" value="{{ old("vps.{$cat['key']}.controlpanel_options.{$optionKey}", $option['price']) }}" class="form-input w-32">
                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-6 mb-3">Billing cycle discounts</h3>
        <div class="flex flex-wrap gap-4">
            @foreach($vpsPeriods as $p)
            <div>
                <label class="form-label">{{ $p['label'] }}</label>
                <input type="number" step="0.01" min="0" max="2" name="vps_period[{{ $p['months'] }}]" value="{{ old("vps_period.{$p['months']}", $p['discount']) }}" class="form-input w-28">
            </div>
            @endforeach
        </div>
    </div>

    {{-- Quick Servers --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Quick Servers</h2>
        <div class="flex flex-wrap gap-4 mb-4">
            <div>
                <label class="form-label">Markup %</label>
                <input type="number" step="0.01" min="0" name="qs_markup_percent" value="{{ old('qs_markup_percent', $qsMarkupPercent) }}" class="form-input w-32">
            </div>
        </div>
        <div>
            <label class="form-label">Per-server price overrides (JSON, InterServer server id → flat USD price)</label>
            <textarea name="qs_server_overrides" rows="4" class="form-input font-mono text-xs">{{ old('qs_server_overrides', $qsServerOverrides) }}</textarea>
        </div>
    </div>

    {{-- SSL --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">SSL Certificates</h2>
        <div class="flex flex-wrap gap-4 mb-4">
            <div>
                <label class="form-label">Markup %</label>
                <input type="number" step="0.01" min="0" name="ssl_markup_percent" value="{{ old('ssl_markup_percent', $sslMarkupPercent) }}" class="form-input w-32">
            </div>
            @foreach($sslPeriods as $p)
            <div>
                <label class="form-label">{{ $p['label'] }} discount</label>
                <input type="number" step="0.01" min="0" max="2" name="ssl_period[{{ $p['months'] }}]" value="{{ old("ssl_period.{$p['months']}", $p['discount']) }}" class="form-input w-28">
            </div>
            @endforeach
        </div>
        <div>
            <label class="form-label">Per-package price overrides (JSON, InterServer package id → flat USD price)</label>
            <textarea name="ssl_package_overrides" rows="4" class="form-input font-mono text-xs">{{ old('ssl_package_overrides', $sslPackageOverrides) }}</textarea>
        </div>
    </div>

    {{-- Domains --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Domains</h2>
        <div class="flex flex-wrap gap-4 mb-4">
            <div>
                <label class="form-label">Markup %</label>
                <input type="number" step="0.01" min="0" name="domains_markup_percent" value="{{ old('domains_markup_percent', $domainsMarkupPercent) }}" class="form-input w-32">
            </div>
            <div>
                <label class="form-label">Whois Privacy add-on</label>
                <input type="number" step="0.01" min="0" name="domains_whois_privacy_price" value="{{ old('domains_whois_privacy_price', $domainsWhoisPrivacyPrice) }}" class="form-input w-32">
            </div>
        </div>
        <div>
            <label class="form-label">Per-TLD price overrides (JSON, e.g. {"com": 12.99})</label>
            <textarea name="domains_tld_overrides" rows="4" class="form-input font-mono text-xs">{{ old('domains_tld_overrides', $domainsTldOverrides) }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Pricing</button>
</form>

{{-- Dedicated Servers (WHMCS, read-only) --}}
<div class="card mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-900 dark:text-white">Dedicated Servers</h2>
        <a href="{{ rtrim(config('services.whmcs.url'), '/') }}/admin/configproducts.php" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline">Edit in WHMCS →</a>
    </div>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">These prices come directly from WHMCS's product catalog — edit them there, not here.</p>
    <div class="space-y-2">
        @forelse($servers as $product)
        <div class="flex items-center justify-between text-sm py-2 border-b border-slate-100 dark:border-white/[0.05] last:border-0">
            <span class="font-medium text-slate-900 dark:text-white">{{ $product['name'] }}</span>
            <span class="text-slate-500 dark:text-slate-400">
                @foreach($product['pricing'] as $cycle => $price)
                    @if(is_numeric($price) && $price >= 0)
                        <span class="ml-3">{{ $cycle }}: ${{ number_format($price, 2) }}</span>
                    @endif
                @endforeach
            </span>
        </div>
        @empty
        <p class="text-sm text-slate-400">No WHMCS products found.</p>
        @endforelse
    </div>
</div>
@endsection
