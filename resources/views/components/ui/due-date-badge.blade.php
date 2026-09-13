@props(['task'])

@if ($task->due_date)
    <span
        {{ $attributes->class([
            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium',
            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $task->isOverdue,
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => ! $task->isOverdue,
        ]) }}
    >
        <x-heroicon-o-calendar class="w-3 h-3" />
        {{ $task->due_date->isToday() ? 'Today' : $task->due_date->format('M j') }}
    </span>
@endif
