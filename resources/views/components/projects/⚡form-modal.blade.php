<?php

use App\Exceptions\AiRequestException;
use App\Models\Project;
use App\Services\AiTextGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
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

    public ?string $aiError = null;

    /**
     * Curated heroicon names a project can use.
     *
     * @var list<string>
     */
    #[Locked]
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
        $this->reset(['projectId', 'name', 'description', 'color', 'icon', 'aiError']);
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
            'icon' => ['required', 'string', Rule::in($this->availableIcons)],
        ];
    }

    /**
     * Ask the configured AI provider to write (or improve) the description from the project name.
     */
    public function generateDescription(AiTextGenerator $generator): void
    {
        $this->aiError = null;
        $this->validateOnly('name');

        $prompt = "Project name: {$this->name}";

        if (filled($this->description)) {
            $prompt .= "\nCurrent description (improve it, keep its meaning): {$this->description}";
        }

        try {
            $generated = $generator->generate(
                'You write descriptions for projects in a personal task-tracking app. '
                .'Write one to three plain sentences describing the goal and scope of the project. '
                .'Reply with only the description: no title, quotes, markdown, or preamble.',
                $prompt,
            );

            $this->description = str($generated)->trim("\"' \n")->limit(2000, '')->toString();
        } catch (AiRequestException $exception) {
            $this->aiError = $exception->getMessage();
        }
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
        <div
            class="fixed inset-0 z-50 flex animate-fade-in items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm"
            wire:click.self="close"
            x-data
            x-on:keydown.escape.window="$wire.close()"
        >
            <div class="w-full max-w-lg animate-scale-in overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-2xl shadow-zinc-950/20 dark:border-white/10 dark:bg-zinc-900">
                <div class="relative overflow-hidden px-6 pt-6 pb-5">
                    <div class="pointer-events-none absolute -top-20 -right-16 size-52 rounded-full opacity-25 blur-3xl transition-colors duration-500" style="background-color: {{ $color }};"></div>

                    <div class="relative flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl text-white shadow-lg transition-all duration-300" style="background: linear-gradient(135deg, {{ $color }}, {{ $color }}b3); box-shadow: 0 8px 20px -6px {{ $color }}99;">
                                <x-dynamic-component :component="'heroicon-o-'.(in_array($icon, $availableIcons, true) ? $icon : 'rectangle-stack')" class="size-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight">{{ $projectId ? 'Edit Project' : 'New Project' }}</h2>
                                <p class="text-muted text-xs">{{ $projectId ? 'Update the details of this board.' : 'Set up a new board for your tasks.' }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="close" aria-label="Close" class="btn-icon">
                            <x-heroicon-o-x-mark class="size-5" />
                        </button>
                    </div>
                </div>

                <form wire:submit="save" class="space-y-5 px-6 pb-6">
                    <div>
                        <label class="field-label" for="project-name">Name</label>
                        <input
                            id="project-name"
                            type="text"
                            wire:model="name"
                            class="input-field"
                            placeholder="e.g. Website Redesign"
                            autofocus
                        >
                        @error('name') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <label class="field-label" for="project-description">Description</label>
                            <x-ui.ai-generate-button action="generateDescription" :has-text="$description !== ''" />
                        </div>
                        <textarea
                            id="project-description"
                            wire:model="description"
                            rows="3"
                            class="input-field resize-none"
                            placeholder="What is this project about?"
                        ></textarea>
                        @error('description') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                        <x-ui.ai-error :message="$aiError" />
                    </div>

                    <div>
                        <span class="field-label">Color</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableColors as $swatch)
                                <button
                                    type="button"
                                    wire:click="$set('color', '{{ $swatch }}')"
                                    aria-label="{{ $swatch }}"
                                    @class([
                                        'flex size-8 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 hover:scale-110 data-loading:opacity-40',
                                        'scale-110 ring-2 ring-zinc-900/80 ring-offset-2 ring-offset-white dark:ring-white/80 dark:ring-offset-zinc-900' => $color === $swatch,
                                    ])
                                    style="background-color: {{ $swatch }};"
                                >
                                    @if ($color === $swatch)
                                        <x-heroicon-m-check class="size-4" />
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="field-label">Icon</span>
                        <div class="grid grid-cols-5 gap-2">
                            @foreach ($availableIcons as $iconName)
                                <button
                                    type="button"
                                    wire:click="$set('icon', '{{ $iconName }}')"
                                    aria-label="{{ $iconName }}"
                                    @class([
                                        'flex h-11 items-center justify-center rounded-xl border transition-all duration-200 hover:-translate-y-0.5 data-loading:opacity-40',
                                        'border-transparent text-white shadow-md' => $icon === $iconName,
                                        'border-zinc-200 text-zinc-500 hover:border-zinc-300 hover:text-zinc-900 dark:border-white/10 dark:text-zinc-400 dark:hover:border-white/20 dark:hover:text-white' => $icon !== $iconName,
                                    ])
                                    @style(["background: linear-gradient(135deg, {$color}, {$color}b3);" => $icon === $iconName])
                                >
                                    <x-dynamic-component :component="'heroicon-o-'.$iconName" class="size-5" />
                                </button>
                            @endforeach
                        </div>
                        @error('icon') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-zinc-100 pt-5 dark:border-white/5">
                        <button type="button" wire:click="close" class="btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary data-loading:opacity-70" wire:loading.attr="disabled" wire:target="save">
                            <x-heroicon-o-check wire:loading.remove wire:target="save" class="size-4" />
                            <x-heroicon-o-arrow-path wire:loading wire:target="save" class="size-4 animate-spin" />
                            Save Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
