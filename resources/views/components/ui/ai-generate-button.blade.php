@props([
    'action',
    'hasText' => false,
])

<button
    type="button"
    wire:click="{{ $action }}"
    wire:loading.attr="disabled"
    wire:target="{{ $action }}"
    {{ $attributes->class(['mb-1.5 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50 data-loading:opacity-60 dark:text-indigo-400 dark:hover:bg-indigo-500/10']) }}
>
    <x-heroicon-o-sparkles wire:loading.remove wire:target="{{ $action }}" class="size-3.5" />
    <x-heroicon-o-arrow-path wire:loading wire:target="{{ $action }}" class="size-3.5 animate-spin" />
    {{ $hasText ? 'Improve with AI' : 'Generate with AI' }}
</button>
