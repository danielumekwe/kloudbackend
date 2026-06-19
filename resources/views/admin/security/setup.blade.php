@extends('layouts.admin')
@section('title', 'Set Up Two-Factor Authentication')
@section('breadcrumb', 'Security')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Set up two-factor authentication</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Scan the QR code below with your authenticator app, then enter the 6-digit code it generates.</p>

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

<div class="card max-w-xl">
    <div class="flex justify-center p-4 bg-white rounded-xl mb-4">
        {!! $qrSvg !!}
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Can't scan it? Enter this key manually:</p>
    <p class="font-mono text-sm px-3 py-2 rounded-lg bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.06] text-slate-700 dark:text-slate-200 mb-6 break-all">
        {{ $secret }}
    </p>

    <form method="POST" action="{{ route('admin.security.two-factor.confirm') }}">
        @csrf
        <div class="space-y-5">
            <div>
                <label for="code" class="form-label">Authentication code</label>
                <input id="code"
                       name="code"
                       type="text"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       required
                       autofocus
                       class="form-input text-center text-lg tracking-widest"
                       placeholder="123456">
            </div>

            <button type="submit" class="btn btn-primary w-full py-2.5 text-base">Confirm and enable</button>
        </div>
    </form>
</div>

<p class="mt-6">
    <a href="{{ route('admin.security') }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">Cancel</a>
</p>
@endsection
