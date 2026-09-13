@props(['task'])

<div
    wire:click="$dispatch('task-selected', { taskId: {{ $task->id }} })"
    class="group relative cursor-pointer rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-3 shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] hover:border-[#19140035] dark:hover:border-[#62605b] transition-colors data-loading:opacity-60 data-loading:pointer-events-none"
>
    <div class="hidden group-data-loading:flex absolute inset-0 items-center justify-center rounded-lg bg-white/60 dark:bg-[#161615]/60">
        <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin text-[#F53003] dark:text-[#FF4433]" />
    </div>

    <p class="text-sm font-medium mb-2 leading-snug">{{ $task->title }}</p>

    <div class="flex flex-wrap items-center gap-1.5 mb-2">
        <x-ui.priority-badge :priority="$task->priority" />
        <x-ui.due-date-badge :task="$task" />
    </div>

    @if ($task->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($task->tags as $tag)
                <x-ui.tag-chip :tag="$tag" wire:key="task-{{ $task->id }}-tag-{{ $tag->id }}" />
            @endforeach
        </div>
    @endif

    <x-ui.checklist-progress :task="$task" />
</div>
