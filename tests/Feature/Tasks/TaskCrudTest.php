<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Livewire\Livewire;

test('opening the detail modal loads the task fields', function () {
    $task = Task::factory()->create([
        'title' => 'Ship the release',
        'priority' => TaskPriority::High,
        'status' => TaskStatus::Todo,
    ]);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->assertSet('title', 'Ship the release')
        ->assertSet('priority', TaskPriority::High->value)
        ->assertSet('status', TaskStatus::Todo->value);
});

test('a task can be updated from the detail modal', function () {
    $task = Task::factory()->create(['title' => 'Old title']);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->set('title', 'New title')
        ->set('priority', TaskPriority::Urgent->value)
        ->set('status', TaskStatus::Done->value)
        ->call('save')
        ->assertDispatched('task-updated');

    $task->refresh();

    expect($task->title)->toBe('New title');
    expect($task->priority)->toBe(TaskPriority::Urgent);
    expect($task->status)->toBe(TaskStatus::Done);
    expect($task->completed_at)->not->toBeNull();
});

test('the detail modal requires a title', function () {
    $task = Task::factory()->create();

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('a task is overdue when its due date has passed and it is not done', function () {
    $overdue = Task::factory()->create(['due_date' => now()->subDay(), 'status' => TaskStatus::Todo]);
    $notOverdueBecauseDone = Task::factory()->create(['due_date' => now()->subDay(), 'status' => TaskStatus::Done]);
    $notOverdueBecauseFuture = Task::factory()->create(['due_date' => now()->addDay(), 'status' => TaskStatus::Todo]);

    expect($overdue->isOverdue)->toBeTrue();
    expect($notOverdueBecauseDone->isOverdue)->toBeFalse();
    expect($notOverdueBecauseFuture->isOverdue)->toBeFalse();
});
