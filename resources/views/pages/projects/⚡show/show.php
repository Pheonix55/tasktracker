<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new
#[Layout('layouts::app')]
class extends Component
{
    public Project $project;

    /**
     * Tag ids used to filter the board. Empty means no filtering.
     *
     * @var list<int>
     */
    public array $tagFilter = [];

    /**
     * Priority values used to filter the board. Empty means no filtering.
     *
     * @var list<string>
     */
    public array $priorityFilter = [];

    /**
     * Per-column quick-add title input, keyed by status value.
     *
     * @var array<string, string>
     */
    public array $newTaskTitle = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render()
    {
        return $this->view()->title($this->project->name.' — Board');
    }

    #[Computed]
    public function columns(): array
    {
        return TaskStatus::cases();
    }

    #[Computed]
    public function availableTags()
    {
        return Tag::orderBy('name')->get();
    }

    /**
     * @return Collection<string, Collection<int, Task>>
     */
    #[Computed]
    public function tasksByStatus()
    {
        return $this->applyBoardFilters($this->project->tasks())
            ->with(['tags', 'checklistItems'])
            ->orderBy('position')
            ->get()
            ->groupBy(fn (Task $task) => $task->status->value);
    }

    /**
     * Restrict a task query to the tags and priorities currently selected on the board.
     *
     * @param  HasMany<Task, Project>  $query
     * @return HasMany<Task, Project>
     */
    private function applyBoardFilters(HasMany $query): HasMany
    {
        if (! empty($this->tagFilter)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $this->tagFilter));
        }

        if (! empty($this->priorityFilter)) {
            $query->whereIn('priority', $this->priorityFilter);
        }

        return $query;
    }

    #[Computed]
    public function priorityOptions(): array
    {
        return TaskPriority::cases();
    }

    public function toggleTagFilter(int $tagId): void
    {
        if (in_array($tagId, $this->tagFilter, true)) {
            $this->tagFilter = array_values(array_diff($this->tagFilter, [$tagId]));
        } else {
            $this->tagFilter[] = $tagId;
        }
    }

    public function togglePriorityFilter(string $priority): void
    {
        if (in_array($priority, $this->priorityFilter, true)) {
            $this->priorityFilter = array_values(array_diff($this->priorityFilter, [$priority]));
        } else {
            $this->priorityFilter[] = $priority;
        }
    }

    public function toggleFavorite(): void
    {
        $this->project->update(['is_favorite' => ! $this->project->is_favorite]);
    }

    public function toggleArchive(): void
    {
        $this->project->update(['is_archived' => ! $this->project->is_archived]);
    }

    public function moveToTrash(): void
    {
        $this->project->delete();

        $this->redirectRoute('projects.index');
    }

    public function quickAdd(string $status): void
    {
        $titles = $this->splitCommaSeparated($this->newTaskTitle[$status] ?? '');

        if ($titles === []) {
            return;
        }

        $status = TaskStatus::from($status);

        $position = $this->project->tasks()->where('status', $status)->max('position');
        $position = $position === null ? 0 : $position + 1;

        foreach ($titles as $title) {
            $this->project->tasks()->create([
                'title' => $title,
                'status' => $status,
                'priority' => TaskPriority::Medium,
                'position' => $position,
            ]);

            $position++;
        }

        $this->newTaskTitle[$status->value] = '';
        unset($this->tasksByStatus);
    }

    /**
     * Split a comma-separated string into a list of trimmed, non-empty values,
     * each truncated to fit the `title`/`label` column length.
     *
     * @return list<string>
     */
    private function splitCommaSeparated(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => $part !== '')
            ->map(fn (string $part): string => Str::limit($part, 255, ''))
            ->values()
            ->all();
    }

    /**
     * Move a dropped task into its destination column. `$position` is the drop index
     * among the cards currently visible, which differs from the full column order
     * while a tag or priority filter hides some tasks.
     */
    public function handleSort(int $id, int $position, string $status): void
    {
        $status = TaskStatus::from($status);
        $task = $this->project->tasks()->findOrFail($id);

        DB::transaction(function () use ($task, $position, $status): void {
            $siblings = $this->project->tasks()
                ->where('status', $status)
                ->whereKeyNot($task->id)
                ->orderBy('position')
                ->get();

            $ordered = $siblings->values();
            $ordered->splice($this->fullColumnIndex($siblings, $position, $status), 0, [$task]);

            foreach ($ordered as $index => $item) {
                if ($item->is($task)) {
                    $task->update([
                        'status' => $status,
                        'position' => $index,
                        'completed_at' => $status === TaskStatus::Done ? ($task->completed_at ?? now()) : null,
                    ]);
                } elseif ($item->position !== $index) {
                    $item->update(['position' => $index]);
                }
            }
        });

        unset($this->tasksByStatus);
    }

    /**
     * Translate a drop index within the visible (filtered) cards of a column into an
     * index within all of the column's tasks: the dropped task goes just before the
     * visible card it landed on, or just after the last visible card.
     *
     * @param  Collection<int, Task>  $siblings  The column's other tasks, in position order.
     */
    private function fullColumnIndex(Collection $siblings, int $visiblePosition, TaskStatus $status): int
    {
        if (empty($this->tagFilter) && empty($this->priorityFilter)) {
            return min($visiblePosition, $siblings->count());
        }

        $visibleIds = $this->applyBoardFilters($this->project->tasks())
            ->where('status', $status)
            ->whereKey($siblings->pluck('id'))
            ->orderBy('position')
            ->pluck('id');

        if ($visibleIds->isEmpty()) {
            return $siblings->count();
        }

        $anchorId = $visibleIds->get($visiblePosition);

        if ($anchorId !== null) {
            return $siblings->search(fn (Task $sibling): bool => $sibling->id === $anchorId);
        }

        return $siblings->search(fn (Task $sibling): bool => $sibling->id === $visibleIds->last()) + 1;
    }

    #[On('task-updated')]
    public function refreshBoard(): void
    {
        unset($this->tasksByStatus);
    }

    /**
     * Pick up edits made in the project form modal so the header isn't stale.
     */
    #[On('project-saved')]
    public function refreshProject(): void
    {
        $this->project->refresh();
    }
};
