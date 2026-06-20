@extends('layouts.auth')
@section('title', 'Sign In')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Welcome back</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">Sign in to your Kloud101 account</p>

@if(session('success'))
<div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

@if(session('error') || $errors->any())
<div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        @if(session('error'))
            <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
        @endif
        @foreach($errors->all() as $error)
            <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
        @endforeach
    </div>
</div>
@endif

<form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf

    <div class="space-y-5">
        {{-- Email --}}
        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email"
                   name="email"
                   type="email"
                   autocomplete="email"
                   value="{{ old('email') }}"
                   required
                   class="form-input {{ $errors->has('email') ? 'border-red-400 dark:border-red-500' : '' }}"
                   placeholder="you@example.com">
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="form-label">Password</label>
            <div class="relative" x-data="{ show: false }">
                <input id="password"
                       name="password"
                       :type="show ? 'text' : 'password'"
                       autocomplete="current-password"
                       required
                       class="form-input pr-10"
                       placeholder="••••••••">
                <button type="button"
                        @click="show = !show"
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

        {{-- Remember me / Forgot password --}}
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" id="remember"
                       class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-600 dark:text-slate-400">Remember me for 30 days</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                Forgot password?
            </a>
        </div>

        {{-- Submit --}}
        <button type="submit"
                :disabled="loading"
                class="btn btn-primary w-full py-2.5 text-base">
            <span x-show="!loading">Sign in to your account</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Signing in…
            </span>
        </button>
    </div>
</form>

{{-- Divider --}}
<div class="flex items-center gap-3 my-6">
    <div class="h-px flex-1 bg-slate-200 dark:bg-white/[0.08]"></div>
    <span class="text-xs text-slate-400 dark:text-slate-500">or continue with</span>
    <div class="h-px flex-1 bg-slate-200 dark:bg-white/[0.08]"></div>
</div>

{{-- Social login --}}
<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('social.redirect', 'google') }}"
       class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08]
              bg-white dark:bg-white/[0.03] text-sm font-semibold text-slate-700 dark:text-slate-200
              hover:bg-slate-50 dark:hover:bg-white/[0.06] transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v2.97h3.86c2.26-2.09 3.56-5.17 3.56-8.79z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-2.97c-1.07.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.27v3.07C3.26 21.3 7.31 24 12 24z"/>
            <path fill="#FBBC05" d="M5.27 14.32A7.18 7.18 0 014.9 12c0-.81.14-1.6.37-2.32V6.61H1.27A11.96 11.96 0 000 12c0 1.93.46 3.76 1.27 5.39l4-3.07z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.27 6.61l4 3.07C6.22 6.86 8.87 4.75 12 4.75z"/>
        </svg>
        Google
    </a>
    <a href="{{ route('social.redirect', 'facebook') }}"
       class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08]
              bg-white dark:bg-white/[0.03] text-sm font-semibold text-slate-700 dark:text-slate-200
              hover:bg-slate-50 dark:hover:bg-white/[0.06] transition-colors">
        <svg class="w-4 h-4" fill="#1877F2" viewBox="0 0 24 24">
            <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.02 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.95h-1.51c-1.49 0-1.95.93-1.95 1.89v2.27h3.32l-.53 3.49h-2.79v8.44C19.61 23.09 24 18.09 24 12.07z"/>
        </svg>
        Facebook
    </a>
</div>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    Don't have an account?
    <a href="{{ route('register') }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">Create one now</a>
</p>
@endsection
