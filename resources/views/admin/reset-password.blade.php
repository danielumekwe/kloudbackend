@extends('layouts.auth')
@section('title', 'Reset Admin Password')

@section('content')
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Choose a new admin password</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-7">Enter a new password for the admin login</p>

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

<form method="POST" action="{{ route('admin.password.update') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="space-y-5">
        <div>
            <label for="email" class="form-label">Admin email</label>
            <input id="email"
                   name="email"
                   type="email"
                   autocomplete="username"
                   value="{{ old('email', $email) }}"
                   required
                   class="form-input"
                   placeholder="admin@example.com">
        </div>

        <div>
            <label for="password" class="form-label">New password</label>
            <input id="password" name="password" type="password"
                   required minlength="8" class="form-input" placeholder="At least 8 characters">
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
    <a href="{{ route('admin.login') }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">Back to admin sign in</a>
</p>
@endsection
