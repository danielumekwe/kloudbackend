@extends('layouts.admin')
@section('title', 'New Invoice')
@section('breadcrumb')
    <a href="{{ route('admin.invoices.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Invoices</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">New Invoice</span>
@endsection

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">New Invoice</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Create a standalone invoice for any client, billed in Naira.</p>

@if($errors->any())
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

<form method="GET" action="{{ route('admin.invoices.create') }}" class="card max-w-lg mb-6">
    <label class="form-label">Find client</label>
    <div class="flex gap-2">
        <input type="text" name="q" value="{{ $query }}" placeholder="Search by name or email" class="form-input flex-1">
        <button type="submit" class="btn btn-secondary">Search</button>
    </div>
</form>

<form method="POST" action="{{ route('admin.invoices.store') }}" class="card max-w-lg space-y-5">
    @csrf

    <div>
        <label class="form-label">Client</label>
        @if($clients->isEmpty())
            <p class="text-sm text-slate-400">{{ $query !== '' ? 'No clients match "' . $query . '".' : 'Search for a client above to select them.' }}</p>
        @else
            <div class="space-y-2">
                @foreach($clients as $c)
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="client_id" value="{{ $c->id }}" required {{ (string) old('client_id') === (string) $c->id ? 'checked' : '' }}>
                    <span class="text-slate-700 dark:text-slate-300">{{ $c->firstname }} {{ $c->lastname }} &middot; {{ $c->email }}</span>
                </label>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <label for="description" class="form-label">Description</label>
        <input id="description" name="description" type="text" value="{{ old('description') }}" required maxlength="255" class="form-input" placeholder="e.g. Custom VPS setup fee">
    </div>

    <div>
        <label for="amount" class="form-label">Amount (&#8358;)</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required class="form-input" placeholder="0.00">
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Create Invoice</button>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
