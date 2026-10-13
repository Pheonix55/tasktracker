@props(['priority'])

@php
    $classes = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-500/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/20',
        'red' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
    ][$priority->color()];

    $icon = match ($priority) {
        \App\Enums\TaskPriority::Low => 'chevron-down',
        \App\Enums\TaskPriority::Medium => 'minus',
        \App\Enums\TaskPriority::High => 'chevron-up',
        \App\Enums\TaskPriority::Urgent => 'fire',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $classes]) }}>
    <x-dynamic-component :component="'heroicon-m-'.$icon" class="size-3" />
    {{ $priority->label() }}
</span>
