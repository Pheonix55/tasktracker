<div>
    <div class="card relative mb-6 overflow-hidden p-5">
        <div class="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full opacity-20 blur-3xl" style="background-color: {{ $project->color }};"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, {{ $project->color }}, {{ $project->color }}22);"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <span
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg"
                    style="background: linear-gradient(135deg, {{ $project->color }}, {{ $project->color }}b3); box-shadow: 0 10px 25px -8px {{ $project->color }}99;"
                >
                    <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="size-7" />
                </span>
                <div class="min-w-0">
                    <div class="text-muted mb-0.5 flex items-center gap-1 text-xs">
                        <a href="{{ route('projects.index') }}" wire:navigate class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Projects</a>
                        <x-heroicon-m-chevron-right class="size-3" />
                        <span class="truncate">Board</span>
                    </div>
                    <h1 class="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                        <span class="truncate">{{ $project->name }}</span>
                        @if ($project->is_archived)
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                <x-heroicon-m-archive-box class="size-3" />
                                Archived
                            </span>
                        @endif
                    </h1>
                    @if ($project->description)
                        <p class="text-muted mt-0.5 text-sm">{{ $project->description }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="toggleFavorite" aria-label="{{ $project->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}" title="{{ $project->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}" class="btn-secondary px-2.5 hover:text-amber-500 data-loading:pointer-events-none data-loading:opacity-50">
                    @if ($project->is_favorite)
                        <x-heroicon-s-star class="size-5 text-amber-400" />
                    @else
                        <x-heroicon-o-star class="size-5" />
                    @endif
                </button>

                <button type="button" wire:click="$dispatch('open-project-form', { projectId: {{ $project->id }} })" aria-label="Edit project" title="Edit" class="btn-secondary px-2.5 data-loading:pointer-events-none data-loading:opacity-50">
                    <x-heroicon-o-pencil-square class="size-5" />
                </button>

                <button type="button" wire:click="toggleArchive" aria-label="{{ $project->is_archived ? 'Unarchive project' : 'Archive project' }}" title="{{ $project->is_archived ? 'Unarchive' : 'Archive' }}" class="btn-secondary px-2.5 data-loading:pointer-events-none data-loading:opacity-50">
                    @if ($project->is_archived)
                        <x-heroicon-o-archive-box-arrow-down class="size-5" />
                    @else
                        <x-heroicon-o-archive-box class="size-5" />
                    @endif
                </button>

                <button type="button" wire:click="moveToTrash" wire:confirm="Move this project to the trash? Its tasks will remain but the project will be hidden." aria-label="Move project to trash" title="Move to trash" class="btn-secondary px-2.5 text-red-600 hover:border-red-200 hover:bg-red-50 data-loading:pointer-events-none data-loading:opacity-50 dark:text-red-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10">
                    <x-heroicon-o-trash class="size-5" />
                </button>
            </div>
        </div>
    </div>

    {{-- Everything the tag/priority filters and drag-sort touch lives in one island. The
         triggers below carry wire:island="board" so a real click only fetches/re-renders
         this island, not the whole page; `always: true` keeps it included in a normal
         full render too (e.g. from the header actions, or in tests), so it never goes
         stale. --}}
    @island(name: 'board', always: true)
        <div class="card mb-6 space-y-3 p-4">
            @if ($this->availableTags->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-muted mr-1 inline-flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                        <x-heroicon-m-tag class="size-3.5" />
                        Tags
                    </span>
                    @foreach ($this->availableTags as $tag)
                        @php $active = in_array($tag->id, $tagFilter, true); @endphp
                        <button
                            type="button"
                            wire:click="toggleTagFilter({{ $tag->id }})"
                            wire:island="board"
                            wire:loading.attr="disabled"
                            wire:target="toggleTagFilter({{ $tag->id }})"
                            @class([
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-all duration-200 hover:scale-105 active:scale-95 data-loading:opacity-50',
                                'shadow-sm' => $active,
                                'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-white/10 dark:text-zinc-400 dark:hover:border-white/20' => ! $active,
                            ])
                            @style([
                                "background-color: {$tag->color}1f; color: {$tag->color}; border-color: {$tag->color}66;" => $active,
                            ])
                        >
                            <x-heroicon-o-arrow-path wire:loading wire:target="toggleTagFilter({{ $tag->id }})" class="size-3 animate-spin" />
                            <span wire:loading.remove wire:target="toggleTagFilter({{ $tag->id }})" class="size-1.5 rounded-full" style="background-color: {{ $tag->color }};"></span>
                            {{ $tag->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-muted mr-1 inline-flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                    <x-heroicon-m-funnel class="size-3.5" />
                    Priority
                </span>
                @foreach ($this->priorityOptions as $priority)
                    @php
                        $active = in_array($priority->value, $priorityFilter, true);
                        $colorClasses = [
                            'slate' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-500/15 dark:text-slate-300 dark:border-slate-500/40',
                            'blue' => 'bg-blue-50 text-blue-700 border-blue-300 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/40',
                            'orange' => 'bg-orange-50 text-orange-700 border-orange-300 dark:bg-orange-500/15 dark:text-orange-300 dark:border-orange-500/40',
                            'red' => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-500/15 dark:text-red-300 dark:border-red-500/40',
                        ][$priority->color()];
                    @endphp
                    <button
                        type="button"
                        wire:click="togglePriorityFilter('{{ $priority->value }}')"
                        wire:island="board"
                        wire:loading.attr="disabled"
                        wire:target="togglePriorityFilter('{{ $priority->value }}')"
                        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition-all duration-200 hover:scale-105 active:scale-95 data-loading:opacity-50 {{ $active ? $colorClasses.' shadow-sm' : 'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-white/10 dark:text-zinc-400 dark:hover:border-white/20' }}"
                    >
                        <x-heroicon-o-arrow-path wire:loading wire:target="togglePriorityFilter('{{ $priority->value }}')" class="size-3 animate-spin" />
                        {{ $priority->label() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->columns as $status)
                @php
                    $tasks = $this->tasksByStatus->get($status->value, collect());
                    [$dotClass, $accentClass, $icon] = match ($status) {
                        \App\Enums\TaskStatus::Todo => ['bg-slate-400', 'from-slate-400', 'stop-circle'],
                        \App\Enums\TaskStatus::InProgress => ['bg-blue-500', 'from-blue-500', 'play-circle'],
                        \App\Enums\TaskStatus::InReview => ['bg-amber-500', 'from-amber-500', 'eye'],
                        \App\Enums\TaskStatus::Done => ['bg-emerald-500', 'from-emerald-500', 'check-circle'],
                    };
                @endphp
                <div
                    class="relative min-h-[220px] animate-fade-in-up overflow-hidden rounded-2xl border border-zinc-200/70 bg-zinc-100/60 p-3 backdrop-blur-sm dark:border-white/5 dark:bg-white/[0.03]"
                    style="animation-delay: {{ $loop->index * 70 }}ms;"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-linear-to-r to-transparent {{ $accentClass }}"></div>

                    <div class="mb-3 flex items-center justify-between px-1">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <span class="relative flex size-2">
                                @if ($status === \App\Enums\TaskStatus::InProgress && $tasks->isNotEmpty())
                                    <span class="absolute inline-flex size-full animate-ping rounded-full opacity-60 {{ $dotClass }}"></span>
                                @endif
                                <span class="relative inline-flex size-2 rounded-full {{ $dotClass }}"></span>
                            </span>
                            {{ $status->label() }}
                        </h2>
                        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-zinc-600 tabular-nums shadow-sm dark:bg-white/10 dark:text-zinc-300">{{ $tasks->count() }}</span>
                    </div>

                    <div wire:loading.remove wire:target="toggleTagFilter,togglePriorityFilter">
                        <ul
                            wire:sort="handleSort"
                            wire:island="board"
                            wire:sort:group="tasks"
                            wire:sort:group-id="{{ $status->value }}"
                            class="min-h-[48px] space-y-2.5"
                        >
                            @foreach ($tasks as $task)
                                <li wire:key="task-{{ $task->id }}" wire:sort:item="{{ $task->id }}">
                                    <x-kanban.task-card :task="$task" />
                                </li>
                            @endforeach
                        </ul>

                        @if ($tasks->isEmpty())
                            <div class="text-muted -mt-12 flex h-12 items-center justify-center gap-1.5 rounded-xl border border-dashed border-zinc-300 text-xs pointer-events-none dark:border-white/10">
                                <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" />
                                Drop tasks here
                            </div>
                        @endif
                    </div>

                    <div wire:loading.block wire:target="toggleTagFilter,togglePriorityFilter" class="hidden space-y-2.5">
                        <x-ui.skeleton-task-card />
                        <x-ui.skeleton-task-card />
                    </div>

                    <form wire:submit="quickAdd('{{ $status->value }}')" wire:island="board" class="relative mt-2.5">
                        <x-heroicon-o-plus class="pointer-events-none absolute top-2.5 left-2.5 size-4 text-zinc-400" />
                        <textarea
                            wire:model="newTaskTitle.{{ $status->value }}"
                            wire:keydown.enter.prevent="quickAdd('{{ $status->value }}')"
                            wire:island="board"
                            rows="1"
                            placeholder="Add task (comma-separated for multiple)"
                            class="w-full resize-none rounded-xl border border-transparent bg-transparent py-2 pr-8 pl-8 text-sm transition placeholder:text-zinc-400 hover:border-zinc-200 hover:bg-white/60 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:outline-none data-loading:opacity-50 dark:hover:border-white/10 dark:hover:bg-white/5 dark:focus:border-indigo-500/50 dark:focus:bg-zinc-900"
                        ></textarea>
                        <x-heroicon-o-arrow-path wire:loading wire:target="quickAdd('{{ $status->value }}')" class="absolute top-1/2 right-2.5 size-4 -translate-y-1/2 animate-spin text-indigo-500" />
                    </form>
                </div>
            @endforeach
        </div>
    @endisland

    <div class="mt-8">
        <livewire:notes.board :project="$project" :key="'notes-'.$project->id" />
    </div>

    <livewire:tasks.detail-modal />
    <livewire:projects.form-modal />
</div>
