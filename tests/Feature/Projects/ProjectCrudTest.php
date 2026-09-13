<?php

use App\Models\Project;
use Livewire\Livewire;

test('a project can be created via the form modal', function () {
    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'New Website')
        ->set('description', 'Rebuild the marketing site')
        ->set('color', '#3b82f6')
        ->set('icon', 'folder')
        ->call('save')
        ->assertDispatched('project-saved');

    expect(Project::where('name', 'New Website')->first())
        ->not->toBeNull()
        ->description->toBe('Rebuild the marketing site')
        ->color->toBe('#3b82f6')
        ->icon->toBe('folder');
});

test('the form requires a name', function () {
    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('an existing project can be edited via the form modal', function () {
    $project = Project::factory()->create(['name' => 'Old Name']);

    Livewire::test('projects.form-modal')
        ->call('open', $project->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertDispatched('project-saved');

    expect($project->fresh()->name)->toBe('Updated Name');
});

test('the projects index can be searched by name', function () {
    Project::factory()->create(['name' => 'Website Redesign']);
    Project::factory()->create(['name' => 'Mobile App']);

    Livewire::test('pages::projects.index')
        ->set('search', 'Website')
        ->assertSee('Website Redesign')
        ->assertDontSee('Mobile App');
});

test('archived projects are hidden by default and shown when toggled', function () {
    Project::factory()->archived()->create(['name' => 'Retired Project']);

    Livewire::test('pages::projects.index')
        ->assertDontSee('Retired Project')
        ->set('showArchived', true)
        ->assertSee('Retired Project');
});
