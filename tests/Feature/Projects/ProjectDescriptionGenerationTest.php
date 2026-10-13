<?php

use App\Enums\AiProvider;
use App\Models\AiSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('a description is generated with an openai compatible provider', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '"Rebuild the marketing site for speed."']]],
        ]),
    ]);
    AiSetting::factory()->provider(AiProvider::Gemini)->active()->create(['api_key' => 'gemini-key']);

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->call('generateDescription')
        ->assertSet('description', 'Rebuild the marketing site for speed.')
        ->assertSet('aiError', null);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer gemini-key')
        && str_contains($request['messages'][1]['content'], 'Website Redesign'));
});

test('a description is generated with claude through the messages api', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'stop_reason' => 'end_turn',
            'content' => [
                ['type' => 'thinking', 'thinking' => ''],
                ['type' => 'text', 'text' => 'Plan and ship the new mobile app.'],
            ],
        ]),
    ]);
    AiSetting::factory()->provider(AiProvider::Anthropic)->active()->create(['api_key' => 'claude-key']);

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Mobile App')
        ->call('generateDescription')
        ->assertSet('description', 'Plan and ship the new mobile app.');

    Http::assertSent(fn (Request $request) => $request->hasHeader('x-api-key', 'claude-key')
        && $request->hasHeader('anthropic-version', '2023-06-01')
        && $request['model'] === 'claude-haiku-5-5');
});

test('an existing description is sent so it can be improved', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Improved text.']]],
        ]),
    ]);
    AiSetting::factory()->provider(AiProvider::Groq)->active()->create();

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->set('description', 'make site faster')
        ->call('generateDescription')
        ->assertSet('description', 'Improved text.');

    Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'make site faster'));
});

test('generating without a configured provider shows an error and keeps the description', function () {
    Http::preventStrayRequests();

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->set('description', 'original')
        ->call('generateDescription')
        ->assertSet('description', 'original')
        ->assertSet('aiError', 'No AI provider is set up yet. Choose one in Settings.');

    Http::assertNothingSent();
});

test('generating requires a project name', function () {
    Http::preventStrayRequests();
    AiSetting::factory()->active()->create();

    Livewire::test('projects.form-modal')
        ->call('open')
        ->call('generateDescription')
        ->assertHasErrors(['name' => 'required']);

    Http::assertNothingSent();
});

test('an unreachable local model shows a helpful error', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:11434/v1/chat/completions' => Http::failedConnection(),
    ]);
    AiSetting::factory()->provider(AiProvider::Ollama)->active()->create([
        'api_key' => null,
        'base_url' => 'http://localhost:11434/v1',
    ]);

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->call('generateDescription')
        ->assertSet('aiError', "Couldn't reach Ollama (local) at http://localhost:11434/v1. Is it running?");
});

test('a claude refusal is reported instead of saved as the description', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response(['stop_reason' => 'refusal', 'content' => []]),
    ]);
    AiSetting::factory()->provider(AiProvider::Anthropic)->active()->create();

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->call('generateDescription')
        ->assertSet('description', '')
        ->assertSet('aiError', 'Claude declined this request. Try rewording it.');
});

test('an unknown model error points the user to loading models', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'error' => ['message' => 'The model `retired-model` does not exist or you do not have access to it.'],
        ], 404),
    ]);
    AiSetting::factory()->provider(AiProvider::Groq)->active()->create(['model' => 'retired-model']);

    Livewire::test('projects.form-modal')
        ->call('open')
        ->set('name', 'Website Redesign')
        ->call('generateDescription')
        ->assertSet('aiError', 'Groq returned an error (404). The model `retired-model` does not exist or you do not have access to it. Use "Load models" in Settings to pick a model your key can use.');
});
