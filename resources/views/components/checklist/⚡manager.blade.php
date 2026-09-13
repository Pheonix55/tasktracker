<?php

use App\Models\ChecklistItem;
use App\Models\Task;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public string $newItemLabel = '';

    public function mount(Task $task): void
    {
        $this->task = $task;
    }

    public function add(): void
    {
        $this->validate([
            'newItemLabel' => ['required', 'string', 'max:2000'],
        ]);

        $labels = collect(explode(',', $this->newItemLabel))
            ->map(fn (string $label): string => trim($label))
            ->filter(fn (string $label): bool => $label !== '')
            ->map(fn (string $label): string => Str::limit($label, 255, ''))
            ->values();

        if ($labels->isEmpty()) {
            return;
        }

        $position = $this->task->checklistItems()->count();

        foreach ($labels as $label) {
            $this->task->checklistItems()->create([
                'label' => $label,
                'position' => $position,
            ]);

            $position++;
        }

        $this->newItemLabel = '';
        $this->task->load('checklistItems');
    }

    public function toggle(int $itemId): void
    {
        $item = $this->task->checklistItems()->findOrFail($itemId);
        $item->update(['is_completed' => ! $item->is_completed]);

        $this->task->load('checklistItems');
    }

    public function remove(int $itemId): void
    {
        $this->task->checklistItems()->findOrFail($itemId)->delete();

        $this->task->load('checklistItems');
    }

    public function handleSort(int $id, int $position): void
    {
        $items = $this->task->checklistItems()->orderBy('position')->get();
        $moved = $items->firstWhere('id', $id);
        $ordered = $items->reject(fn (ChecklistItem $item): bool => $item->id === $moved->id)->values();
        $ordered->splice($position, 0, [$moved]);

        foreach ($ordered as $index => $item) {
            if ($item->position !== $index) {
                $item->update(['position' => $index]);
            }
        }

        $this->task->load('checklistItems');
    }
};
?>

<div class="space-y-3">
    <h3 class="text-sm font-medium flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4" />
        Checklist
        <x-ui.checklist-progress :task="$task" />
    </h3>

    <ul wire:sort="handleSort" class="space-y-1.5">
        @foreach ($task->checklistItems as $item)
            <li
                wire:key="checklist-item-{{ $item->id }}"
                wire:sort:item="{{ $item->id }}"
                class="flex items-center gap-2 group rounded-md px-2 py-1.5 hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a]"
            >
                <span wire:sort:handle class="cursor-grab text-[#a1a09a]">
                    <x-heroicon-o-bars-2 class="w-4 h-4" />
                </span>

                <input
                    type="checkbox"
                    wire:click="toggle({{ $item->id }})"
                    @checked($item->is_completed)
                    class="rounded border-[#e3e3e0] dark:border-[#3E3E3A] text-[#F53003] focus:ring-[#F53003]/40 data-loading:opacity-50"
                >

                <span class="flex-1 text-sm {{ $item->is_completed ? 'line-through text-[#a1a09a]' : '' }}">
                    {{ $item->label }}
                </span>

                <button
                    type="button"
                    wire:click="remove({{ $item->id }})"
                    class="opacity-0 group-hover:opacity-100 text-[#a1a09a] hover:text-red-600 dark:hover:text-red-400 data-loading:opacity-100 data-loading:pointer-events-none"
                >
                    <x-heroicon-o-x-mark wire:loading.remove wire:target="remove({{ $item->id }})" class="w-4 h-4" />
                    <x-heroicon-o-arrow-path wire:loading wire:target="remove({{ $item->id }})" class="w-4 h-4 animate-spin" />
                </button>
            </li>
        @endforeach
    </ul>

    <form wire:submit="add" class="flex items-center gap-2">
        <textarea
            wire:model="newItemLabel"
            wire:keydown.enter.prevent="add"
            rows="1"
            placeholder="Add checklist items (comma-separated)..."
            class="flex-1 resize-none rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40 data-loading:opacity-50"
        ></textarea>
        <button type="submit" wire:loading.attr="disabled" wire:target="add" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70">
            <x-heroicon-o-arrow-path wire:loading wire:target="add" class="w-3.5 h-3.5 animate-spin" />
            Add
        </button>
    </form>
    @error('newItemLabel') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>
