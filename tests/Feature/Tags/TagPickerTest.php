<?php

use App\Models\Tag;
use App\Models\Task;
use Livewire\Livewire;

test('a tag can be attached and detached from a task', function () {
    $task = Task::factory()->create();
    $tag = Tag::factory()->create();

    Livewire::test('tags.picker', ['task' => $task])
        ->call('toggleTag', $tag->id);

    expect($task->tags()->where('tags.id', $tag->id)->exists())->toBeTrue();

    Livewire::test('tags.picker', ['task' => $task])
        ->call('toggleTag', $tag->id);

    expect($task->tags()->where('tags.id', $tag->id)->exists())->toBeFalse();
});

test('a new tag can be created and attached inline from the picker', function () {
    $task = Task::factory()->create();

    Livewire::test('tags.picker', ['task' => $task])
        ->set('newTagName', 'Design')
        ->call('createTag');

    $tag = Tag::where('name', 'Design')->first();

    expect($tag)->not->toBeNull();
    expect($task->tags()->where('tags.id', $tag->id)->exists())->toBeTrue();
});

test('the picker requires a unique tag name', function () {
    Tag::factory()->create(['name' => 'Design']);
    $task = Task::factory()->create();

    Livewire::test('tags.picker', ['task' => $task])
        ->set('newTagName', 'Design')
        ->call('createTag')
        ->assertHasErrors(['newTagName' => 'unique']);
});
