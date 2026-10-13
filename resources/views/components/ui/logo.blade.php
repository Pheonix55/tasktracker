@props(['size' => 'md', 'wordmark' => true])

@php
    [$iconClass, $textClass] = match ($size) {
        'sm' => ['size-8', 'text-base'],
        'lg' => ['size-12', 'text-2xl'],
        default => ['size-10', 'text-lg'],
    };
@endphp

<span {{ $attributes->class(['group inline-flex items-center gap-2.5']) }}>
    <img
        src="{{ asset('images/logo-icon.png') }}"
        alt="{{ $wordmark ? '' : 'Task Tracker' }}"
        @class([
            $iconClass,
            'shrink-0 drop-shadow-[0_6px_12px_rgba(10,99,245,0.35)] transition-transform duration-300 group-hover:scale-105 group-hover:-rotate-6',
        ])
    >

    @if ($wordmark)
        <span @class([$textClass, 'font-semibold tracking-tight'])>
            <span class="text-brand-navy dark:text-brand-ink-dark">Task</span>
            <span class="text-brand-blue dark:text-brand-blue-dark">Tracker</span>
        </span>
    @endif
</span>
