<?php

use App\Enums\AiProvider;
use App\Models\AiSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('saving a provider stores its key encrypted and makes it the only active one', function () {
    $previous = AiSetting::factory()->provider(AiProvider::Groq)->active()->create();

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Gemini->value)
        ->set('apiKey', 'gemini-secret')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Google Gemini is now used for AI suggestions.');

    $gemini = AiSetting::where('provider', AiProvider::Gemini)->first();
    expect($gemini)
        ->api_key->toBe('gemini-secret')
        ->model->toBe('gemini-2.5-flash')
        ->is_active->toBeTrue();
    expect(DB::table('ai_settings')->where('id', $gemini->id)->value('api_key'))->not->toBe('gemini-secret');
    expect($previous->fresh()->is_active)->toBeFalse();
});

test('a provider that needs a key cannot be saved without one', function () {
    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('apiKey', '')
        ->call('save')
        ->assertHasErrors(['apiKey' => 'required']);

    expect(AiSetting::count())->toBe(0);
});

test('a local provider can be saved without a key', function () {
    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Ollama->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(AiSetting::active())
        ->provider->toBe(AiProvider::Ollama)
        ->base_url->toBe('http://localhost:11434/v1')
        ->api_key->toBeNull();
});

test('cloudflare requires an account specific base url', function () {
    Livewire::test('pages::settings')
        ->set('provider', AiProvider::CloudflareWorkersAi->value)
        ->set('apiKey', 'cf-token')
        ->call('save')
        ->assertHasErrors(['baseUrl' => 'required']);
});

test('switching providers keeps each provider\'s saved key', function () {
    AiSetting::factory()->provider(AiProvider::Groq)->create(['model' => 'my-groq-model']);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->assertSet('model', 'my-groq-model')
        ->assertSet('hasSavedKey', true)
        ->set('provider', AiProvider::Mistral->value)
        ->assertSet('model', 'mistral-small-latest')
        ->assertSet('hasSavedKey', false);
});

test('updating a provider with a blank key keeps the saved key', function () {
    AiSetting::factory()->provider(AiProvider::Groq)->create(['api_key' => 'kept-key']);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('model', 'other-model')
        ->call('save')
        ->assertHasNoErrors();

    expect(AiSetting::where('provider', AiProvider::Groq)->first())
        ->api_key->toBe('kept-key')
        ->model->toBe('other-model');
});

test('test connection calls the provider with the typed key and shows the reply', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Hello there!']]],
        ]),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('apiKey', 'groq-key')
        ->call('test')
        ->assertSet('testPassed', true)
        ->assertSee('Hello there!');

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer groq-key')
        && $request['model'] === 'llama-3.3-70b-versatile');
    expect(AiSetting::count())->toBe(0);
});

test('test connection reports a rejected key', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => ['message' => 'Invalid API Key']], 401),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('apiKey', 'bad-key')
        ->call('test')
        ->assertSet('testPassed', false)
        ->assertSee('Groq rejected the API key. Invalid API Key');
});

test('the saved key can be removed', function () {
    $setting = AiSetting::factory()->provider(AiProvider::Groq)->active()->create();

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->call('removeKey')
        ->assertSet('hasSavedKey', false);

    expect($setting->fresh()->api_key)->toBeNull();
});

test('ai suggestions can be turned off', function () {
    $setting = AiSetting::factory()->active()->create();

    Livewire::test('pages::settings')
        ->call('disable')
        ->assertSee('AI suggestions are turned off.');

    expect($setting->fresh()->is_active)->toBeFalse();
});

test('the settings page renders', function () {
    $this->get(route('settings'))->assertSee('AI suggestions');
});

test('models can be loaded from an openai compatible provider', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/openai/models' => Http::response([
            'data' => [
                ['id' => 'models/gemini-2.5-pro'],
                ['id' => 'models/gemini-2.5-flash'],
            ],
        ]),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Gemini->value)
        ->set('apiKey', 'gemini-key')
        ->call('loadModels')
        ->assertSet('availableModels', ['gemini-2.5-flash', 'gemini-2.5-pro'])
        ->assertSet('testPassed', true)
        ->assertSee('Found 2 models.');

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer gemini-key'));
});

test('claude models are loaded with anthropic headers', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.anthropic.com/v1/models?limit=1000' => Http::response([
            'data' => [['id' => 'claude-haiku-5-5'], ['id' => 'claude-sonnet-5-5']],
        ]),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Anthropic->value)
        ->set('apiKey', 'claude-key')
        ->call('loadModels')
        ->assertSet('availableModels', ['claude-haiku-5-5', 'claude-sonnet-5-5']);

    Http::assertSent(fn (Request $request) => $request->hasHeader('x-api-key', 'claude-key')
        && $request->hasHeader('anthropic-version', '2023-06-01'));
});

test('github models are loaded from the catalog', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://models.github.ai/catalog/models' => Http::response([
            ['id' => 'openai/gpt-4.1-mini'],
            ['id' => 'meta/llama-3.3-70b-instruct'],
        ]),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::GitHubModels->value)
        ->set('apiKey', 'github-token')
        ->call('loadModels')
        ->assertSet('availableModels', ['meta/llama-3.3-70b-instruct', 'openai/gpt-4.1-mini']);
});

test('loading models with the saved key works without retyping it', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/models' => Http::response(['data' => [['id' => 'llama-3.3-70b-versatile']]]),
    ]);
    AiSetting::factory()->provider(AiProvider::Groq)->create(['api_key' => 'saved-groq-key']);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->call('loadModels')
        ->assertSet('availableModels', ['llama-3.3-70b-versatile']);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer saved-groq-key'));
});

test('loading models needs a key first', function () {
    Http::preventStrayRequests();

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->call('loadModels')
        ->assertHasErrors(['apiKey' => 'required']);

    Http::assertNothingSent();
});

test('a failed model list is reported and clears earlier results', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/openai/v1/models' => Http::response(['error' => ['message' => 'Invalid API Key']], 401),
    ]);

    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('apiKey', 'bad-key')
        ->set('availableModels', ['stale-model'])
        ->call('loadModels')
        ->assertSet('availableModels', [])
        ->assertSet('testPassed', false)
        ->assertSee('Groq rejected the API key. Invalid API Key');
});

test('switching providers clears loaded models', function () {
    Livewire::test('pages::settings')
        ->set('provider', AiProvider::Groq->value)
        ->set('availableModels', ['llama-3.3-70b-versatile'])
        ->set('provider', AiProvider::Mistral->value)
        ->assertSet('availableModels', []);
});

test('cloudflare does not offer model loading', function () {
    Livewire::test('pages::settings')
        ->set('provider', AiProvider::CloudflareWorkersAi->value)
        ->assertDontSee('Load models');
});
