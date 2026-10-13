<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
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

test('closing the detail modal dispatches a board refresh', function () {
    $task = Task::factory()->create();

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->call('close')
        ->assertDispatched('task-updated');
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

test('a task due today is not overdue and is overdue once the day has passed', function () {
    $this->travelTo(now()->setTime(15, 0));

    $task = Task::factory()->create(['due_date' => today(), 'status' => TaskStatus::Todo]);

    expect($task->fresh()->isOverdue)->toBeFalse();

    $this->travel(1)->days();

    expect($task->fresh()->isOverdue)->toBeTrue();
});

test('changing a task status in the detail modal moves it to the bottom of the new column', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::InProgress, 'position' => 0]);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::InProgress, 'position' => 1]);
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'position' => 0]);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->set('status', TaskStatus::InProgress->value)
        ->call('save');

    expect($task->fresh())->status->toBe(TaskStatus::InProgress)->position->toBe(2);
});

test('saving the detail modal without changing status keeps the task position', function () {
    $task = Task::factory()->create(['status' => TaskStatus::Todo, 'position' => 3]);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->set('title', 'Renamed')
        ->call('save');

    expect($task->fresh()->position)->toBe(3);
});
