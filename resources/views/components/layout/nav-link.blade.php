@props(['route', 'active' => false])

<a
    href="{{ $route }}"
    wire:navigate
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
        'bg-linear-to-r from-indigo-500/10 to-violet-500/5 text-indigo-700 dark:from-indigo-500/20 dark:to-violet-500/5 dark:text-indigo-300' => $active,
        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-zinc-100' => ! $active,
    ]) }}
>
    @if ($active)
        <span class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-linear-to-b from-indigo-500 to-violet-500"></span>
    @endif

    {{ $slot }}
</a>
