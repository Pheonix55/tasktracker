@props(['route', 'active' => false])

<a
    href="{{ $route }}"
    wire:navigate
    {{ $attributes->class([
        'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors',
        'bg-[#fff2f2] dark:bg-[#1D0002] text-[#F53003] dark:text-[#FF4433]' => $active,
        'text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#f5f5f4] dark:hover:bg-[#161615]' => ! $active,
    ]) }}
>
    {{ $slot }}
</a>
