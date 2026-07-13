@extends('layouts.admin')
@section('title', 'Edit Product')
@section('breadcrumb', 'Products')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.products.index', $type) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">&larr; Back to {{ ucfirst($type) }}</a>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $defaultName }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 font-mono">{{ $type }} / {{ $key }}</p>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if($errors->any())
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

<div x-data="{ tab: 'details' }">
    {{-- Tabs --}}
    <div class="flex gap-1 mb-6 border-b border-slate-200 dark:border-white/[0.06]">
        <button @click="tab = 'details'"
                :class="tab === 'details' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors">
            Details
        </button>
        <button @click="tab = 'pricing'"
                :class="tab === 'pricing' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors">
            Pricing
        </button>
    </div>

    {{-- Details tab --}}
    <div x-show="tab === 'details'" class="card">
        <form method="POST" action="{{ route('admin.products.details', [$type, $key]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Name</label>
                <input type="text" name="name" value="{{ old('name', $defaultName) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Tagline</label>
                <input type="text" name="tagline" value="{{ old('tagline', $product?->tagline) }}" class="form-input" placeholder="Optional short description">
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-input">{{ old('description', $product?->description) }}</textarea>
            </div>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_hidden" value="1" @checked(old('is_hidden', $product?->is_hidden))>
                    Hidden (hide from order form)
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_retired" value="1" @checked(old('is_retired', $product?->is_retired))>
                    Retired (hide from admin lists)
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Save Details</button>
        </form>
    </div>

    {{-- Pricing tab --}}
    <div x-show="tab === 'pricing'" class="card">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            @if($type === 'vps')
                Set an explicit price per slice for a cycle/currency to override the computed
                <code class="text-xs">price_per_slice &times; cycle discount</code>. Leave a cell blank or
                disabled to keep using the computed price.
            @else
                Set an explicit price to override the live-cost markup formula for that currency/cycle.
                Leave a cell blank or disabled to keep tracking InterServer's live cost automatically.
            @endif
        </p>
        <form method="POST" action="{{ route('admin.products.pricing', [$type, $key]) }}">
            @csrf
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Currency</th>
                            @foreach($cycles as $cycle)
                            <th>{{ $cycle['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($currencies as $currency)
                        <tr>
                            <td class="font-medium text-slate-900 dark:text-white">{{ $currency }}</td>
                            @foreach($cycles as $cycle)
                            @php($row = $priceGrid[$currency][$cycle['months']] ?? null)
                            <td>
                                <div class="flex flex-col gap-1.5">
                                    <input type="number" step="0.01" min="0"
                                           name="prices[{{ $currency }}][{{ $cycle['months'] }}][price]"
                                           value="{{ old("prices.{$currency}.{$cycle['months']}.price", $row?->price) }}"
                                           class="form-input w-28 text-sm" placeholder="—">
                                    <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        <input type="checkbox"
                                               name="prices[{{ $currency }}][{{ $cycle['months'] }}][is_enabled]"
                                               value="1"
                                               @checked(old("prices.{$currency}.{$cycle['months']}.is_enabled", $row?->is_enabled))>
                                        Enable
                                    </label>
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save Pricing</button>
        </form>
    </div>
</div>
@endsection
