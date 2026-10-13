<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Livewire\Livewire;

test('the dashboard shows accurate project and task counts', function () {
    $project = Project::factory()->create();

    Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'due_date' => null]);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done, 'due_date' => null]);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'due_date' => now()->subDay()]);

    $component = Livewire::test('pages::dashboard');

    expect($component->instance()->projectCount)->toBe(1);
    expect($component->instance()->openTaskCount)->toBe(3);
    expect($component->instance()->completedTaskCount)->toBe(1);
    expect($component->instance()->overdueTaskCount)->toBe(1);
});

test('due soon tasks are ordered by due date and limited to the next 7 days', function () {
    $project = Project::factory()->create();

    $soon = Task::factory()->create(['project_id' => $project->id, 'title' => 'Due tomorrow', 'due_date' => now()->addDay(), 'status' => TaskStatus::Todo]);
    $later = Task::factory()->create(['project_id' => $project->id, 'title' => 'Due in 3 days', 'due_date' => now()->addDays(3), 'status' => TaskStatus::Todo]);
    Task::factory()->create(['project_id' => $project->id, 'title' => 'Due next month', 'due_date' => now()->addMonth(), 'status' => TaskStatus::Todo]);

    Livewire::test('pages::dashboard')
        ->assertSeeInOrder([$soon->title, $later->title])
        ->assertDontSee('Due next month');
});

test('a task in a trashed project is excluded from dashboard counts', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo]);
    $project->delete();

    expect(Livewire::test('pages::dashboard')->instance()->openTaskCount)->toBe(0);
});

test('generating today\'s summary mentions due-today, overdue, and completed-today tasks', function () {
    $project = Project::factory()->create();

    Task::factory()->create(['project_id' => $project->id, 'title' => 'Due Today Task', 'status' => TaskStatus::Todo, 'due_date' => now()]);
    Task::factory()->create(['project_id' => $project->id, 'title' => 'Overdue Task', 'status' => TaskStatus::Todo, 'due_date' => now()->subDay()]);
    Task::factory()->create(['project_id' => $project->id, 'title' => 'Completed Task', 'status' => TaskStatus::Done, 'completed_at' => now()]);

    Livewire::test('pages::dashboard')
        ->call('generateTodaySummary')
        ->assertSet('todaySummary', fn (string $summary) => str_contains($summary, 'Due Today Task'))
        ->assertSet('todaySummary', fn (string $summary) => str_contains($summary, 'Overdue Task'))
        ->assertSet('todaySummary', fn (string $summary) => str_contains($summary, 'Completed Task'));
});

test('today\'s summary reports a calm day when nothing needs attention', function () {
    Livewire::test('pages::dashboard')
        ->call('generateTodaySummary')
        ->assertSet('todaySummary', fn (string $summary) => str_contains($summary, 'Nothing needs your attention right now'));
});

test('today\'s summary is cleared when a task is updated', function () {
    Livewire::test('pages::dashboard')
        ->call('generateTodaySummary')
        ->assertSet('todaySummary', fn (?string $summary) => $summary !== null)
        ->dispatch('task-updated')
        ->assertSet('todaySummary', null);
});

test('tasks in an archived project are excluded from dashboard counts', function () {
    $project = Project::factory()->archived()->create();
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo, 'due_date' => now()->subDay()]);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done]);

    $dashboard = Livewire::test('pages::dashboard')->instance();

    expect($dashboard->openTaskCount)->toBe(0);
    expect($dashboard->overdueTaskCount)->toBe(0);
    expect($dashboard->completedTaskCount)->toBe(0);
});

test('today\'s summary lists tasks due today from highest to lowest priority', function () {
    $project = Project::factory()->create();

    foreach ([TaskPriority::Low, TaskPriority::High, TaskPriority::Medium, TaskPriority::Urgent] as $priority) {
        Task::factory()->create([
            'project_id' => $project->id,
            'title' => "{$priority->label()} Task",
            'priority' => $priority,
            'status' => TaskStatus::Todo,
            'due_date' => today(),
        ]);
    }

    Livewire::test('pages::dashboard')
        ->call('generateTodaySummary')
        ->assertSet('todaySummary', fn (string $summary) => str_contains($summary, 'Urgent Task, High Task, Medium Task and Low Task'));
});
