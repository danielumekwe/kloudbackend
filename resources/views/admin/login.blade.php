@extends('layouts.auth')
@section('title', 'Admin Sign In')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Admin</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">Sign in to manage pricing</p>

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

<form method="POST" action="{{ route('admin.login') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf

    <div class="space-y-5">
        <div>
            <label for="password" class="form-label">Admin password</label>
            <input id="password"
                   name="password"
                   type="password"
                   autocomplete="current-password"
                   required
                   autofocus
                   class="form-input"
                   placeholder="••••••••">
        </div>

        <button type="submit"
                :disabled="loading"
                class="btn btn-primary w-full py-2.5 text-base">
            <span x-show="!loading">Sign in</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Signing in…
            </span>
        </button>
    </div>
</form>
@endsection
