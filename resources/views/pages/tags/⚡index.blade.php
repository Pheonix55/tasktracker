<?php

use App\Models\Tag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Tags')] class extends Component
{
    public string $newTagName = '';

    public string $newTagColor = '#6366f1';

    public ?int $editingTagId = null;

    public string $editingName = '';

    public string $editingColor = '#6366f1';

    /**
     * @var list<string>
     */
    public array $palette = [
        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316',
        '#eab308', '#22c55e', '#10b981', '#06b6d4', '#3b82f6',
    ];

    #[Computed]
    public function tags()
    {
        return Tag::withCount('tasks')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->validate([
            'newTagName' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ]);

        Tag::create([
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
        ]);

        $this->newTagName = '';
        unset($this->tags);
    }

    public function edit(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->editingTagId = $tag->id;
        $this->editingName = $tag->name;
        $this->editingColor = $tag->color;
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'max:255', 'unique:tags,name,'.$this->editingTagId],
        ]);

        Tag::findOrFail($this->editingTagId)->update([
            'name' => $this->editingName,
            'color' => $this->editingColor,
        ]);

        $this->editingTagId = null;
        unset($this->tags);
    }

    public function cancelEdit(): void
    {
        $this->editingTagId = null;
    }

    public function delete(int $tagId): void
    {
        Tag::findOrFail($tagId)->delete();
        unset($this->tags);
    }
};
?>

<div class="space-y-6 max-w-2xl">
    <div>
        <h1 class="text-xl font-semibold">Tags</h1>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Organize tasks across every project with reusable labels.</p>
    </div>

    <form wire:submit="create" class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium mb-1" for="new-tag-name">New tag</label>
            <input
                id="new-tag-name"
                type="text"
                wire:model="newTagName"
                placeholder="e.g. Bug"
                class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
            >
            @error('newTagName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-1.5 pb-2">
            @foreach ($palette as $swatch)
                <button
                    type="button"
                    wire:click="$set('newTagColor', '{{ $swatch }}')"
                    class="w-6 h-6 rounded-full data-loading:opacity-40 {{ $newTagColor === $swatch ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#0a0a0a] ring-current' : '' }}"
                    style="background-color: {{ $swatch }};"
                ></button>
            @endforeach
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="create" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70">
            <x-heroicon-o-arrow-path wire:loading wire:target="create" class="w-4 h-4 animate-spin" />
            Add
        </button>
    </form>

    <ul class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A] rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]">
        @forelse ($this->tags as $tag)
            <li wire:key="tag-{{ $tag->id }}" class="p-3 flex items-center gap-3">
                @if ($editingTagId === $tag->id)
                    <form wire:submit="update" class="flex items-center gap-2 flex-1">
                        <div class="flex gap-1">
                            @foreach ($palette as $swatch)
                                <button
                                    type="button"
                                    wire:click="$set('editingColor', '{{ $swatch }}')"
                                    class="w-5 h-5 rounded-full data-loading:opacity-40 {{ $editingColor === $swatch ? 'ring-2 ring-offset-1 ring-current' : '' }}"
                                    style="background-color: {{ $swatch }};"
                                ></button>
                            @endforeach
                        </div>
                        <input type="text" wire:model="editingName" class="flex-1 rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-2 py-1 text-sm">
                        <button type="submit" wire:loading.attr="disabled" wire:target="update" class="text-sm text-emerald-600 dark:text-emerald-400 data-loading:opacity-50">Save</button>
                        <button type="button" wire:click="cancelEdit" class="text-sm text-[#a1a09a] data-loading:opacity-50">Cancel</button>
                    </form>
                @else
                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $tag->color }};"></span>
                    <span class="flex-1 text-sm font-medium">{{ $tag->name }}</span>
                    <span class="text-xs text-[#a1a09a]">{{ $tag->tasks_count }} {{ $tag->tasks_count === 1 ? 'task' : 'tasks' }}</span>
                    <button type="button" wire:click="edit({{ $tag->id }})" class="p-1.5 rounded-md text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a] data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                    </button>
                    <button type="button" wire:click="delete({{ $tag->id }})" wire:confirm="Delete this tag? It will be removed from all tasks." class="p-1.5 rounded-md text-red-600 dark:text-red-400 hover:bg-[#f5f5f4] dark:hover:bg-[#1b1b1a] data-loading:opacity-50 data-loading:pointer-events-none">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                @endif
            </li>
        @empty
            <li class="p-6 text-center text-sm text-[#a1a09a]">No tags yet.</li>
        @endforelse
    </ul>
</div>
