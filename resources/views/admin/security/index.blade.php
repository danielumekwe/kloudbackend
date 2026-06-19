@extends('layouts.admin')
@section('title', 'Security')
@section('breadcrumb', 'Security')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Security</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Two-factor authentication for your own admin account.</p>

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

@if($freshRecoveryCodes)
<div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2">Save these recovery codes now — they won't be shown again</p>
    <p class="text-xs text-amber-700 dark:text-amber-400 mb-3">Each code can be used once to sign in if you lose access to your authenticator app.</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-sm">
        @foreach($freshRecoveryCodes as $code)
            <div class="px-3 py-2 rounded-lg bg-white dark:bg-black/20 border border-amber-200 dark:border-amber-500/20 text-slate-700 dark:text-slate-200">{{ $code }}</div>
        @endforeach
    </div>
</div>
@endif

<div class="card max-w-xl">
    @if($admin->hasTwoFactorEnabled())
        <div class="flex items-start gap-3 mb-4">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Enabled
            </span>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
            Two-factor authentication is protecting your account. Regenerate your recovery codes if you've used some up, or disable 2FA if you're switching devices.
        </p>

        <form method="POST" action="{{ route('admin.security.two-factor.recovery-codes') }}" class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-slate-100 dark:border-white/[0.05]" x-data="{ show: false }">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Current password</label>
                <input type="password" name="password" required class="form-input" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-secondary">Regenerate recovery codes</button>
        </form>

        <form method="POST" action="{{ route('admin.security.two-factor.disable') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Current password</label>
                <input type="password" name="password" required class="form-input" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-danger">Disable 2FA</button>
        </form>
    @else
        <div class="flex items-start gap-3 mb-4">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-400">
                Not enabled
            </span>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
            Add an extra layer of security to your admin account using an authenticator app (Google Authenticator, Authy, 1Password, etc).
        </p>
        <a href="{{ route('admin.security.two-factor.setup') }}" class="btn btn-primary inline-flex">Enable two-factor authentication</a>
    @endif
</div>
@endsection
