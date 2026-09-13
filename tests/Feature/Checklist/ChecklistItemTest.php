<?php

use App\Models\ChecklistItem;
use App\Models\Task;
use Livewire\Livewire;

test('a checklist item can be added to a task', function () {
    $task = Task::factory()->create();

    Livewire::test('checklist.manager', ['task' => $task])
        ->set('newItemLabel', 'Write tests')
        ->call('add')
        ->assertDispatched('task-updated');

    expect($task->checklistItems()->where('label', 'Write tests')->exists())->toBeTrue();
});

test('a checklist item can be toggled complete', function () {
    $task = Task::factory()->create();
    $item = ChecklistItem::factory()->create(['task_id' => $task->id, 'is_completed' => false]);

    Livewire::test('checklist.manager', ['task' => $task])
        ->call('toggle', $item->id)
        ->assertDispatched('task-updated');

    expect($item->fresh()->is_completed)->toBeTrue();
});

test('a checklist item can be removed', function () {
    $task = Task::factory()->create();
    $item = ChecklistItem::factory()->create(['task_id' => $task->id]);

    Livewire::test('checklist.manager', ['task' => $task])
        ->call('remove', $item->id);

    expect(ChecklistItem::find($item->id))->toBeNull();
});

test('checklist items can be reordered', function () {
    $task = Task::factory()->create();
    $first = ChecklistItem::factory()->create(['task_id' => $task->id, 'position' => 0]);
    $second = ChecklistItem::factory()->create(['task_id' => $task->id, 'position' => 1]);

    Livewire::test('checklist.manager', ['task' => $task])
        ->call('handleSort', $second->id, 0);

    expect($second->fresh()->position)->toBe(0);
    expect($first->fresh()->position)->toBe(1);
});

test('checklist progress reflects completed vs total items', function () {
    $task = Task::factory()->create();
    ChecklistItem::factory()->create(['task_id' => $task->id, 'is_completed' => true]);
    ChecklistItem::factory()->create(['task_id' => $task->id, 'is_completed' => false]);

    expect($task->checklistProgress)->toBe(['completed' => 1, 'total' => 2]);
});
