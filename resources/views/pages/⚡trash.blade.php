<?php

use App\Models\Project;
use App\Models\Task;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Trash')] class extends Component
{
    #[Computed]
    public function trashedProjects()
    {
        return Project::onlyTrashed()->orderByDesc('deleted_at')->get();
    }

    #[Computed]
    public function trashedTasks()
    {
        return Task::onlyTrashed()
            ->with(['project' => fn ($query) => $query->withTrashed()])
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function restoreProject(int $projectId): void
    {
        Project::onlyTrashed()->findOrFail($projectId)->restore();
        unset($this->trashedProjects);
    }

    public function forceDeleteProject(int $projectId): void
    {
        Project::onlyTrashed()->findOrFail($projectId)->forceDelete();
        unset($this->trashedProjects);
    }

    /**
     * Restore a trashed task. A task whose project is also in the trash can't be
     * restored on its own, since it would stay hidden until the project comes back.
     */
    public function restoreTask(int $taskId): void
    {
        $task = Task::onlyTrashed()
            ->with(['project' => fn ($query) => $query->withTrashed()])
            ->findOrFail($taskId);

        if ($task->project?->trashed()) {
            return;
        }

        $task->restore();
        unset($this->trashedTasks);
    }

    public function forceDeleteTask(int $taskId): void
    {
        Task::onlyTrashed()->findOrFail($taskId)->forceDelete();
        unset($this->trashedTasks);
    }
};
?>

<div class="space-y-8">
    <x-ui.page-header
        title="Trash"
        description="Restore something you deleted, or remove it for good."
        icon="trash"
        gradient="from-zinc-500 to-zinc-700 shadow-zinc-500/30"
    />

    <section class="space-y-3">
        <h2 class="flex items-center gap-2.5 text-sm font-semibold">
            <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                <x-heroicon-o-rectangle-stack class="size-4" />
            </span>
            Projects
            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 tabular-nums dark:bg-white/10 dark:text-zinc-400">{{ $this->trashedProjects->count() }}</span>
        </h2>

        <ul class="card divide-y divide-zinc-100 overflow-hidden dark:divide-white/5">
            @forelse ($this->trashedProjects as $project)
                <li wire:key="trashed-project-{{ $project->id }}" class="flex animate-fade-in-up flex-wrap items-center gap-3 px-5 py-3.5 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03]">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-white opacity-70 grayscale-[30%]" style="background: linear-gradient(135deg, {{ $project->color }}, {{ $project->color }}b3);">
                        <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $project->name }}</p>
                        <p class="text-muted flex items-center gap-1 text-xs">
                            <x-heroicon-m-clock class="size-3" />
                            Deleted {{ $project->deleted_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="restoreProject({{ $project->id }})" class="btn-secondary px-3 py-1.5 data-loading:pointer-events-none data-loading:opacity-50">
                            <x-heroicon-o-arrow-uturn-left class="size-3.5" />
                            Restore
                        </button>
                        <button type="button" wire:click="forceDeleteProject({{ $project->id }})" wire:confirm="Permanently delete this project and all of its tasks? This cannot be undone." class="btn-secondary border-red-200 px-3 py-1.5 text-red-600 hover:border-red-300 hover:bg-red-50 data-loading:pointer-events-none data-loading:opacity-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10">
                            <x-heroicon-o-trash class="size-3.5" />
                            Delete Permanently
                        </button>
                    </div>
                </li>
            @empty
                <li>
                    <x-ui.empty-state icon="sparkles" title="No trashed projects." />
                </li>
            @endforelse
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="flex items-center gap-2.5 text-sm font-semibold">
            <span class="flex size-8 items-center justify-center rounded-lg bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400">
                <x-heroicon-o-clipboard-document-check class="size-4" />
            </span>
            Tasks
            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 tabular-nums dark:bg-white/10 dark:text-zinc-400">{{ $this->trashedTasks->count() }}</span>
        </h2>

        <ul class="card divide-y divide-zinc-100 overflow-hidden dark:divide-white/5">
            @forelse ($this->trashedTasks as $task)
                <li wire:key="trashed-task-{{ $task->id }}" class="flex animate-fade-in-up flex-wrap items-center gap-3 px-5 py-3.5 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03]">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-white/5 dark:text-zinc-500">
                        <x-heroicon-o-document-text class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                        <p class="text-muted flex flex-wrap items-center gap-1 text-xs">
                            @if ($task->project)
                                <span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                            @endif
                            {{ $task->project?->name ?? 'Deleted project' }}{{ $task->project?->trashed() ? ' (in trash)' : '' }} &middot; Deleted {{ $task->deleted_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($task->project?->trashed())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200 ring-inset dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                <x-heroicon-m-information-circle class="size-3.5" />
                                Restore its project first
                            </span>
                        @else
                            <button type="button" wire:click="restoreTask({{ $task->id }})" class="btn-secondary px-3 py-1.5 data-loading:pointer-events-none data-loading:opacity-50">
                                <x-heroicon-o-arrow-uturn-left class="size-3.5" />
                                Restore
                            </button>
                        @endif
                        <button type="button" wire:click="forceDeleteTask({{ $task->id }})" wire:confirm="Permanently delete this task? This cannot be undone." class="btn-secondary border-red-200 px-3 py-1.5 text-red-600 hover:border-red-300 hover:bg-red-50 data-loading:pointer-events-none data-loading:opacity-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10">
                            <x-heroicon-o-trash class="size-3.5" />
                            Delete Permanently
                        </button>
                    </div>
                </li>
            @empty
                <li>
                    <x-ui.empty-state icon="sparkles" title="No trashed tasks." />
                </li>
            @endforelse
        </ul>
    </section>
</div>
