@props(['tag', 'removable' => false])

<span
    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
    style="background-color: {{ $tag->color }}1a; color: {{ $tag->color }}; --tw-ring-color: {{ $tag->color }}40;"
>
    <span class="size-1.5 rounded-full" style="background-color: {{ $tag->color }};"></span>
    {{ $tag->name }}

    @if ($removable)
        <button type="button" {{ $attributes }} class="transition hover:opacity-70">
            <x-heroicon-o-x-mark class="size-3" />
        </button>
    @endif
</span>
