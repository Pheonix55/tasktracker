<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Exceptions\AiRequestException;
use App\Models\Task;
use App\Services\AiTextGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $show = false;

    public ?int $taskId = null;

    public string $title = '';

    public string $description = '';

    public string $priority = '';

    public string $status = '';

    public ?string $dueDate = null;

    public ?string $aiError = null;

    #[On('task-selected')]
    public function open(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        $this->taskId = $task->id;
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->priority = $task->priority->value;
        $this->status = $task->status->value;
        $this->dueDate = $task->due_date?->format('Y-m-d');
        $this->aiError = null;
        $this->resetValidation();

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->taskId = null;

        // Checklist/tag edits made while the panel was open don't refresh the
        // board or dashboard live (that would mean a round trip per click);
        // catch them up now that the task is no longer being edited.
        $this->dispatch('task-updated');
    }

    #[Computed]
    public function task(): ?Task
    {
        return $this->taskId !== null ? Task::find($this->taskId) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    /**
     * Ask the configured AI provider to write (or improve) the description from the task title and its project.
     */
    public function generateDescription(AiTextGenerator $generator): void
    {
        $this->aiError = null;
        $this->validateOnly('title');

        $project = Task::with('project')->findOrFail($this->taskId)->project;

        $prompt = "Task title: {$this->title}";

        if ($project !== null) {
            $prompt = "Project: {$project->name}\n"
                .(filled($project->description) ? "Project description: {$project->description}\n" : '')
                .$prompt;
        }

        if (filled($this->description)) {
            $prompt .= "\nCurrent task description (improve it, keep its meaning): {$this->description}";
        }

        try {
            $generated = $generator->generate(
                'You write descriptions for tasks in a personal task-tracking app. '
                .'Write a short, actionable description of what needs to be done for this task and what "done" looks like, '
                .'in two to four plain sentences. Use the project only as context. '
                .'Reply with only the description: no title, quotes, markdown, or preamble.',
                $prompt,
            );

            $this->description = str($generated)->trim("\"' \n")->limit(5000, '')->toString();
        } catch (AiRequestException $exception) {
            $this->aiError = $exception->getMessage();
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $task = Task::findOrFail($this->taskId);
        $status = TaskStatus::from($validated['status']);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => TaskPriority::from($validated['priority']),
            'status' => $status,
            'due_date' => $validated['dueDate'],
            'position' => $task->status === $status ? $task->position : $this->nextPositionIn($task, $status),
            'completed_at' => $status === TaskStatus::Done ? ($task->completed_at ?? now()) : null,
        ]);

        $this->dispatch('task-updated');
    }

    /**
     * The position at the bottom of the given status column in the task's project,
     * so a task moved there by changing its status doesn't collide with an existing card.
     */
    private function nextPositionIn(Task $task, TaskStatus $status): int
    {
        $maxPosition = Task::query()
            ->where('project_id', $task->project_id)
            ->where('status', $status)
            ->max('position');

        return $maxPosition === null ? 0 : $maxPosition + 1;
    }

    public function moveToTrash(): void
    {
        Task::findOrFail($this->taskId)->delete();

        $this->show = false;
        $this->taskId = null;
        $this->dispatch('task-updated');
    }
};
?>

<div>
    @if ($show && $this->task)
        <div
            class="fixed inset-0 z-50 flex animate-fade-in justify-end bg-zinc-950/40 backdrop-blur-sm"
            wire:click.self="close"
            x-data
            x-on:keydown.escape.window="$wire.close()"
        >
            <div class="h-full w-full max-w-lg animate-slide-in-right overflow-y-auto border-l border-zinc-200/80 bg-white shadow-2xl shadow-zinc-950/30 dark:border-white/10 dark:bg-zinc-900">
                <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-zinc-100 bg-white/80 px-6 py-4 backdrop-blur-xl dark:border-white/5 dark:bg-zinc-900/80">
                    <h2 class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-violet-500 text-white shadow-md shadow-indigo-500/30">
                            <x-heroicon-o-clipboard-document-check class="size-5" />
                        </span>
                        Task Details
                    </h2>
                    <button type="button" wire:click="close" aria-label="Close" class="btn-icon">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>

                <div class="space-y-6 p-6">
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <label class="field-label" for="task-title">Title</label>
                            <input
                                id="task-title"
                                type="text"
                                wire:model="title"
                                class="input-field text-base font-medium"
                            >
                            @error('title') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label class="field-label" for="task-description">Description</label>
                                <x-ui.ai-generate-button action="generateDescription" :has-text="$description !== ''" />
                            </div>
                            <textarea
                                id="task-description"
                                wire:model="description"
                                rows="4"
                                placeholder="Add more detail..."
                                class="input-field resize-none"
                            ></textarea>
                            @error('description') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                            <x-ui.ai-error :message="$aiError" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label flex items-center gap-1.5" for="task-status">
                                    <x-heroicon-m-signal class="size-3.5 text-zinc-400" />
                                    Status
                                </label>
                                <select id="task-status" wire:model="status" class="input-field cursor-pointer">
                                    @foreach (\App\Enums\TaskStatus::cases() as $option)
                                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="field-label flex items-center gap-1.5" for="task-priority">
                                    <x-heroicon-m-flag class="size-3.5 text-zinc-400" />
                                    Priority
                                </label>
                                <select id="task-priority" wire:model="priority" class="input-field cursor-pointer">
                                    @foreach (\App\Enums\TaskPriority::cases() as $option)
                                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="field-label flex items-center gap-1.5" for="task-due-date">
                                <x-heroicon-m-calendar class="size-3.5 text-zinc-400" />
                                Due date
                            </label>
                            <input
                                id="task-due-date"
                                type="date"
                                wire:model="dueDate"
                                class="input-field cursor-pointer dark:[color-scheme:dark]"
                            >
                            @error('dueDate') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" wire:click="moveToTrash" wire:confirm="Move this task to the trash?" wire:loading.attr="disabled" wire:target="moveToTrash" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 data-loading:opacity-70 dark:text-red-400 dark:hover:bg-red-500/10">
                                <x-heroicon-o-trash wire:loading.remove wire:target="moveToTrash" class="size-4" />
                                <x-heroicon-o-arrow-path wire:loading wire:target="moveToTrash" class="size-4 animate-spin" />
                                Move to Trash
                            </button>

                            <button type="submit" class="btn-primary data-loading:opacity-70" wire:loading.attr="disabled" wire:target="save">
                                <x-heroicon-o-check wire:loading.remove wire:target="save" class="size-4" />
                                <x-heroicon-o-arrow-path wire:loading wire:target="save" class="size-4 animate-spin" />
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/5 dark:bg-white/[0.02]">
                        <livewire:checklist.manager :task="$this->task" :key="'checklist-'.$this->task->id" />
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/5 dark:bg-white/[0.02]">
                        <livewire:tags.picker :task="$this->task" :key="'tags-'.$this->task->id" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
