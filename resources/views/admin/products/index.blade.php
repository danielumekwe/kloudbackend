@extends('layouts.admin')
@section('title', 'Products')
@section('breadcrumb', 'Products')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Products</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
        Edit each product's name, description, and optional per-currency/per-cycle price overrides.
        For the engine-wide markup %/discount knobs these fall back to, see
        <a href="{{ route('admin.pricing') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Pricing</a>.
    </p>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

{{-- Type tabs --}}
<div class="flex gap-1 mb-6 border-b border-slate-200 dark:border-white/[0.06]">
    @foreach(['vps' => 'VPS', 'qs' => 'Quick Servers', 'ssl' => 'SSL', 'domain' => 'Domains'] as $t => $label)
    <a href="{{ route('admin.products.index', $t) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors
              {{ $type === $t
                    ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                    : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

@if($type === 'domain')
<div class="card mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-3">Add a TLD override</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">
        Domain TLDs aren't an enumerable catalog (pricing is looked up live per domain), so only TLDs
        you've customized appear below. Type a TLD to add or edit one.
    </p>
    <form onsubmit="event.preventDefault(); var v=this.tld.value.trim().toLowerCase().replace(/^\.+/, ''); if(v) window.location = '{{ url('/admin/products/domain') }}/' + encodeURIComponent(v) + '/edit';"
          class="flex items-center gap-3">
        <input type="text" name="tld" placeholder="com" class="form-input w-40" required>
        <button type="submit" class="btn btn-primary text-sm">Edit TLD</button>
    </form>
</div>
@endif

<div class="card !p-0 overflow-hidden">
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Key</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td class="font-medium text-slate-900 dark:text-white">{{ $item['label'] }}</td>
                    <td class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $item['key'] }}</td>
                    <td>
                        @if($item['product']?->is_retired)
                            <span class="badge badge-closed">Retired</span>
                        @elseif($item['product']?->is_hidden)
                            <span class="badge badge-answered">Hidden</span>
                        @else
                            <span class="badge badge-active">Active</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.products.edit', [$type, $item['key']]) }}"
                           class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Edit →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-8 text-sm text-slate-400">
                        @if($type === 'domain')
                            No TLD overrides yet — add one above.
                        @else
                            No {{ $type }} items found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
