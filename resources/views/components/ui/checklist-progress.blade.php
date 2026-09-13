@props(['task'])

@php
    $progress = $task->checklistProgress;
@endphp

@if ($progress['total'] > 0)
    <span {{ $attributes->class(['inline-flex items-center gap-1 text-xs text-[#706f6c] dark:text-[#A1A09A]']) }}>
        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
        {{ $progress['completed'] }}/{{ $progress['total'] }}
    </span>
@endif
