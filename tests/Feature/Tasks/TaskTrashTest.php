<?php

use App\Models\Task;
use Livewire\Livewire;

test('moving a task to trash from the detail modal soft deletes it', function () {
    $task = Task::factory()->create();

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->call('moveToTrash')
        ->assertDispatched('task-updated');

    expect(Task::find($task->id))->toBeNull();
    expect(Task::onlyTrashed()->find($task->id))->not->toBeNull();
});

test('a trashed task appears in the trash and can be restored', function () {
    $task = Task::factory()->create();
    $task->delete();

    Livewire::test('pages::trash')
        ->assertSee($task->title)
        ->call('restoreTask', $task->id);

    expect(Task::find($task->id))->not->toBeNull();
});

test('a trashed task can be permanently deleted', function () {
    $task = Task::factory()->create();
    $task->delete();

    Livewire::test('pages::trash')
        ->call('forceDeleteTask', $task->id);

    expect(Task::withTrashed()->find($task->id))->toBeNull();
});
