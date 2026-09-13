<?php

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('moving a project to trash soft deletes it and it appears in the trash list', function () {
    $project = Project::factory()->create();

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('moveToTrash')
        ->assertRedirect(route('projects.index'));

    expect(Project::find($project->id))->toBeNull();
    expect(Project::onlyTrashed()->find($project->id))->not->toBeNull();

    Livewire::test('pages::trash')
        ->assertSee($project->name);
});

test('a trashed project can be restored', function () {
    $project = Project::factory()->create();
    $project->delete();

    Livewire::test('pages::trash')
        ->call('restoreProject', $project->id);

    expect(Project::find($project->id))->not->toBeNull();
});

test('force deleting a project cascades to its tasks, checklist items, and tag attachments', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);
    $item = ChecklistItem::factory()->create(['task_id' => $task->id]);
    $tag = Tag::factory()->create();
    $task->tags()->attach($tag);

    $project->delete();

    Livewire::test('pages::trash')
        ->call('forceDeleteProject', $project->id);

    expect(Project::withTrashed()->find($project->id))->toBeNull();
    expect(Task::withTrashed()->find($task->id))->toBeNull();
    expect(ChecklistItem::find($item->id))->toBeNull();
    expect(Tag::find($tag->id))->not->toBeNull();
    expect(DB::table('tag_task')->where('task_id', $task->id)->exists())->toBeFalse();
});
