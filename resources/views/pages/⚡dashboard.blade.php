<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
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
        return Task::where('status', '!=', TaskStatus::Done->value)->whereHas('project')->count();
    }

    #[Computed]
    public function overdueTaskCount(): int
    {
        return Task::overdue()->whereHas('project')->count();
    }

    #[Computed]
    public function completedTaskCount(): int
    {
        return Task::where('status', TaskStatus::Done->value)->whereHas('project')->count();
    }

    #[Computed]
    public function dueSoonTasks()
    {
        return Task::dueSoon()
            ->whereHas('project')
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

        $dueToday = Task::whereHas('project')
            ->whereDate('due_date', $today)
            ->where('status', '!=', TaskStatus::Done->value)
            ->orderByDesc('priority')
            ->get();

        $overdue = Task::overdue()->whereHas('project')->orderBy('due_date')->get();

        $completedToday = Task::whereHas('project')
            ->whereDate('completed_at', $today)
            ->get();

        $inProgressCount = Task::whereHas('project')
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
    <div>
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">A quick overview of everything you're tracking.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Active Projects</p>
            <p class="text-2xl font-semibold mt-1">{{ $this->projectCount }}</p>
        </div>
        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Open Tasks</p>
            <p class="text-2xl font-semibold mt-1">{{ $this->openTaskCount }}</p>
        </div>
        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Overdue</p>
            <p class="text-2xl font-semibold mt-1 text-red-600 dark:text-red-400">{{ $this->overdueTaskCount }}</p>
        </div>
        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Completed</p>
            <p class="text-2xl font-semibold mt-1 text-emerald-600 dark:text-emerald-400">{{ $this->completedTaskCount }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-sm font-semibold flex items-center gap-2">
                <x-heroicon-o-document-text class="w-4 h-4" />
                Today's Summary
            </h2>
            <button
                type="button"
                wire:click="generateTodaySummary"
                wire:loading.attr="disabled"
                wire:target="generateTodaySummary"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70"
            >
                <x-heroicon-o-arrow-path wire:loading wire:target="generateTodaySummary" class="w-3.5 h-3.5 animate-spin" />
                {{ $todaySummary ? 'Regenerate' : 'Generate Summary' }}
            </button>
        </div>

        @if ($todaySummary)
            <p class="text-sm leading-relaxed">{{ $todaySummary }}</p>
        @else
            <p class="text-sm text-[#a1a09a]">Click "Generate Summary" for a quick rundown of what's due, overdue, and completed today.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <h2 class="text-sm font-semibold mb-3 flex items-center gap-2">
                <x-heroicon-o-clock class="w-4 h-4" />
                Due Soon
            </h2>

            @if ($this->dueSoonTasks->isEmpty())
                <p class="text-sm text-[#a1a09a]">Nothing due in the next 7 days. Nice work!</p>
            @else
                <ul class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                    @foreach ($this->dueSoonTasks as $task)
                        <li wire:key="due-soon-{{ $task->id }}" class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                                <a href="{{ route('projects.show', $task->project) }}" wire:navigate class="text-xs text-[#706f6c] dark:text-[#A1A09A] hover:underline">
                                    {{ $task->project->name }}
                                </a>
                            </div>
                            <x-ui.due-date-badge :task="$task" class="shrink-0" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
            <h2 class="text-sm font-semibold mb-3 flex items-center gap-2">
                <x-heroicon-s-star class="w-4 h-4 text-amber-500" />
                Favorite Projects
            </h2>

            @if ($this->favoriteProjects->isEmpty())
                <p class="text-sm text-[#a1a09a]">Star a project to pin it here.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($this->favoriteProjects as $project)
                        <li wire:key="fav-project-{{ $project->id }}">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex items-center gap-2 rounded-md p-2 hover:bg-[#f5f5f4] dark:hover:bg-[#161615]">
                                <span class="flex items-center justify-center w-7 h-7 rounded-md text-white shrink-0" style="background-color: {{ $project->color }};">
                                    <x-dynamic-component :component="'heroicon-o-'.$project->icon" class="w-4 h-4" />
                                </span>
                                <span class="text-sm font-medium truncate">{{ $project->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('projects.index') }}" wire:navigate class="mt-4 inline-flex items-center gap-1 text-sm text-[#F53003] dark:text-[#FF4433] font-medium">
                View all projects
                <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
            </a>
        </div>
    </div>
</div>
