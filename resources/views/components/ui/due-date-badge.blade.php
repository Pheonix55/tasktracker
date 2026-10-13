@props(['task'])

@if ($task->due_date)
    @php $isToday = $task->due_date->isToday(); @endphp

    <span
        {{ $attributes->class([
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
            'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20' => $task->isOverdue,
            'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20' => ! $task->isOverdue && $isToday,
            'bg-zinc-50 text-zinc-600 ring-zinc-200 dark:bg-white/5 dark:text-zinc-300 dark:ring-white/10' => ! $task->isOverdue && ! $isToday,
        ]) }}
    >
        @if ($task->isOverdue)
            <span class="relative flex size-1.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
            </span>
        @else
            <x-heroicon-m-calendar class="size-3" />
        @endif
        {{ $isToday ? 'Today' : $task->due_date->format('M j') }}
    </span>
@endif
