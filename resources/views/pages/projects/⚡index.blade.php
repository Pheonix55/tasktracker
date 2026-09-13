<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Projects')] class extends Component
{
    #[Url]
    public string $search = '';

    public bool $showArchived = false;

    #[Computed]
    public function projects()
    {
        return Project::query()
            ->when(! $this->showArchived, fn ($query) => $query->active())
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->withCount('tasks')
            ->orderByDesc('is_favorite')
            ->orderBy('name')
            ->get();
    }

    public function toggleFavorite(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $project->update(['is_favorite' => ! $project->is_favorite]);
    }

    public function toggleArchive(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $project->update(['is_archived' => ! $project->is_archived]);
    }

    public function moveToTrash(int $projectId): void
    {
        Project::findOrFail($projectId)->delete();
    }

    #[On('project-saved')]
    public function refresh(): void
    {
        unset($this->projects);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Projects</h1>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Everything you're working on, organized into boards.</p>
        </div>

        <button
            type="button"
            wire:click="$dispatch('open-project-form')"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70"
        >
            <x-heroicon-o-plus class="w-4 h-4" />
            New Project
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px] max-w-sm">
            <x-heroicon-o-magnifying-glass wire:loading.remove wire:target="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#a1a09a]" />
            <x-heroicon-o-arrow-path wire:loading wire:target="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#a1a09a] animate-spin" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search projects..."
                class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
            >
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
            <input type="checkbox" wire:model.live="showArchived" class="peer rounded border-[#e3e3e0] dark:border-[#3E3E3A] text-[#F53003] focus:ring-[#F53003]/40 data-loading:opacity-50">
            <span class="peer-data-loading:opacity-50">Show archived</span>
        </label>
    </div>

    <div wire:loading.remove wire:target="search, showArchived">
        @if ($this->projects->isEmpty())
            <div class="rounded-lg border border-dashed border-[#e3e3e0] dark:border-[#3E3E3A] p-10 text-center">
                <p class="text-sm text-[#a1a09a]">No projects found. Create your first one to get started.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->projects as $project)
                <div
                    wire:key="project-{{ $project->id }}"
                    x-data="{ navigating: false }"
                    class="relative rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4 bg-white dark:bg-[#161615] flex flex-col gap-3 transition-colors hover:border-[#19140035] dark:hover:border-[#62605b]"
                >
                    <a
                        href="{{ route('projects.show', $project) }}"
                        wire:navigate
                        @click="navigating = true"
                        class="absolute inset-0 z-0 rounded-lg"
                        aria-label="Open {{ $project->name }}"
                    ></a>

                    <div
                        x-show="navigating"
                        x-cloak
                        class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/70 dark:bg-[#161615]/70 backdrop-blur-[1px]"
                    >
                        <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-[#F53003] dark:text-[#FF4433]" />
                    </div>

                    <div class="relative z-10 flex items-start justify-between gap-2 pointer-events-none">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex items-center justify-center w-10 h-10 rounded-lg text-white shrink-0" style="background-color: {{ $project->color }};">
                                <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="w-5 h-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-medium truncate">{{ $project->name }}</span>
                                <span class="block text-xs text-[#a1a09a]">{{ $project->tasks_count }} {{ $project->tasks_count === 1 ? 'task' : 'tasks' }}</span>
                            </span>
                        </div>

                        <button
                            type="button"
                            wire:click="toggleFavorite({{ $project->id }})"
                            class="relative z-10 shrink-0 pointer-events-auto text-[#a1a09a] hover:text-amber-500 data-loading:opacity-50 data-loading:pointer-events-none"
                        >
                            @if ($project->is_favorite)
                                <x-heroicon-s-star class="w-5 h-5 text-amber-500" />
                            @else
                                <x-heroicon-o-star class="w-5 h-5" />
                            @endif
                        </button>
                    </div>

                    @if ($project->description)
                        <p class="relative z-10 pointer-events-none text-sm text-[#706f6c] dark:text-[#A1A09A] line-clamp-2">{{ $project->description }}</p>
                    @endif

                    <div class="relative z-10 pointer-events-none w-full h-1.5 rounded-full bg-[#f0f0ef] dark:bg-[#232322] overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $project->completionPercentage() }}%; background-color: {{ $project->color }};"></div>
                    </div>

                    <div class="relative z-10 flex items-center justify-between pt-1 pointer-events-none">
                        <span class="text-xs text-[#a1a09a]">{{ $project->completionPercentage() }}% complete</span>

                        <div class="flex items-center gap-1 pointer-events-auto">
                            <button type="button" wire:click="$dispatch('open-project-form', { projectId: {{ $project->id }} })" class="relative z-10 p-1.5 rounded-md text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a] data-loading:opacity-50 data-loading:pointer-events-none">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                            </button>
                            <button type="button" wire:click="toggleArchive({{ $project->id }})" class="relative z-10 p-1.5 rounded-md text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a] data-loading:opacity-50 data-loading:pointer-events-none">
                                <x-heroicon-o-archive-box class="w-4 h-4" />
                            </button>
                            <button type="button" wire:click="moveToTrash({{ $project->id }})" wire:confirm="Move this project to the trash?" class="relative z-10 p-1.5 rounded-md text-red-600 dark:text-red-400 hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a] data-loading:opacity-50 data-loading:pointer-events-none">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </div>

    <div wire:loading.grid wire:target="search, showArchived" class="hidden grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-ui.skeleton-project-card />
        <x-ui.skeleton-project-card />
        <x-ui.skeleton-project-card />
    </div>

    <livewire:projects.form-modal />
</div>
