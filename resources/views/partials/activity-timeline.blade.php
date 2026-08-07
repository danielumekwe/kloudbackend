{{--
    Shared activity timeline partial — used on admin server detail (all entries)
    and customer VPS/Dedicated show pages (client-visible entries only).
    Expects: $activity (Collection of App\Models\ActivityLog), newest first.
--}}
@if($activity->isEmpty())
<p class="text-sm text-slate-500 dark:text-slate-400 py-4">No activity recorded yet.</p>
@else
<div class="space-y-0">
    @foreach($activity as $entry)
    <div class="flex gap-3 pb-4 {{ ! $loop->last ? 'border-l border-slate-200 dark:border-white/[0.08] ml-1.5 pl-4 -mt-0.5' : 'ml-1.5 pl-4' }}">
        <div class="relative">
            <span class="absolute -left-[1.45rem] top-0.5 w-2.5 h-2.5 rounded-full flex-shrink-0
                {{ str_contains($entry->action, 'failed') ? 'bg-red-500' : (str_contains($entry->action, 'suspend') ? 'bg-amber-500' : 'bg-blue-500') }}"></span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-900 dark:text-white">{{ $entry->description }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                {{ $entry->causerName() }} &middot; {{ $entry->created_at->format('M j, Y g:i A') }}
            </p>
        </div>
    </div>
    @endforeach
</div>
@endif
