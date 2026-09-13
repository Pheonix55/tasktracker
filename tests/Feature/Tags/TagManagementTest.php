<?php

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('a tag can be created', function () {
    Livewire::test('pages::tags.index')
        ->set('newTagName', 'Bug')
        ->set('newTagColor', '#f43f5e')
        ->call('create');

    expect(Tag::where('name', 'Bug')->where('color', '#f43f5e')->exists())->toBeTrue();
});

test('a tag name must be unique', function () {
    Tag::factory()->create(['name' => 'Bug']);

    Livewire::test('pages::tags.index')
        ->set('newTagName', 'Bug')
        ->call('create')
        ->assertHasErrors(['newTagName' => 'unique']);
});

test('a tag can be renamed and recolored', function () {
    $tag = Tag::factory()->create(['name' => 'Old', 'color' => '#000000']);

    Livewire::test('pages::tags.index')
        ->call('edit', $tag->id)
        ->set('editingName', 'New')
        ->set('editingColor', '#22c55e')
        ->call('update');

    expect($tag->fresh())->name->toBe('New')->color->toBe('#22c55e');
});

test('deleting a tag detaches it from tasks', function () {
    $tag = Tag::factory()->create();
    $task = Task::factory()->create();
    $task->tags()->attach($tag);

    Livewire::test('pages::tags.index')
        ->call('delete', $tag->id);

    expect(Tag::find($tag->id))->toBeNull();
    expect(DB::table('tag_task')->where('tag_id', $tag->id)->exists())->toBeFalse();
    expect(Task::find($task->id))->not->toBeNull();
});

test('the tags index shows how many tasks use each tag', function () {
    $tag = Tag::factory()->create(['name' => 'Feature']);
    Task::factory()->count(3)->create()->each(fn (Task $task) => $task->tags()->attach($tag));

    Livewire::test('pages::tags.index')
        ->assertSee('Feature')
        ->assertSee('3 tasks');
});
