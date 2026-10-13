@props([
    'title',
    'description' => null,
    'icon' => null,
    'gradient' => 'from-indigo-500 to-violet-500 shadow-indigo-500/30',
])

<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-4']) }}>
    <div class="flex items-center gap-4">
        @if ($icon)
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-lg {{ $gradient }}">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
            </span>
        @endif

        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="text-muted mt-0.5 text-sm">{{ $description }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
