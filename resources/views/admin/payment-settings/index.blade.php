@extends('layouts.admin')
@section('title', 'Payment Settings')
@section('breadcrumb', 'Payment Settings')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Payment Settings</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Gateway credentials used to accept payments. Leave a secret field blank to keep its current value.</p>

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

<form method="POST" action="{{ route('admin.payment-settings.update') }}" class="space-y-6">
    @csrf

    @php
        $labels = [
            'paystack'    => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'nowpayments' => 'NOWPayments',
        ];
        $fieldLabels = [
            'public_key'   => 'Public Key',
            'secret_key'   => 'Secret Key',
            'webhook_hash' => 'Webhook Hash',
            'api_key'      => 'API Key',
            'ipn_secret'   => 'IPN Secret',
        ];
    @endphp

    @foreach($gateways as $gateway => $fields)
    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">{{ $labels[$gateway] ?? ucfirst($gateway) }}</h2>
        <div class="space-y-4">
            @foreach($fields as $keyType => $field)
            <div>
                <label class="form-label">{{ $fieldLabels[$keyType] ?? $keyType }}</label>
                @if($field['is_secret'])
                    <input type="text" name="{{ $gateway }}[{{ $keyType }}]" value="{{ old("{$gateway}.{$keyType}") }}"
                           placeholder="{{ $field['display'] }}" autocomplete="off" class="form-input">
                @else
                    <input type="text" name="{{ $gateway }}[{{ $keyType }}]" value="{{ old("{$gateway}.{$keyType}", $field['display']) }}"
                           autocomplete="off" class="form-input">
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <button type="submit" class="btn btn-primary">Save Payment Settings</button>
</form>
@endsection
