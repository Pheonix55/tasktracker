<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Livewire\Livewire;

test('dragging a task to a new column updates its status and position', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'position' => 0]);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('handleSort', $task->id, 0, TaskStatus::InProgress->value);

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::InProgress);
    expect($task->position)->toBe(0);
});

test('dragging a task into the done column sets completed_at', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'completed_at' => null]);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('handleSort', $task->id, 0, TaskStatus::Done->value);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('dragging a task out of the done column clears completed_at', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done, 'completed_at' => now()]);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('handleSort', $task->id, 0, TaskStatus::Todo->value);

    expect($task->fresh()->completed_at)->toBeNull();
});

test('sibling positions are re-sequenced within the destination column', function () {
    $project = Project::factory()->create();

    $a = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'position' => 0]);
    $b = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'position' => 1]);
    $incoming = Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::InProgress, 'position' => 0]);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('handleSort', $incoming->id, 0, TaskStatus::Todo->value);

    expect($incoming->fresh())->status->toBe(TaskStatus::Todo)->position->toBe(0);
    expect($a->fresh()->position)->toBe(1);
    expect($b->fresh()->position)->toBe(2);
});
