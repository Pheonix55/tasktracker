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
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold flex items-center gap-2">
            <x-heroicon-o-rectangle-stack class="w-4 h-4" />
            Sticky Notes
        </h2>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="addNote"
                wire:loading.attr="disabled"
                wire:target="addNote"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:text-current data-loading:opacity-50"
            >
                <x-heroicon-o-plus class="w-3.5 h-3.5" />
                Note
            </button>

            <button
                type="button"
                wire:click="addGeneralNote"
                wire:loading.attr="disabled"
                wire:target="addGeneralNote"
                title="General notes appear on every project's board"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:text-current data-loading:opacity-50"
            >
                <x-heroicon-o-globe-alt class="w-3.5 h-3.5" />
                General Note
            </button>
        </div>
    </div>

    <div class="relative w-full min-h-[220px] rounded-lg border border-dashed border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#f5f5f4] dark:bg-[#111110] p-2">
        @if ($this->notes->isEmpty())
            <p class="absolute inset-0 flex items-center justify-center text-xs text-[#a1a09a]">
                No notes yet — add one above.
            </p>
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
                x-bind:style="'transform: translate(' + x + 'px, ' + y + 'px); background-color: {{ $note->color }}; z-index: ' + (dragging ? 30 : {{ $note->is_pinned ? 20 : 10 }}) + ';'"
                x-bind:class="dragging ? 'shadow-xl scale-[1.03]' : 'shadow-md'"
                class="absolute top-0 left-0 w-48 rounded-md p-3 cursor-grab active:cursor-grabbing select-none touch-none transition-shadow
                    {{ $note->is_pinned ? 'ring-2 ring-amber-500' : '' }}
                    {{ $note->project_id === null ? 'border-2 border-dashed border-slate-600/40' : '' }}"
            >
                <div class="flex items-start justify-between gap-1 mb-1">
                    @if ($note->project_id === null)
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-700/70">General</span>
                    @else
                        <span></span>
                    @endif

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="togglePin({{ $note->id }})"
                            wire:loading.attr="disabled"
                            wire:target="togglePin({{ $note->id }})"
                            x-on:pointerdown.stop
                            title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}"
                            class="text-slate-700/60 hover:text-slate-900 data-loading:opacity-50"
                        >
                            @if ($note->is_pinned)
                                <x-heroicon-s-bookmark class="w-3.5 h-3.5" />
                            @else
                                <x-heroicon-o-bookmark class="w-3.5 h-3.5" />
                            @endif
                        </button>

                        <button
                            type="button"
                            wire:click="delete({{ $note->id }})"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $note->id }})"
                            x-on:pointerdown.stop
                            title="Delete note"
                            class="text-slate-700/60 hover:text-red-600 data-loading:opacity-50"
                        >
                            <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <textarea
                    x-on:pointerdown.stop
                    wire:blur="updateBody({{ $note->id }}, $event.target.value)"
                    rows="4"
                    placeholder="Write a note…"
                    class="w-full bg-transparent resize-none text-sm text-slate-800 placeholder:text-slate-800/50 focus:outline-none"
                >{{ $note->body }}</textarea>
            </div>
        @endforeach
    </div>
</div>
