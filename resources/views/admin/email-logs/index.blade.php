@extends('layouts.admin')
@section('title', 'Email Logs')
@section('breadcrumb', 'Email Logs')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Email Logs</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Every email sent by the platform, with resend.</p>
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

<div class="card mb-6">
    <form method="GET" action="{{ route('admin.email-logs.index') }}" class="flex items-center gap-3">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search by recipient or subject…" class="form-input text-sm flex-1">
        <button type="submit" class="btn btn-primary text-sm py-1.5">Search</button>
    </form>
</div>

@if($logs->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No emails found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">Nothing has been sent yet, or no results match your search.</p>
</div>
@else
<div class="card !p-0 overflow-hidden">
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Sent</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-sm text-slate-700 dark:text-slate-300">{{ $log->to_email }}</td>
                    <td class="text-sm text-slate-900 dark:text-white">{{ $log->subject }}</td>
                    <td class="text-xs text-slate-400 font-mono">{{ class_basename($log->mailable_class ?? '') ?: '—' }}</td>
                    <td class="text-sm text-slate-500 dark:text-slate-400">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                    <td>
                        @if($log->body_html)
                        <form method="POST" action="{{ route('admin.email-logs.resend', $log) }}"
                              onsubmit="return confirm('Resend this email to {{ $log->to_email }}?')">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Resend</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $logs->links() }}
</div>
@endif
@endsection
