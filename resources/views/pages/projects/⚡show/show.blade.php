<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <span
                class="flex items-center justify-center w-11 h-11 rounded-lg text-white shrink-0"
                style="background-color: {{ $project->color }};"
            >
                <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="w-6 h-6" />
            </span>
            <div>
                <h1 class="text-xl font-semibold flex items-center gap-2">
                    {{ $project->name }}
                    @if ($project->is_archived)
                        <span class="text-xs font-normal px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Archived</span>
                    @endif
                </h1>
                @if ($project->description)
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-0.5">{{ $project->description }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="toggleFavorite" class="p-2 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:text-current data-loading:opacity-50 data-loading:pointer-events-none">
                @if ($project->is_favorite)
                    <x-heroicon-s-star class="w-5 h-5 text-amber-500" />
                @else
                    <x-heroicon-o-star class="w-5 h-5" />
                @endif
            </button>

            <button type="button" wire:click="$dispatch('open-project-form', { projectId: {{ $project->id }} })" class="p-2 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:text-current data-loading:opacity-50 data-loading:pointer-events-none">
                <x-heroicon-o-pencil-square class="w-5 h-5" />
            </button>

            <button type="button" wire:click="toggleArchive" class="p-2 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:text-current data-loading:opacity-50 data-loading:pointer-events-none">
                <x-heroicon-o-archive-box class="w-5 h-5" />
            </button>

            <button type="button" wire:click="moveToTrash" wire:confirm="Move this project to the trash? Its tasks will remain but the project will be hidden." class="p-2 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] text-red-600 dark:text-red-400 data-loading:opacity-50 data-loading:pointer-events-none">
                <x-heroicon-o-trash class="w-5 h-5" />
            </button>
        </div>
    </div>

    @if ($this->availableTags->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <span class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Filter by tag:</span>
            @foreach ($this->availableTags as $tag)
                @php $active = in_array($tag->id, $tagFilter, true); @endphp
                <button
                    type="button"
                    wire:click="toggleTagFilter({{ $tag->id }})"
                    wire:loading.attr="disabled"
                    wire:target="toggleTagFilter({{ $tag->id }})"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border data-loading:opacity-50"
                    @style([
                        "background-color: {$tag->color}22; color: {$tag->color}; border-color: {$tag->color}55;" => $active,
                        'border-color: #e3e3e0; color: #706f6c;' => ! $active,
                    ])
                >
                    <x-heroicon-o-arrow-path wire:loading wire:target="toggleTagFilter({{ $tag->id }})" class="w-3 h-3 animate-spin" />
                    {{ $tag->name }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
        @foreach ($this->columns as $status)
            @php
                $tasks = $this->tasksByStatus->get($status->value, collect());
                $dotClass = match ($status) {
                    \App\Enums\TaskStatus::Todo => 'bg-slate-500',
                    \App\Enums\TaskStatus::InProgress => 'bg-blue-500',
                    \App\Enums\TaskStatus::InReview => 'bg-amber-500',
                    \App\Enums\TaskStatus::Done => 'bg-emerald-500',
                };
            @endphp
            <div class="rounded-lg bg-[#f5f5f4] dark:bg-[#111110] p-3 min-h-[200px]">
                <div class="flex items-center justify-between mb-3 px-1">
                    <h2 class="text-sm font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                        {{ $status->label() }}
                    </h2>
                    <span class="text-xs text-[#a1a09a]">{{ $tasks->count() }}</span>
                </div>

                <div wire:loading.remove wire:target="toggleTagFilter">
                    <ul
                        wire:sort="handleSort"
                        wire:sort:group="tasks"
                        wire:sort:group-id="{{ $status->value }}"
                        class="space-y-2 min-h-[40px]"
                    >
                        @foreach ($tasks as $task)
                            <li wire:key="task-{{ $task->id }}" wire:sort:item="{{ $task->id }}">
                                <x-kanban.task-card :task="$task" />
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div wire:loading.block wire:target="toggleTagFilter" class="hidden space-y-2">
                    <x-ui.skeleton-task-card />
                    <x-ui.skeleton-task-card />
                </div>

                <form wire:submit="quickAdd('{{ $status->value }}')" class="relative mt-2">
                    <input
                        type="text"
                        wire:model="newTaskTitle.{{ $status->value }}"
                        placeholder="+ Add task"
                        class="w-full rounded-md border border-transparent bg-transparent px-2 py-1.5 text-sm placeholder:text-[#a1a09a] hover:border-[#e3e3e0] dark:hover:border-[#3E3E3A] focus:outline-none focus:bg-white dark:focus:bg-[#161615] focus:border-[#e3e3e0] dark:focus:border-[#3E3E3A] data-loading:opacity-50"
                    >
                    <x-heroicon-o-arrow-path wire:loading wire:target="quickAdd('{{ $status->value }}')" class="w-3.5 h-3.5 animate-spin absolute right-2 top-1/2 -translate-y-1/2 text-[#a1a09a]" />
                </form>
            </div>
        @endforeach
    </div>

    <livewire:tasks.detail-modal />
    <livewire:projects.form-modal />
</div>
