@extends('layouts.admin')
@section('title', 'Billing Settings')
@section('breadcrumb', 'Billing Settings')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Billing Settings</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Currency exchange rates and tax apply immediately to new invoices.</p>

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

<form method="POST" action="{{ route('admin.billing-settings.update') }}" class="space-y-6">
    @csrf

    {{-- Currencies --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-1">Currencies</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">1 USD = this many units of the currency.</p>
        <div class="space-y-4">
            @foreach($currencies as $currency)
            <div class="flex flex-wrap items-end gap-3 pb-4 border-b border-slate-100 dark:border-white/[0.05] last:border-0 last:pb-0">
                <div class="flex-1 min-w-[140px]">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $currency['label'] }} ({{ $currency['code'] }})</p>
                    @if($currency['default'])
                        <p class="text-xs text-slate-400">Base currency — fixed at 1.00</p>
                    @endif
                </div>
                <div>
                    <label class="form-label">Rate</label>
                    @if($currency['default'])
                        <input type="number" value="1.00" disabled class="form-input w-32 opacity-50">
                    @else
                        <input type="number" step="0.01" min="0" name="currency[{{ $currency['code'] }}][rate]" value="{{ old("currency.{$currency['code']}.rate", $currency['rate']) }}" class="form-input w-32">
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tax --}}
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Tax</h2>
        <div>
            <label class="form-label">Flat tax rate % (applied to every invoice)</label>
            <input type="number" step="0.01" min="0" max="100" name="tax_rate_percent" value="{{ old('tax_rate_percent', $taxRatePercent) }}" class="form-input w-32">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Billing Settings</button>
</form>
@endsection
