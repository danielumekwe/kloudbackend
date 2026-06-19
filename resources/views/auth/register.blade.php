@extends('layouts.auth')
@section('title', 'Create Account')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Create your account</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">Get started with Kloud101 today</p>

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

<form method="POST" action="{{ route('register') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf

    <div class="space-y-4">
        {{-- Name row --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="firstname" class="form-label">First name</label>
                <input id="firstname" name="firstname" type="text" autocomplete="given-name"
                       value="{{ old('firstname') }}" required class="form-input" placeholder="John">
            </div>
            <div>
                <label for="lastname" class="form-label">Last name</label>
                <input id="lastname" name="lastname" type="text" autocomplete="family-name"
                       value="{{ old('lastname') }}" required class="form-input" placeholder="Doe">
            </div>
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email"
                   value="{{ old('email') }}" required class="form-input" placeholder="you@example.com">
        </div>

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

        {{-- Password --}}
        <div x-data="{ show: false }">
            <label for="password" class="form-label">Password</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'"
                       required minlength="8" class="form-input pr-10" placeholder="At least 8 characters">
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   required minlength="8" class="form-input" placeholder="Repeat your password">
        </div>

        {{-- Submit --}}
        <button type="submit" :disabled="loading" class="btn btn-primary w-full py-2.5 text-base mt-2">
            <span x-show="!loading">Create account</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Creating account…
            </span>
        </button>
    </div>
</form>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    Already have an account?
    <a href="{{ route('login') }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">Sign in</a>
</p>
@endsection
