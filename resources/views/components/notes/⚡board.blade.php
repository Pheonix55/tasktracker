<?php

use App\Models\Note;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    /**
     * Sticky-note color palette so generated notes look intentional.
     *
     * @var list<string>
     */
    protected array $palette = [
        '#fde68a', '#fca5a5', '#bbf7d0', '#bfdbfe', '#e9d5ff', '#fed7aa',
    ];

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Notes visible on this board: the project's own notes plus every
     * general note, pinned notes first.
     *
     * @return \Illuminate\Support\Collection<int, Note>
     */
    #[Computed]
    public function notes()
    {
        return Note::query()
            ->visibleOnProject($this->project->id)
            ->orderByDesc('is_pinned')
            ->orderBy('id')
            ->get();
    }

    public function addNote(): void
    {
        $this->createNote($this->project->id);
    }

    public function addGeneralNote(): void
    {
        $this->createNote(null);
    }

    private function createNote(?int $projectId): void
    {
        Note::create([
            'project_id' => $projectId,
            'body' => '',
            'color' => $this->palette[array_rand($this->palette)],
            'position_x' => random_int(16, 160),
            'position_y' => random_int(16, 120),
        ]);

        unset($this->notes);
    }

    /**
     * Persist a note's text. Renderless: the textarea already shows what
     * was typed, so there's nothing new for the server to render.
     */
    #[Renderless]
    public function updateBody(int $noteId, string $body): void
    {
        Note::whereKey($noteId)->update(['body' => $body]);
    }

    /**
     * Persist a note's dropped position. Renderless: the client already
     * moved the note optimistically while dragging, so this call is purely
     * to save it — no re-render needed.
     */
    #[Renderless]
    public function move(int $noteId, int $x, int $y): void
    {
        Note::whereKey($noteId)->update(['position_x' => $x, 'position_y' => $y]);
    }

    public function togglePin(int $noteId): void
    {
        $note = Note::findOrFail($noteId);
        $note->update(['is_pinned' => ! $note->is_pinned]);

        unset($this->notes);
    }

    public function delete(int $noteId): void
    {
        Note::whereKey($noteId)->delete();

        unset($this->notes);
    }
};
?>

