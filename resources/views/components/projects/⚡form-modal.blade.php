<?php

use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $show = false;

    public ?int $projectId = null;

    public string $name = '';

    public string $description = '';

    public string $color = '#6366f1';

    public string $icon = 'rectangle-stack';

    /**
     * Curated heroicon names a project can use.
     *
     * @var list<string>
     */
    public array $availableIcons = [
        'rectangle-stack', 'folder', 'briefcase', 'rocket-launch', 'light-bulb',
        'code-bracket', 'paint-brush', 'book-open', 'chart-bar', 'globe-alt',
    ];

    /**
     * Curated color swatches a project can use.
     *
     * @var list<string>
     */
    public array $availableColors = [
        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316',
        '#eab308', '#22c55e', '#10b981', '#06b6d4', '#3b82f6',
    ];

    /**
     * Open the modal, optionally loading an existing project for editing.
     */
    #[On('open-project-form')]
    public function open(?int $projectId = null): void
    {
        $this->reset(['projectId', 'name', 'description', 'color', 'icon']);
        $this->resetValidation();

        if ($projectId !== null) {
            $project = Project::findOrFail($projectId);

            $this->projectId = $project->id;
            $this->name = $project->name;
            $this->description = (string) $project->description;
            $this->color = $project->color;
            $this->icon = $project->icon;
        } else {
            $this->color = '#6366f1';
            $this->icon = 'rectangle-stack';
        }

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'string', 'max:7'],
            'icon' => ['required', 'string', 'max:100'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $project = $this->projectId !== null
            ? Project::findOrFail($this->projectId)
            : new Project;

        $project->fill($validated);
        $project->save();

        $this->show = false;
        $this->dispatch('project-saved');
    }
};
?>

<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="close">
            <div class="w-full max-w-lg rounded-lg bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">{{ $projectId ? 'Edit Project' : 'New Project' }}</h2>
                    <button type="button" wire:click="close" class="text-[#706f6c] dark:text-[#A1A09A] hover:text-current">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="project-name">Name</label>
                        <input
                            id="project-name"
                            type="text"
                            wire:model="name"
                            class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
                            placeholder="e.g. Website Redesign"
                        >
                        @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="project-description">Description</label>
                        <textarea
                            id="project-description"
                            wire:model="description"
                            rows="3"
                            class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F53003]/40"
                            placeholder="What is this project about?"
                        ></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="block text-sm font-medium mb-1">Color</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableColors as $swatch)
                                <button
                                    type="button"
                                    wire:click="$set('color', '{{ $swatch }}')"
                                    class="w-7 h-7 rounded-full data-loading:opacity-40 {{ $color === $swatch ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#161615] ring-current' : '' }}"
                                    style="background-color: {{ $swatch }};"
                                    aria-label="{{ $swatch }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="block text-sm font-medium mb-1">Icon</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableIcons as $iconName)
                                <button
                                    type="button"
                                    wire:click="$set('icon', '{{ $iconName }}')"
                                    class="flex items-center justify-center w-9 h-9 rounded-md border data-loading:opacity-40 {{ $icon === $iconName ? 'border-[#F53003] dark:border-[#FF4433] text-[#F53003] dark:text-[#FF4433]' : 'border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A]' }}"
                                >
                                    <x-dynamic-component :component="'heroicon-o-'.$iconName" class="w-5 h-5" />
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="close" class="px-4 py-2 rounded-md text-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white data-loading:opacity-70" wire:loading.attr="disabled" wire:target="save">
                            <x-heroicon-o-arrow-path wire:loading wire:target="save" class="w-4 h-4 animate-spin" />
                            Save Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
