<?php

use App\Models\Tag;
use App\Models\Task;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public string $newTagName = '';

    public function mount(Task $task): void
    {
        $this->task = $task;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tag>
     */
    public function allTags()
    {
        return Tag::orderBy('name')->get();
    }

    public function toggleTag(int $tagId): void
    {
        $this->task->tags()->toggle($tagId);
        $this->task->load('tags');
    }

    public function createTag(): void
    {
        $this->validate([
            'newTagName' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ]);

        $palette = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#10b981', '#06b6d4', '#3b82f6'];

        $tag = Tag::create([
            'name' => $this->newTagName,
            'color' => $palette[array_rand($palette)],
        ]);

        $this->task->tags()->attach($tag->id);
        $this->task->load('tags');
        $this->newTagName = '';
    }
};
?>

<div class="space-y-3">
    <h3 class="flex items-center gap-2 text-sm font-semibold">
        <span class="flex size-7 items-center justify-center rounded-lg bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-500/15 dark:text-fuchsia-400">
            <x-heroicon-o-tag class="size-4" />
        </span>
        Tags
    </h3>

    <div class="flex flex-wrap gap-2">
        @foreach ($this->allTags() as $tag)
            @php $attached = $task->tags->contains($tag->id); @endphp
            <button
                type="button"
                wire:click="toggleTag({{ $tag->id }})"
                wire:key="tag-picker-{{ $tag->id }}"
                @class([
                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-all duration-200 hover:scale-105 active:scale-95 data-loading:pointer-events-none data-loading:opacity-50',
                    'shadow-sm' => $attached,
                    'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 dark:border-white/10 dark:bg-transparent dark:text-zinc-400' => ! $attached,
                ])
                @style([
                    "background-color: {$tag->color}1f; color: {$tag->color}; border-color: {$tag->color}66;" => $attached,
                ])
            >
                @if ($attached)
                    <x-heroicon-m-check class="size-3" />
                @else
                    <span class="size-1.5 rounded-full" style="background-color: {{ $tag->color }};"></span>
                @endif
                {{ $tag->name }}
            </button>
        @endforeach
    </div>

    <form wire:submit="createTag" class="flex items-center gap-2">
        <input
            type="text"
            wire:model="newTagName"
            placeholder="Create new tag..."
            class="input-field flex-1 py-1.5 data-loading:opacity-50"
        >
        <button type="submit" wire:loading.attr="disabled" wire:target="createTag" class="btn-secondary px-3 py-1.5 data-loading:opacity-70">
            <x-heroicon-o-plus wire:loading.remove wire:target="createTag" class="size-4" />
            <x-heroicon-o-arrow-path wire:loading wire:target="createTag" class="size-4 animate-spin" />
            Add
        </button>
    </form>
    @error('newTagName') <p class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
</div>
