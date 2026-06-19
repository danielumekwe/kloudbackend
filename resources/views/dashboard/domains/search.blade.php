@extends('layouts.app')
@section('title', 'Register a Domain')
@section('breadcrumb')
    <a href="{{ route('domains.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Domains</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Register a Domain</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Register a Domain</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Search for a domain name to check availability and pricing.</p>
</div>

<div x-data="domainSearch()">
    <div class="card mb-6">
        <div class="flex gap-3">
            <input type="text" x-model="query" @keyup.enter="lookup()" placeholder="example.com" class="form-input flex-1">
            <button @click="lookup()" :disabled="searching || !query" class="btn btn-primary">
                <span x-show="!searching">Search</span>
                <span x-show="searching">Searching…</span>
            </button>
        </div>
        <div x-show="error" class="text-sm text-red-600 dark:text-red-400 mt-3" x-text="error"></div>
    </div>

    <template x-if="result">
        <div class="card">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white" x-text="query"></h3>
                    <p class="text-sm mt-0.5" :class="result.available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                       x-text="result.available ? 'Available' : 'Not available'"></p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xl font-bold text-slate-900 dark:text-white" x-show="result.available">
                        {{ $currency }}<span x-text="result.price?.toFixed(2)"></span>/yr
                    </span>
                    <a :href="result.available ? `{{ route('domains.catalog') }}?domain=${encodeURIComponent(query)}` : '#'"
                       class="btn btn-primary"
                       :class="!result.available ? 'opacity-50 pointer-events-none' : ''">
                        Register
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function domainSearch() {
    return {
        query: '',
        result: null,
        searching: false,
        error: '',

        async lookup() {
            if (!this.query) return;
            this.searching = true;
            this.error = '';
            this.result = null;
            try {
                const res = await fetch('{{ route('domains.lookup') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ domain: this.query }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.result = data;
                } else {
                    this.error = data.error || 'Could not look up this domain.';
                }
            } catch (e) {
                this.error = 'Could not reach the server. Please try again.';
            } finally {
                this.searching = false;
            }
        },
    };
}
</script>
@endpush