<div>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2.5 text-sm font-semibold">
            <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                <x-heroicon-o-pencil class="size-4" />
            </span>
            Sticky Notes
        </h2>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="addNote"
                wire:loading.attr="disabled"
                wire:target="addNote"
                class="btn-secondary px-3 py-1.5 text-xs data-loading:opacity-50"
            >
                <x-heroicon-o-plus wire:loading.remove wire:target="addNote" class="size-3.5" />
                <x-heroicon-o-arrow-path wire:loading wire:target="addNote" class="size-3.5 animate-spin" />
                Note
            </button>

            <button
                type="button"
                wire:click="addGeneralNote"
                wire:loading.attr="disabled"
                wire:target="addGeneralNote"
                title="General notes appear on every project's board"
                class="btn-secondary px-3 py-1.5 text-xs data-loading:opacity-50"
            >
                <x-heroicon-o-globe-alt wire:loading.remove wire:target="addGeneralNote" class="size-3.5" />
                <x-heroicon-o-arrow-path wire:loading wire:target="addGeneralNote" class="size-3.5 animate-spin" />
                General Note
            </button>
        </div>
    </div>

    <div class="relative min-h-[260px] w-full rounded-2xl border border-dashed border-zinc-300 bg-zinc-100/50 bg-[radial-gradient(circle,rgb(0_0_0/0.07)_1px,transparent_1px)] [background-size:18px_18px] p-2 dark:border-white/10 dark:bg-white/[0.02] dark:bg-[radial-gradient(circle,rgb(255_255_255/0.06)_1px,transparent_1px)]">
        @if ($this->notes->isEmpty())
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-zinc-400">
                <x-heroicon-o-document-plus class="size-8" />
                <p class="text-xs">No notes yet — add one above.</p>
            </div>
        @endif

        @foreach ($this->notes as $note)
            <div
                wire:key="note-{{ $note->id }}"
                x-data="{
                    x: {{ $note->position_x }},
                    y: {{ $note->position_y }},
                    dragging: false,
                    offsetX: 0,
                    offsetY: 0,
                    startDrag(e) {
                        if (e.target.closest('textarea, button')) return;
                        this.dragging = true;
                        this.offsetX = e.clientX - this.x;
                        this.offsetY = e.clientY - this.y;
                        e.preventDefault();
                    },
                    onDrag(e) {
                        if (! this.dragging) return;
                        const maxX = this.$el.parentElement.clientWidth - this.$el.offsetWidth;
                        this.x = Math.min(Math.max(0, e.clientX - this.offsetX), Math.max(0, maxX));
                        this.y = Math.max(0, e.clientY - this.offsetY);
                    },
                    endDrag() {
                        if (! this.dragging) return;
                        this.dragging = false;
                        $wire.move({{ $note->id }}, Math.round(this.x), Math.round(this.y));
                    },
                }"
                x-on:pointerdown="startDrag($event)"
                x-on:pointermove.window="onDrag($event)"
                x-on:pointerup.window="endDrag($event)"
                x-bind:style="'transform: translate(' + x + 'px, ' + y + 'px) rotate(' + (dragging ? 0 : {{ ($note->id % 5) - 2 }} * 0.6) + 'deg); background-color: {{ $note->color }}; z-index: ' + (dragging ? 30 : {{ $note->is_pinned ? 20 : 10 }}) + ';'"
                x-bind:class="dragging ? 'shadow-2xl shadow-zinc-900/25 scale-[1.04]' : 'shadow-lg shadow-zinc-900/10 hover:shadow-xl'"
                class="absolute top-0 left-0 w-52 cursor-grab touch-none rounded-xl p-3 transition-[box-shadow,scale] duration-200 select-none active:cursor-grabbing
                    {{ $note->is_pinned ? 'ring-2 ring-amber-500/80' : 'ring-1 ring-black/5' }}
                    {{ $note->project_id === null ? 'outline-2 outline-offset-[-6px] outline-dashed outline-slate-700/25' : '' }}"
            >
                <div class="mb-1 flex items-start justify-between gap-1">
                    @if ($note->project_id === null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-black/5 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-slate-700/80 uppercase">
                            <x-heroicon-m-globe-alt class="size-3" />
                            General
                        </span>
                    @else
                        <span></span>
                    @endif

                    <div class="flex items-center gap-0.5">
                        <button
                            type="button"
                            wire:click="togglePin({{ $note->id }})"
                            wire:loading.attr="disabled"
                            wire:target="togglePin({{ $note->id }})"
                            x-on:pointerdown.stop
                            title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}"
                            class="rounded-md p-1 text-slate-700/60 transition hover:bg-black/5 hover:text-slate-900 data-loading:opacity-50"
                        >
                            @if ($note->is_pinned)
                                <x-heroicon-s-bookmark class="size-3.5" />
                            @else
                                <x-heroicon-o-bookmark class="size-3.5" />
                            @endif
                        </button>

                        <button
                            type="button"
                            wire:click="delete({{ $note->id }})"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $note->id }})"
                            x-on:pointerdown.stop
                            title="Delete note"
                            class="rounded-md p-1 text-slate-700/60 transition hover:bg-black/5 hover:text-red-600 data-loading:opacity-50"
                        >
                            <x-heroicon-o-x-mark class="size-3.5" />
                        </button>
                    </div>
                </div>

                <textarea
                    x-on:pointerdown.stop
                    wire:blur="updateBody({{ $note->id }}, $event.target.value)"
                    rows="4"
                    placeholder="Write a note…"
                    class="w-full resize-none bg-transparent text-sm leading-relaxed text-slate-800 placeholder:text-slate-800/50 focus:outline-none"
                >{{ $note->body }}</textarea>
            </div>
        @endforeach
    </div>
</div>
