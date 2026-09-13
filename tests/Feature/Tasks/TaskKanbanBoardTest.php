<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Livewire\Livewire;

test('tasks are grouped into their status columns', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'title' => 'Todo Task']);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done, 'title' => 'Done Task']);

    $tasksByStatus = Livewire::test('pages::projects.show', ['project' => $project])
        ->assertSee('Todo Task')
        ->assertSee('Done Task')
        ->instance()->tasksByStatus;

    expect($tasksByStatus->get(TaskStatus::Todo->value)->pluck('title'))->toContain('Todo Task');
    expect($tasksByStatus->get(TaskStatus::Done->value)->pluck('title'))->toContain('Done Task');
});

test('the board can be filtered by tag', function () {
    $project = Project::factory()->create();
    $tag = Tag::factory()->create();

    $tagged = Task::factory()->create(['project_id' => $project->id, 'title' => 'Tagged Task']);
    $tagged->tags()->attach($tag);

    Task::factory()->create(['project_id' => $project->id, 'title' => 'Untagged Task']);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->assertSee('Tagged Task')
        ->assertSee('Untagged Task')
        ->call('toggleTagFilter', $tag->id)
        ->assertSee('Tagged Task')
        ->assertDontSee('Untagged Task');
});

test('a task can be quick-added to a column', function () {
    $project = Project::factory()->create();

    Livewire::test('pages::projects.show', ['project' => $project])
        ->set('newTaskTitle.todo', 'Write the release notes')
        ->call('quickAdd', 'todo');

    expect(Task::where('title', 'Write the release notes')->where('status', TaskStatus::Todo)->exists())->toBeTrue();
});
