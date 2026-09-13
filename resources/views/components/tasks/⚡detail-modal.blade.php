<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
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
            'completed_at' => $status === TaskStatus::Done ? ($task->completed_at ?? now()) : null,
        ]);

        $this->dispatch('task-updated');
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
        <div class="fixed inset-0 z-50 flex justify-end bg-black/40" wire:click.self="close">
            <div class="w-full max-w-lg h-full overflow-y-auto bg-white dark:bg-[#161615] border-l border-[#e3e3e0] dark:border-[#3E3E3A] p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                        Task Details
                    </h2>
                    <button type="button" wire:click="close" class="text-[#706f6c] dark:text-[#A1A09A] hover:text-current">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="task-title">Title</label>
                        <input
                            id="task-title"
                            type="text"
                            wire:model="title"
                            class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
                        >
                        @error('title') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="task-description">Description</label>
                        <textarea
                            id="task-description"
                            wire:model="description"
                            rows="3"
                            class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1" for="task-status">Status</label>
                            <select id="task-status" wire:model="status" class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm">
                                @foreach (\App\Enums\TaskStatus::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1" for="task-priority">Priority</label>
                            <select id="task-priority" wire:model="priority" class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm">
                                @foreach (\App\Enums\TaskPriority::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="task-due-date">Due date</label>
                        <input
                            id="task-due-date"
                            type="date"
                            wire:model="dueDate"
                            class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
                        >
                        @error('dueDate') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <button type="button" wire:click="moveToTrash" wire:confirm="Move this task to the trash?" wire:loading.attr="disabled" wire:target="moveToTrash" class="inline-flex items-center gap-1 text-sm text-red-600 dark:text-red-400 data-loading:opacity-70">
                            <x-heroicon-o-trash wire:loading.remove wire:target="moveToTrash" class="w-4 h-4" />
                            <x-heroicon-o-arrow-path wire:loading wire:target="moveToTrash" class="w-4 h-4 animate-spin" />
                            Move to Trash
                        </button>

                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70" wire:loading.attr="disabled" wire:target="save">
                            <x-heroicon-o-arrow-path wire:loading wire:target="save" class="w-4 h-4 animate-spin" />
                            Save Changes
                        </button>
                    </div>
                </form>

                <hr class="my-6 border-[#e3e3e0] dark:border-[#3E3E3A]">

                <livewire:checklist.manager :task="$this->task" :key="'checklist-'.$this->task->id" />

                <hr class="my-6 border-[#e3e3e0] dark:border-[#3E3E3A]">

                <livewire:tags.picker :task="$this->task" :key="'tags-'.$this->task->id" />
            </div>
        </div>
    @endif
</div>
