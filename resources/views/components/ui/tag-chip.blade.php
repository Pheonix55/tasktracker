@props(['tag', 'removable' => false])

<span
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
    style="background-color: {{ $tag->color }}22; color: {{ $tag->color }};"
>
    {{ $tag->name }}

    @if ($removable)
        <button type="button" {{ $attributes }} class="hover:opacity-70">
            <x-heroicon-o-x-mark class="w-3 h-3" />
        </button>
    @endif
</span>
