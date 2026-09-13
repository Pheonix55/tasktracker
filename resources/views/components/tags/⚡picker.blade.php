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
        $this->dispatch('task-updated');
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
        $this->dispatch('task-updated');
    }
};
?>

<div class="space-y-3">
    <h3 class="text-sm font-medium flex items-center gap-2">
        <x-heroicon-o-tag class="w-4 h-4" />
        Tags
    </h3>

    <div class="flex flex-wrap gap-2">
        @foreach ($this->allTags() as $tag)
            @php $attached = $task->tags->contains($tag->id); @endphp
            <button
                type="button"
                wire:click="toggleTag({{ $tag->id }})"
                wire:key="tag-picker-{{ $tag->id }}"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border transition-colors data-loading:opacity-50 data-loading:pointer-events-none"
                @style([
                    "background-color: {$tag->color}22; color: {$tag->color}; border-color: {$tag->color}55;" => $attached,
                    'border-color: #e3e3e0; color: #706f6c;' => ! $attached,
                ])
            >
                @if ($attached)
                    <x-heroicon-o-check class="w-3 h-3" />
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
            class="flex-1 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40 data-loading:opacity-50"
        >
        <button type="submit" wire:loading.attr="disabled" wire:target="createTag" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm border border-[#e3e3e0] dark:border-[#3E3E3A] data-loading:opacity-70">
            <x-heroicon-o-arrow-path wire:loading wire:target="createTag" class="w-3.5 h-3.5 animate-spin" />
            Add
        </button>
    </form>
    @error('newTagName') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>
