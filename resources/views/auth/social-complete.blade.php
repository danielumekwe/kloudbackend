@extends('layouts.auth')
@section('title', 'Complete Your Account')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Almost there, {{ $pending['firstname'] }}</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">
    We just need a few more billing details to finish setting up your Kloud101 account.
</p>

@if($errors->any())
<div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
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

<div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]">
    <div>
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $pending['firstname'] }} {{ $pending['lastname'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $pending['email'] }}</p>
    </div>
</div>

<form method="POST" action="{{ route('social.complete.store') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf

    <div class="space-y-4">
        {{-- Phone --}}
        <div>
            <label for="phonenumber" class="form-label">Phone number</label>
            <input id="phonenumber" name="phonenumber" type="tel" autocomplete="tel"
                   value="{{ old('phonenumber') }}" required class="form-input" placeholder="+1 555 000 0000">
        </div>

        {{-- Address --}}
        <div>
            <label for="address1" class="form-label">Street address</label>
            <input id="address1" name="address1" type="text" autocomplete="street-address"
                   value="{{ old('address1') }}" required class="form-input" placeholder="123 Main St">
        </div>

        {{-- City / State / Postcode --}}
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label for="city" class="form-label">City</label>
                <input id="city" name="city" type="text" autocomplete="address-level2"
                       value="{{ old('city') }}" required class="form-input" placeholder="New York">
            </div>
            <div>
                <label for="state" class="form-label">State</label>
                <input id="state" name="state" type="text" autocomplete="address-level1"
                       value="{{ old('state') }}" required class="form-input" placeholder="NY">
            </div>
            <div>
                <label for="postcode" class="form-label">Postcode</label>
                <input id="postcode" name="postcode" type="text" autocomplete="postal-code"
                       value="{{ old('postcode') }}" required class="form-input" placeholder="10001">
            </div>
        </div>

        {{-- Country --}}
        <div>
            <label for="country" class="form-label">Country code</label>
            <input id="country" name="country" type="text" autocomplete="country"
                   value="{{ old('country', 'US') }}" required maxlength="2"
                   class="form-input uppercase" placeholder="US">
            <p class="mt-1 text-xs text-slate-400">2-letter ISO country code (e.g. US, GB, CA)</p>
        </div>

        {{-- Submit --}}
        <button type="submit" :disabled="loading" class="btn btn-primary w-full py-2.5 text-base mt-2">
            <span x-show="!loading">Finish creating account</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Creating account…
            </span>
        </button>
    </div>
</form>
@endsection
