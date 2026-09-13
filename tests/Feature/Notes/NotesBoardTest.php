<?php

use App\Models\Note;
use App\Models\Project;
use Livewire\Livewire;

test('a project-scoped note can be added to the board', function () {
    $project = Project::factory()->create();

    Livewire::test('notes.board', ['project' => $project])
        ->call('addNote');

    expect(Note::where('project_id', $project->id)->exists())->toBeTrue();
});

test('a general note can be added and is not scoped to any project', function () {
    $project = Project::factory()->create();

    Livewire::test('notes.board', ['project' => $project])
        ->call('addGeneralNote');

    $note = Note::first();

    expect($note)->not->toBeNull();
    expect($note->project_id)->toBeNull();
});

test('a general note appears on every project board, project notes only on their own', function () {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $general = Note::factory()->general()->create(['body' => 'General note']);
    $scoped = Note::factory()->create(['project_id' => $projectA->id, 'body' => 'Scoped note']);

    Livewire::test('notes.board', ['project' => $projectA])
        ->assertSee('General note')
        ->assertSee('Scoped note');

    Livewire::test('notes.board', ['project' => $projectB])
        ->assertSee('General note')
        ->assertDontSee('Scoped note');
});

test('a note can be deleted', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create(['project_id' => $project->id]);

    Livewire::test('notes.board', ['project' => $project])
        ->call('delete', $note->id);

    expect(Note::find($note->id))->toBeNull();
});

test('a note can be moved and its position persists', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create(['project_id' => $project->id, 'position_x' => 0, 'position_y' => 0]);

    Livewire::test('notes.board', ['project' => $project])
        ->call('move', $note->id, 120, 340);

    $note->refresh();

    expect($note->position_x)->toBe(120);
    expect($note->position_y)->toBe(340);
});

test('a note can be pinned and unpinned', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create(['project_id' => $project->id, 'is_pinned' => false]);

    Livewire::test('notes.board', ['project' => $project])
        ->call('togglePin', $note->id);

    expect($note->fresh()->is_pinned)->toBeTrue();

    Livewire::test('notes.board', ['project' => $project])
        ->call('togglePin', $note->id);

    expect($note->fresh()->is_pinned)->toBeFalse();
});

test('pinned notes are listed before unpinned notes', function () {
    $project = Project::factory()->create();
    $unpinned = Note::factory()->create(['project_id' => $project->id, 'body' => 'Unpinned']);
    $pinned = Note::factory()->pinned()->create(['project_id' => $project->id, 'body' => 'Pinned']);

    $notes = Livewire::test('notes.board', ['project' => $project])
        ->instance()->notes;

    expect($notes->first()->id)->toBe($pinned->id);
    expect($notes->last()->id)->toBe($unpinned->id);
});

test("a note's body can be edited", function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create(['project_id' => $project->id, 'body' => 'Old text']);

    Livewire::test('notes.board', ['project' => $project])
        ->call('updateBody', $note->id, 'New text');

    expect($note->fresh()->body)->toBe('New text');
});
