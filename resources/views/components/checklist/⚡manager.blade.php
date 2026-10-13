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
    @php
        $progress = $task->checklistProgress;
        $percent = $progress['total'] > 0 ? (int) round($progress['completed'] / $progress['total'] * 100) : 0;
    @endphp

    <div class="flex items-center justify-between gap-3">
        <h3 class="flex items-center gap-2 text-sm font-semibold">
            <span class="flex size-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <x-heroicon-o-check-circle class="size-4" />
            </span>
            Checklist
        </h3>
        @if ($progress['total'] > 0)
            <span class="text-muted text-xs font-medium tabular-nums">{{ $progress['completed'] }}/{{ $progress['total'] }} &middot; {{ $percent }}%</span>
        @endif
    </div>

    @if ($progress['total'] > 0)
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
            <div class="h-full rounded-full bg-linear-to-r from-emerald-400 to-teal-500 transition-all duration-500 ease-out" style="width: {{ $percent }}%;"></div>
        </div>
    @endif

    <ul wire:sort="handleSort" class="space-y-1">
        @foreach ($task->checklistItems as $item)
            <li
                wire:key="checklist-item-{{ $item->id }}"
                wire:sort:item="{{ $item->id }}"
                class="group flex items-center gap-2.5 rounded-xl px-2 py-2 transition hover:bg-white dark:hover:bg-white/5"
            >
                <span wire:sort:handle class="cursor-grab text-zinc-300 transition group-hover:text-zinc-500 active:cursor-grabbing dark:text-zinc-600 dark:group-hover:text-zinc-400">
                    <x-heroicon-o-bars-2 class="size-4" />
                </span>

                <input
                    type="checkbox"
                    wire:click="toggle({{ $item->id }})"
                    @checked($item->is_completed)
                    class="size-4 cursor-pointer rounded accent-emerald-500 transition data-loading:opacity-50"
                >

                <span @class([
                    'flex-1 text-sm transition-all duration-300',
                    'text-zinc-400 line-through dark:text-zinc-500' => $item->is_completed,
                ])>
                    {{ $item->label }}
                </span>

                <button
                    type="button"
                    wire:click="remove({{ $item->id }})"
                    aria-label="Remove item"
                    class="rounded-lg p-1 text-zinc-400 opacity-0 transition group-hover:opacity-100 hover:bg-red-50 hover:text-red-600 data-loading:pointer-events-none data-loading:opacity-100 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                >
                    <x-heroicon-o-x-mark wire:loading.remove wire:target="remove({{ $item->id }})" class="size-4" />
                    <x-heroicon-o-arrow-path wire:loading wire:target="remove({{ $item->id }})" class="size-4 animate-spin" />
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
            class="input-field flex-1 resize-none py-1.5 data-loading:opacity-50"
        ></textarea>
        <button type="submit" wire:loading.attr="disabled" wire:target="add" aria-label="Add checklist item" class="btn-primary px-3 py-2 data-loading:opacity-70">
            <x-heroicon-o-plus wire:loading.remove wire:target="add" class="size-4" />
            <x-heroicon-o-arrow-path wire:loading wire:target="add" class="size-4 animate-spin" />
        </button>
    </form>
    @error('newItemLabel') <p class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
</div>
