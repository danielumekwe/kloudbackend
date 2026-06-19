@extends('layouts.auth')
@section('title', 'Reset Password')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Choose a new password</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">Enter a new password for your account</p>

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

<form method="POST" action="{{ route('password.update') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="space-y-5">
        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email"
                   name="email"
                   type="email"
                   autocomplete="email"
                   value="{{ old('email', $email) }}"
                   required
                   class="form-input"
                   placeholder="you@example.com">
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="form-label">New password</label>
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

        <div>
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   required minlength="8" class="form-input" placeholder="Repeat your new password">
        </div>

        <button type="submit"
                :disabled="loading"
                class="btn btn-primary w-full py-2.5 text-base">
            <span x-show="!loading">Reset password</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Resetting…
            </span>
        </button>
    </div>
</form>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    <a href="{{ route('login') }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">Back to sign in</a>
</p>
@endsection
