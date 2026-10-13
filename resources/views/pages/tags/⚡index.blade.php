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

<div class="max-w-3xl space-y-6">
    <x-ui.page-header
        title="Tags"
        description="Organize tasks across every project with reusable labels."
        icon="tag"
        gradient="from-fuchsia-500 to-pink-500 shadow-fuchsia-500/30"
    />

    <form wire:submit="create" class="card space-y-4 p-5">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="field-label" for="new-tag-name">New tag</label>
                <div class="relative">
                    <span class="absolute top-1/2 left-3.5 size-3 -translate-y-1/2 rounded-full transition-colors duration-300" style="background-color: {{ $newTagColor }};"></span>
                    <input
                        id="new-tag-name"
                        type="text"
                        wire:model="newTagName"
                        placeholder="e.g. Bug"
                        class="input-field pl-9"
                    >
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="create" class="btn-primary data-loading:opacity-70">
                <x-heroicon-o-plus wire:loading.remove wire:target="create" class="size-4" />
                <x-heroicon-o-arrow-path wire:loading wire:target="create" class="size-4 animate-spin" />
                Add
            </button>
        </div>
        @error('newTagName') <p class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-muted mr-1 text-xs font-medium">Color</span>
            @foreach ($palette as $swatch)
                <button
                    type="button"
                    wire:click="$set('newTagColor', '{{ $swatch }}')"
                    aria-label="Use color {{ $swatch }}"
                    @class([
                        'flex size-7 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 hover:scale-110 data-loading:opacity-40',
                        'scale-110 ring-2 ring-zinc-900/80 ring-offset-2 ring-offset-white dark:ring-white/80 dark:ring-offset-zinc-900' => $newTagColor === $swatch,
                    ])
                    style="background-color: {{ $swatch }};"
                >
                    @if ($newTagColor === $swatch)
                        <x-heroicon-m-check class="size-4" />
                    @endif
                </button>
            @endforeach
        </div>
    </form>

    <div class="card overflow-hidden">
        <ul class="divide-y divide-zinc-100 dark:divide-white/5">
            @forelse ($this->tags as $tag)
                <li
                    wire:key="tag-{{ $tag->id }}"
                    class="group flex animate-fade-in-up items-center gap-3 px-5 py-3.5 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03]"
                    style="animation-delay: {{ min($loop->index, 12) * 40 }}ms;"
                >
                    @if ($editingTagId === $tag->id)
                        <form wire:submit="update" class="flex flex-1 flex-wrap items-center gap-3">
                            <div class="flex gap-1.5">
                                @foreach ($palette as $swatch)
                                    <button
                                        type="button"
                                        wire:click="$set('editingColor', '{{ $swatch }}')"
                                        aria-label="Use color {{ $swatch }}"
                                        @class([
                                            'size-5 rounded-full transition-transform duration-200 hover:scale-125 data-loading:opacity-40',
                                            'scale-110 ring-2 ring-zinc-900/80 ring-offset-1 ring-offset-white dark:ring-white/80 dark:ring-offset-zinc-900' => $editingColor === $swatch,
                                        ])
                                        style="background-color: {{ $swatch }};"
                                    ></button>
                                @endforeach
                            </div>
                            <input type="text" wire:model="editingName" class="input-field min-w-[140px] flex-1 py-1.5">
                            <div class="flex items-center gap-1">
                                <button type="submit" wire:loading.attr="disabled" wire:target="update" aria-label="Save tag" class="btn-icon size-8 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 data-loading:opacity-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                    <x-heroicon-o-check class="size-4" />
                                </button>
                                <button type="button" wire:click="cancelEdit" aria-label="Cancel editing" class="btn-icon size-8 data-loading:opacity-50">
                                    <x-heroicon-o-x-mark class="size-4" />
                                </button>
                            </div>
                        </form>
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl transition-transform duration-200 group-hover:scale-110" style="background-color: {{ $tag->color }}1f; color: {{ $tag->color }};">
                            <x-heroicon-s-tag class="size-4" />
                        </span>
                        <span class="flex-1 text-sm font-medium">{{ $tag->name }}</span>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 tabular-nums dark:bg-white/10 dark:text-zinc-300">{{ $tag->tasks_count }} {{ $tag->tasks_count === 1 ? 'task' : 'tasks' }}</span>
                        <div class="flex items-center gap-0.5 opacity-60 transition-opacity group-hover:opacity-100">
                            <button type="button" wire:click="edit({{ $tag->id }})" aria-label="Edit tag" title="Edit" class="btn-icon size-8 data-loading:pointer-events-none data-loading:opacity-50">
                                <x-heroicon-o-pencil-square class="size-4" />
                            </button>
                            <button type="button" wire:click="delete({{ $tag->id }})" wire:confirm="Delete this tag? It will be removed from all tasks." aria-label="Delete tag" title="Delete" class="btn-icon size-8 hover:bg-red-50 hover:text-red-600 data-loading:pointer-events-none data-loading:opacity-50 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <x-heroicon-o-trash class="size-4" />
                            </button>
                        </div>
                    @endif
                </li>
            @empty
                <li>
                    <x-ui.empty-state icon="tag" title="No tags yet." description="Create one above to start labelling tasks." />
                </li>
            @endforelse
        </ul>
    </div>
</div>
