<?php

use App\Enums\AiProvider;
use App\Models\AiSetting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('a task description is generated with the project as context', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Write the launch email and schedule it.']]],
        ]),
    ]);
    AiSetting::factory()->provider(AiProvider::Groq)->active()->create();
    $project = Project::factory()->create(['name' => 'Product Launch', 'description' => 'Release v2 to customers']);
    $task = Task::factory()->for($project)->create(['title' => 'Announce release', 'description' => null]);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->call('generateDescription')
        ->assertSet('description', 'Write the launch email and schedule it.')
        ->assertSet('aiError', null);

    Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'Product Launch')
        && str_contains($request['messages'][1]['content'], 'Release v2 to customers')
        && str_contains($request['messages'][1]['content'], 'Announce release'));
    expect($task->fresh()->description)->toBeNull();
});

test('an existing task description is sent so it can be improved', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Improved task text.']]],
        ]),
    ]);
    AiSetting::factory()->provider(AiProvider::Groq)->active()->create();
    $task = Task::factory()->create(['description' => 'email ppl']);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->call('generateDescription')
        ->assertSet('description', 'Improved task text.');

    Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'email ppl'));
});

test('generating a task description without a provider shows an error', function () {
    Http::preventStrayRequests();
    $task = Task::factory()->create(['description' => 'original']);

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->call('generateDescription')
        ->assertSet('description', 'original')
        ->assertSet('aiError', 'No AI provider is set up yet. Choose one in Settings.');

    Http::assertNothingSent();
});

test('generating a task description requires a title', function () {
    Http::preventStrayRequests();
    AiSetting::factory()->active()->create();
    $task = Task::factory()->create();

    Livewire::test('tasks.detail-modal')
        ->call('open', $task->id)
        ->set('title', '')
        ->call('generateDescription')
        ->assertHasErrors(['title' => 'required']);

    Http::assertNothingSent();
});
