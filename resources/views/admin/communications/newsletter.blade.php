@extends('layouts.admin')
@section('title', 'Newsletter')
@section('breadcrumb', 'Newsletter')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Newsletter</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Send an email broadcast to all verified clients.</p>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
</div>
@endif

<div class="card">
    <div class="flex items-center gap-3 mb-6 p-4 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            This will be sent to <strong>{{ $clientCount }}</strong> verified client{{ $clientCount !== 1 ? 's' : '' }}.
            Emails are queued — they will be sent in the background.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.communications.newsletter.send') }}" class="space-y-5"
          x-data="{ confirm: false }" @submit.prevent="confirm ? $el.submit() : (confirm = true)">
        @csrf

        <div>
            <label class="form-label">Subject</label>
            <input type="text" name="subject" value="{{ old('subject') }}"
                   class="form-input" required maxlength="200"
                   placeholder="e.g. New services now available at Kloud101">
            @error('subject')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label">Message</label>
            <textarea name="body" class="form-input" rows="12" required maxlength="10000"
                      placeholder="Write your newsletter content here. Plain text — line breaks are preserved.">{{ old('body') }}</textarea>
            @error('body')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    :class="confirm ? 'btn btn-danger' : 'btn btn-primary'"
                    x-text="confirm ? 'Confirm — send to {{ $clientCount }} clients' : 'Send Newsletter'">
            </button>
            <button x-show="confirm" type="button" @click="confirm = false"
                    class="btn btn-secondary text-sm">Cancel</button>
        </div>
    </form>
</div>
@endsection
