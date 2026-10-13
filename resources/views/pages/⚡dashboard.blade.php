<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Dashboard')] class extends Component
{
    public ?string $todaySummary = null;

    #[Computed]
    public function projectCount(): int
    {
        return Project::active()->count();
    }

    #[Computed]
    public function openTaskCount(): int
    {
        return $this->trackedTasks()->where('status', '!=', TaskStatus::Done->value)->count();
    }

    #[Computed]
    public function overdueTaskCount(): int
    {
        return $this->trackedTasks()->overdue()->count();
    }

    #[Computed]
    public function completedTaskCount(): int
    {
        return $this->trackedTasks()->where('status', TaskStatus::Done->value)->count();
    }

    #[Computed]
    public function dueSoonTasks()
    {
        return $this->trackedTasks()
            ->dueSoon()
            ->with('project')
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function favoriteProjects()
    {
        return Project::favorite()->active()->orderBy('name')->get();
    }

    /**
     * Build a plain-language summary of today's task activity and store it for display.
     * Generated entirely from local data — no external service involved.
     */
    public function generateTodaySummary(): void
    {
        $this->todaySummary = $this->buildTodaySummary();
    }

    /**
     * Clear a previously generated summary once the underlying tasks change, so a
     * stale report is never shown as current.
     */
    #[On('task-updated')]
    public function invalidateTodaySummary(): void
    {
        $this->todaySummary = null;
    }

    private function buildTodaySummary(): string
    {
        $today = now()->startOfDay();

        $dueToday = $this->trackedTasks()
            ->whereDate('due_date', $today)
            ->where('status', '!=', TaskStatus::Done->value)
            ->get()
            ->sortByDesc(fn (Task $task): int => $task->priority->weight())
            ->values();

        $overdue = $this->trackedTasks()->overdue()->orderBy('due_date')->get();

        $completedToday = $this->trackedTasks()
            ->whereDate('completed_at', $today)
            ->get();

        $inProgressCount = $this->trackedTasks()
            ->where('status', TaskStatus::InProgress->value)
            ->count();

        $sentences = [
            "Good {$this->dayPart()}! Here's where things stand today.",
        ];

        $sentences[] = $dueToday->isNotEmpty()
            ? $this->describeTasks('due today', $dueToday->pluck('title'))
            : 'Nothing is due today.';

        if ($overdue->isNotEmpty()) {
            $sentences[] = $this->describeTasks('overdue and need attention', $overdue->pluck('title'));
        }

        if ($completedToday->isNotEmpty()) {
            $sentences[] = $this->describeTasks('completed today', $completedToday->pluck('title'));
        }

        if ($inProgressCount > 0) {
            $sentences[] = $inProgressCount === 1
                ? 'There is 1 task currently in progress.'
                : "There are {$inProgressCount} tasks currently in progress.";
        }

        if ($dueToday->isEmpty() && $overdue->isEmpty() && $completedToday->isEmpty() && $inProgressCount === 0) {
            $sentences[] = "Nothing needs your attention right now — enjoy the calm!";
        } elseif ($dueToday->isEmpty() && $overdue->isEmpty()) {
            $sentences[] = "You're all caught up for today. Nice work!";
        }

        return implode(' ', $sentences);
    }

    /**
     * Render a "N tasks are {verbPhrase}: A, B and C." sentence, capping the named
     * tasks so the summary stays readable when a list is long.
     *
     * @param  Collection<int, string>  $titles
     */
    private function describeTasks(string $verbPhrase, Collection $titles): string
    {
        $count = $titles->count();
        $noun = $count === 1 ? 'task is' : 'tasks are';
        $list = $this->toSentenceList($titles->take(5)->all(), $count);

        return "{$count} {$noun} {$verbPhrase}: {$list}.";
    }

    /**
     * Join a capped list of items into a natural-language sentence fragment,
     * e.g. "A, B and C" or "A, B, C, D and 2 more" when items were truncated.
     *
     * @param  list<string>  $shown
     */
    private function toSentenceList(array $shown, int $totalCount): string
    {
        $remaining = $totalCount - count($shown);

        if ($remaining > 0) {
            return implode(', ', $shown).", and {$remaining} more";
        }

        if (count($shown) === 1) {
            return $shown[0];
        }

        $last = array_pop($shown);

        return implode(', ', $shown).' and '.$last;
    }

    /**
     * Tasks that count toward the dashboard: those in a project that is
     * neither trashed nor archived.
     *
     * @return Builder<Task>
     */
    private function trackedTasks(): Builder
    {
        return Task::query()->whereHas('project', fn (Builder $query) => $query->active());
    }

    private function dayPart(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 12 => 'morning',
            $hour < 17 => 'afternoon',
            default => 'evening',
        };
    }
};
?>

