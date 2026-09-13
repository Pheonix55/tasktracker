<?php

use App\Models\Project;
use Livewire\Livewire;

test('a project can be favorited and unfavorited from the index', function () {
    $project = Project::factory()->create(['is_favorite' => false]);

    Livewire::test('pages::projects.index')
        ->call('toggleFavorite', $project->id);

    expect($project->fresh()->is_favorite)->toBeTrue();

    Livewire::test('pages::projects.index')
        ->call('toggleFavorite', $project->id);

    expect($project->fresh()->is_favorite)->toBeFalse();
});

test('a project can be archived and unarchived from the index', function () {
    $project = Project::factory()->create(['is_archived' => false]);

    Livewire::test('pages::projects.index')
        ->call('toggleArchive', $project->id);

    expect($project->fresh()->is_archived)->toBeTrue();
});

test('a project can be favorited and archived from the kanban board header', function () {
    $project = Project::factory()->create(['is_favorite' => false, 'is_archived' => false]);

    Livewire::test('pages::projects.show', ['project' => $project])
        ->call('toggleFavorite')
        ->call('toggleArchive');

    expect($project->fresh())
        ->is_favorite->toBeTrue()
        ->is_archived->toBeTrue();
});
