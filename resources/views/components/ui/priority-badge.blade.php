@props(['priority'])

@php
    $color = $priority->color();
    $classes = [
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ][$color];
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium", $classes]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $priority->label() }}
</span>
