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
        return Task::onlyTrashed()->with('project')->orderByDesc('deleted_at')->get();
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

    public function restoreTask(int $taskId): void
    {
        Task::onlyTrashed()->findOrFail($taskId)->restore();
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
    <div>
        <h1 class="text-xl font-semibold">Trash</h1>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Restore something you deleted, or remove it for good.</p>
    </div>

    <section>
        <h2 class="text-sm font-semibold mb-3 flex items-center gap-2">
            <x-heroicon-o-rectangle-stack class="w-4 h-4" />
            Projects
        </h2>

        <ul class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A] rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]">
            @forelse ($this->trashedProjects as $project)
                <li wire:key="trashed-project-{{ $project->id }}" class="p-3 flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-md text-white shrink-0" style="background-color: {{ $project->color }};">
                        <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="w-4 h-4" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $project->name }}</p>
                        <p class="text-xs text-[#a1a09a]">Deleted {{ $project->deleted_at->diffForHumans() }}</p>
                    </div>
                    <button type="button" wire:click="restoreProject({{ $project->id }})" class="px-3 py-1.5 rounded-md text-sm border border-[#e3e3e0] dark:border-[#3E3E3A] inline-flex items-center gap-1 data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                        Restore
                    </button>
                    <button type="button" wire:click="forceDeleteProject({{ $project->id }})" wire:confirm="Permanently delete this project and all of its tasks? This cannot be undone." class="px-3 py-1.5 rounded-md text-sm text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/40 inline-flex items-center gap-1 data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                        Delete Permanently
                    </button>
                </li>
            @empty
                <li class="p-6 text-center text-sm text-[#a1a09a]">No trashed projects.</li>
            @endforelse
        </ul>
    </section>

    <section>
        <h2 class="text-sm font-semibold mb-3 flex items-center gap-2">
            <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
            Tasks
        </h2>

        <ul class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A] rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]">
            @forelse ($this->trashedTasks as $task)
                <li wire:key="trashed-task-{{ $task->id }}" class="p-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                        <p class="text-xs text-[#a1a09a]">
                            {{ $task->project?->name ?? 'Deleted project' }} &middot; Deleted {{ $task->deleted_at->diffForHumans() }}
                        </p>
                    </div>
                    <button type="button" wire:click="restoreTask({{ $task->id }})" class="px-3 py-1.5 rounded-md text-sm border border-[#e3e3e0] dark:border-[#3E3E3A] inline-flex items-center gap-1 data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                        Restore
                    </button>
                    <button type="button" wire:click="forceDeleteTask({{ $task->id }})" wire:confirm="Permanently delete this task? This cannot be undone." class="px-3 py-1.5 rounded-md text-sm text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/40 inline-flex items-center gap-1 data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                        Delete Permanently
                    </button>
                </li>
            @empty
                <li class="p-6 text-center text-sm text-[#a1a09a]">No trashed tasks.</li>
            @endforelse
        </ul>
    </section>
</div>
