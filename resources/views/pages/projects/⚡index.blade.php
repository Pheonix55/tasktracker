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
    <x-ui.page-header
        title="Projects"
        description="Everything you're working on, organized into boards."
        icon="rectangle-stack"
    >
        <x-slot:actions>
            <button type="button" wire:click="$dispatch('open-project-form')" class="btn-primary data-loading:opacity-70">
                <x-heroicon-o-plus class="size-4" />
                New Project
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap items-center gap-4">
        <div class="relative max-w-sm min-w-[220px] flex-1">
            <x-heroicon-o-magnifying-glass wire:loading.remove wire:target="search" class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
            <x-heroicon-o-arrow-path wire:loading wire:target="search" class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 animate-spin text-indigo-500" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search projects..."
                class="input-field pl-10"
            >
        </div>

        <label class="inline-flex cursor-pointer items-center gap-3 text-sm font-medium text-zinc-600 select-none dark:text-zinc-400">
            <span class="relative inline-flex">
                <input type="checkbox" wire:model.live="showArchived" class="peer sr-only">
                <span class="h-6 w-11 rounded-full bg-zinc-200 transition-colors duration-200 peer-checked:bg-linear-to-r peer-checked:from-indigo-500 peer-checked:to-violet-500 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500/50 peer-data-loading:opacity-50 dark:bg-zinc-700"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-md transition-transform duration-200 peer-checked:translate-x-5"></span>
            </span>
            Show archived
        </label>
    </div>

    <div wire:loading.remove wire:target="search, showArchived">
        @if ($this->projects->isEmpty())
            <div class="card border-dashed">
                <x-ui.empty-state icon="folder-plus" title="No projects found." description="Create your first one to get started.">
                    <button type="button" wire:click="$dispatch('open-project-form')" class="btn-primary mt-2">
                        <x-heroicon-o-plus class="size-4" />
                        New Project
                    </button>
                </x-ui.empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->projects as $project)
                    @php $completion = $project->completionPercentage(); @endphp
                    <div
                        wire:key="project-{{ $project->id }}"
                        x-data="{ navigating: false }"
                        class="card group relative flex animate-fade-in-up flex-col gap-4 overflow-hidden p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/5 dark:hover:border-white/20"
                        style="animation-delay: {{ min($loop->index, 12) * 50 }}ms;"
                    >
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 opacity-80 transition-opacity group-hover:opacity-100" style="background: linear-gradient(90deg, {{ $project->color }}, {{ $project->color }}33);"></div>

                        <a
                            href="{{ route('projects.show', $project) }}"
                            wire:navigate
                            @click="navigating = true"
                            class="absolute inset-0 z-0 rounded-2xl"
                            aria-label="Open {{ $project->name }}"
                        ></a>

                        <div
                            x-show="navigating"
                            x-cloak
                            class="absolute inset-0 z-20 flex items-center justify-center rounded-2xl bg-white/70 backdrop-blur-[2px] dark:bg-zinc-900/70"
                        >
                            <x-heroicon-o-arrow-path class="size-6 animate-spin text-indigo-500" />
                        </div>

                        <div class="pointer-events-none relative z-10 flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3" style="background: linear-gradient(135deg, {{ $project->color }}, {{ $project->color }}b3); box-shadow: 0 8px 20px -6px {{ $project->color }}80;">
                                    <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="size-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate font-semibold tracking-tight">{{ $project->name }}</span>
                                        @if ($project->is_archived)
                                            <span class="shrink-0 rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-white/10 dark:text-zinc-400">Archived</span>
                                        @endif
                                    </span>
                                    <span class="text-muted block text-xs">{{ $project->tasks_count }} {{ $project->tasks_count === 1 ? 'task' : 'tasks' }}</span>
                                </span>
                            </div>

                            <button
                                type="button"
                                wire:click="toggleFavorite({{ $project->id }})"
                                aria-label="{{ $project->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}"
                                title="{{ $project->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}"
                                class="btn-icon pointer-events-auto relative z-10 size-8 shrink-0 hover:text-amber-500 data-loading:pointer-events-none data-loading:opacity-50"
                            >
                                @if ($project->is_favorite)
                                    <x-heroicon-s-star class="size-5 text-amber-400 drop-shadow-[0_0_6px_rgba(251,191,36,0.5)]" />
                                @else
                                    <x-heroicon-o-star class="size-5" />
                                @endif
                            </button>
                        </div>

                        @if ($project->description)
                            <p class="text-muted pointer-events-none relative z-10 line-clamp-2 text-sm">{{ $project->description }}</p>
                        @endif

                        <div class="pointer-events-none relative z-10 mt-auto space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted">Progress</span>
                                <span class="font-semibold tabular-nums">{{ $completion }}% complete</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                                <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: {{ $completion }}%; background: linear-gradient(90deg, {{ $project->color }}b3, {{ $project->color }});"></div>
                            </div>
                        </div>

                        <div class="relative z-10 -mb-1 flex items-center justify-end gap-1 border-t border-zinc-100 pt-3 dark:border-white/5">
                            <button type="button" wire:click="$dispatch('open-project-form', { projectId: {{ $project->id }} })" aria-label="Edit project" title="Edit" class="btn-icon size-8 data-loading:pointer-events-none data-loading:opacity-50">
                                <x-heroicon-o-pencil-square class="size-4" />
                            </button>
                            <button type="button" wire:click="toggleArchive({{ $project->id }})" aria-label="{{ $project->is_archived ? 'Unarchive project' : 'Archive project' }}" title="{{ $project->is_archived ? 'Unarchive' : 'Archive' }}" class="btn-icon size-8 data-loading:pointer-events-none data-loading:opacity-50">
                                @if ($project->is_archived)
                                    <x-heroicon-o-archive-box-arrow-down class="size-4" />
                                @else
                                    <x-heroicon-o-archive-box class="size-4" />
                                @endif
                            </button>
                            <button type="button" wire:click="moveToTrash({{ $project->id }})" wire:confirm="Move this project to the trash?" aria-label="Move project to trash" title="Move to trash" class="btn-icon size-8 hover:bg-red-50 hover:text-red-600 data-loading:pointer-events-none data-loading:opacity-50 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <x-heroicon-o-trash class="size-4" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div wire:loading.grid wire:target="search, showArchived" class="hidden grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.skeleton-project-card />
        <x-ui.skeleton-project-card />
        <x-ui.skeleton-project-card />
    </div>

    <livewire:projects.form-modal />
</div>
