@extends('layouts.app')
@section('title', \Illuminate\Support\Str::title($title))
@section('breadcrumb', \Illuminate\Support\Str::title($title))

@section('content')
<div class="card text-center py-20">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">{{ \Illuminate\Support\Str::title($title) }} is coming soon</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
        We're still wiring this category up. In the meantime, open a support ticket and we'll help you get set up manually.
    </p>
    <a href="{{ route('support.create') }}" class="btn btn-primary mt-6 inline-flex">Contact Support</a>
</div>
@endsection
