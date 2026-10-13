@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-6 py-12 text-center']) }}>
    <span class="flex size-14 items-center justify-center rounded-2xl bg-linear-to-br from-zinc-100 to-zinc-200/60 text-zinc-400 ring-1 ring-zinc-200 dark:from-white/10 dark:to-white/5 dark:text-zinc-500 dark:ring-white/10">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-7" />
    </span>
    <div>
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $title }}</p>
        @if ($description)
            <p class="text-muted mt-1 text-sm">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
