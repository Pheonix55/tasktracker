@props(['message' => null])

@if ($message)
    <p {{ $attributes->class(['mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400']) }}>
        <x-heroicon-m-exclamation-circle class="size-3.5 shrink-0" />
        <span>{{ $message }} <a href="{{ route('settings') }}" wire:navigate class="font-medium underline">Open Settings</a></span>
    </p>
@endif
