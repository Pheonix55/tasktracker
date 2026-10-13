@props(['task'])

@php
    $isDone = $task->status === \App\Enums\TaskStatus::Done;
    $stripe = [
        'slate' => 'before:bg-slate-300 dark:before:bg-slate-600',
        'blue' => 'before:bg-blue-500',
        'orange' => 'before:bg-orange-500',
        'red' => 'before:bg-red-500',
    ][$task->priority->color()];
@endphp

<div
    wire:click="$dispatch('task-selected', { taskId: {{ $task->id }} })"
    @class([
        'group relative cursor-pointer overflow-hidden rounded-xl border border-zinc-200/80 bg-white p-3.5 pl-4 shadow-sm shadow-zinc-900/[0.04] transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md hover:shadow-indigo-500/10 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-indigo-500/40 data-loading:pointer-events-none data-loading:opacity-60',
        'before:absolute before:inset-y-3 before:left-0 before:w-1 before:rounded-r-full',
        $stripe,
    ])
>
    <div class="absolute inset-0 hidden items-center justify-center rounded-xl bg-white/60 group-data-loading:flex dark:bg-zinc-900/60">
        <x-heroicon-o-arrow-path class="size-4 animate-spin text-indigo-500" />
    </div>

    <p @class(['mb-2.5 text-sm leading-snug font-medium', 'text-zinc-400 line-through dark:text-zinc-500' => $isDone])>
        @if ($isDone)
            <x-heroicon-s-check-circle class="-mt-0.5 mr-0.5 inline size-4 text-emerald-500" />
        @endif
        {{ $task->title }}
    </p>

    <div class="mb-2 flex flex-wrap items-center gap-1.5">
        <x-ui.priority-badge :priority="$task->priority" />
        <x-ui.due-date-badge :task="$task" />
    </div>

    @if ($task->tags->isNotEmpty())
        <div class="mb-2 flex flex-wrap gap-1">
            @foreach ($task->tags as $tag)
                <x-ui.tag-chip :tag="$tag" wire:key="task-{{ $task->id }}-tag-{{ $tag->id }}" />
            @endforeach
        </div>
    @endif

    <x-ui.checklist-progress :task="$task" />
</div>
