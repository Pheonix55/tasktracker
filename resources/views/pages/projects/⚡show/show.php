<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Collection;
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
        $query = $this->project->tasks()->with(['tags', 'checklistItems'])->orderBy('position');

        if (! empty($this->tagFilter)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $this->tagFilter));
        }

        return $query->get()->groupBy(fn (Task $task) => $task->status->value);
    }

    public function toggleTagFilter(int $tagId): void
    {
        if (in_array($tagId, $this->tagFilter, true)) {
            $this->tagFilter = array_values(array_diff($this->tagFilter, [$tagId]));
        } else {
            $this->tagFilter[] = $tagId;
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
        $title = trim($this->newTaskTitle[$status] ?? '');

        if ($title === '') {
            return;
        }

        $status = TaskStatus::from($status);

        $position = $this->project->tasks()->where('status', $status)->max('position');

        $this->project->tasks()->create([
            'title' => $title,
            'status' => $status,
            'priority' => TaskPriority::Medium,
            'position' => $position === null ? 0 : $position + 1,
        ]);

        $this->newTaskTitle[$status->value] = '';
        unset($this->tasksByStatus);
    }

    public function handleSort(int $id, int $position, string $status): void
    {
        $status = TaskStatus::from($status);
        $task = $this->project->tasks()->findOrFail($id);

        $task->update([
            'status' => $status,
            'position' => $position,
            'completed_at' => $status === TaskStatus::Done ? ($task->completed_at ?? now()) : null,
        ]);

        // Re-sequence siblings in the destination column so positions stay contiguous.
        $siblings = $this->project->tasks()
            ->where('status', $status)
            ->where('id', '!=', $task->id)
            ->orderBy('position')
            ->get();

        $index = 0;

        foreach ($siblings as $sibling) {
            if ($index === $position) {
                $index++;
            }

            $sibling->update(['position' => $index]);
            $index++;
        }

        unset($this->tasksByStatus);
    }

    #[On('task-updated')]
    public function refreshBoard(): void
    {
        unset($this->tasksByStatus);
    }
};
