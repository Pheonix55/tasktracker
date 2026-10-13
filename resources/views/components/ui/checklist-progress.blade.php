@props(['task'])

@php
    $progress = $task->checklistProgress;
@endphp

@if ($progress['total'] > 0)
    @php
        $percent = (int) round($progress['completed'] / $progress['total'] * 100);
        $isComplete = $progress['completed'] === $progress['total'];
    @endphp

    <span {{ $attributes->class(['inline-flex items-center gap-2 text-xs font-medium', 'text-emerald-600 dark:text-emerald-400' => $isComplete, 'text-muted' => ! $isComplete]) }}>
        <x-heroicon-m-check-circle class="size-3.5" />
        <span class="h-1 w-12 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
            <span class="block h-full rounded-full bg-linear-to-r from-emerald-400 to-teal-500 transition-all duration-500" style="width: {{ $percent }}%;"></span>
        </span>
        {{ $progress['completed'] }}/{{ $progress['total'] }}
    </span>
@endif