<div class="space-y-8">
    <x-ui.page-header
        title="Dashboard"
        description="A quick overview of everything you're tracking."
        icon="squares-2x2"
    />

    @php
        $stats = [
            ['label' => 'Active Projects', 'value' => $this->projectCount, 'icon' => 'rectangle-stack', 'tile' => 'from-indigo-500 to-violet-500 shadow-indigo-500/30', 'valueClass' => ''],
            ['label' => 'Open Tasks', 'value' => $this->openTaskCount, 'icon' => 'clipboard-document-list', 'tile' => 'from-sky-500 to-blue-500 shadow-sky-500/30', 'valueClass' => ''],
            ['label' => 'Overdue', 'value' => $this->overdueTaskCount, 'icon' => 'exclamation-triangle', 'tile' => 'from-rose-500 to-red-500 shadow-rose-500/30', 'valueClass' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Completed', 'value' => $this->completedTaskCount, 'icon' => 'check-badge', 'tile' => 'from-emerald-500 to-teal-500 shadow-emerald-500/30', 'valueClass' => 'text-emerald-600 dark:text-emerald-400'],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div
                wire:key="stat-{{ $loop->index }}"
                class="card group relative animate-fade-in-up overflow-hidden p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/5"
                style="animation-delay: {{ $loop->index * 70 }}ms;"
            >
                <div class="relative flex items-start justify-between gap-3">
                    <div>
                        <p class="text-muted text-xs font-medium tracking-wide uppercase">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums {{ $stat['valueClass'] }}">{{ $stat['value'] }}</p>
                    </div>
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 {{ $stat['tile'] }}">
                        <x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="size-5" />
                    </span>
                </div>
                <div class="pointer-events-none absolute -right-8 -bottom-10 size-28 rounded-full bg-linear-to-br opacity-10 blur-2xl transition-opacity duration-300 group-hover:opacity-25 {{ $stat['tile'] }}"></div>
            </div>
        @endforeach
    </div>

    <div class="card relative overflow-hidden p-5">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-indigo-500/60 to-transparent"></div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2.5 text-sm font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-linear-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-orange-500/25">
                    <x-heroicon-s-sparkles class="size-4" />
                </span>
                Today's Summary
            </h2>
            <button
                type="button"
                wire:click="generateTodaySummary"
                wire:loading.attr="disabled"
                wire:target="generateTodaySummary"
                class="btn-primary"
            >
                <x-heroicon-o-sparkles wire:loading.remove wire:target="generateTodaySummary" class="size-4" />
                <x-heroicon-o-arrow-path wire:loading wire:target="generateTodaySummary" class="size-4 animate-spin" />
                {{ $todaySummary ? 'Regenerate' : 'Generate Summary' }}
            </button>
        </div>

        @if ($todaySummary)
            <p wire:key="summary-{{ md5($todaySummary) }}" class="animate-fade-in-up rounded-xl bg-linear-to-br from-indigo-50 to-violet-50 p-4 text-sm leading-relaxed text-zinc-700 dark:from-indigo-500/10 dark:to-violet-500/5 dark:text-zinc-300">{{ $todaySummary }}</p>
        @else
            <p class="text-muted text-sm">Click "Generate Summary" for a quick rundown of what's due, overdue, and completed today.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-4 flex items-center gap-2.5 text-sm font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400">
                    <x-heroicon-o-clock class="size-4" />
                </span>
                Due Soon
            </h2>

            @if ($this->dueSoonTasks->isEmpty())
                <x-ui.empty-state icon="face-smile" title="Nothing due in the next 7 days. Nice work!" />
            @else
                <ul class="-mx-2 space-y-1">
                    @foreach ($this->dueSoonTasks as $task)
                        <li
                            wire:key="due-soon-{{ $task->id }}"
                            class="flex animate-fade-in-up items-center justify-between gap-3 rounded-xl px-2 py-2.5 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                            style="animation-delay: {{ $loop->index * 40 }}ms;"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="size-2.5 shrink-0 rounded-full ring-4 ring-white dark:ring-zinc-900" style="background-color: {{ $task->project->color }};"></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                                    <a href="{{ route('projects.show', $task->project) }}" wire:navigate class="text-muted text-xs transition hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $task->project->name }}
                                    </a>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <span class="hidden sm:block"><x-ui.priority-badge :priority="$task->priority" /></span>
                                <x-ui.due-date-badge :task="$task" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card flex flex-col p-5">
            <h2 class="mb-4 flex items-center gap-2.5 text-sm font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400">
                    <x-heroicon-s-star class="size-4" />
                </span>
                Favorite Projects
            </h2>

            @if ($this->favoriteProjects->isEmpty())
                <x-ui.empty-state icon="star" title="Star a project to pin it here." class="flex-1" />
            @else
                <ul class="-mx-2 space-y-1">
                    @foreach ($this->favoriteProjects as $project)
                        <li wire:key="fav-project-{{ $project->id }}">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="group flex items-center gap-3 rounded-xl p-2 transition hover:bg-zinc-50 dark:hover:bg-white/5">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl text-white shadow-md transition-transform duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, {{ $project->color }}, {{ $project->color }}b3);">
                                    <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="size-4" />
                                </span>
                                <span class="flex-1 truncate text-sm font-medium">{{ $project->name }}</span>
                                <x-heroicon-o-chevron-right class="size-4 -translate-x-1 text-zinc-400 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('projects.index') }}" wire:navigate class="group mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                View all projects
                <x-heroicon-o-arrow-right class="size-3.5 transition-transform duration-200 group-hover:translate-x-1" />
            </a>
        </div>
    </div>
</div>
