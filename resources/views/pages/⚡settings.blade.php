<?php

use App\Enums\AiProvider;
use App\Exceptions\AiRequestException;
use App\Models\AiSetting;
use App\Services\AiTextGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Settings')] class extends Component
{
    public string $provider = '';

    public string $model = '';

    public string $apiKey = '';

    public string $baseUrl = '';

    public bool $hasSavedKey = false;

    public ?string $testMessage = null;

    public bool $testPassed = false;

    public ?string $savedMessage = null;

    /**
     * Model IDs fetched from the provider, offered as suggestions in the model field.
     *
     * @var list<string>
     */
    public array $availableModels = [];

    public function mount(): void
    {
        $this->loadProvider((AiSetting::active()?->provider ?? AiProvider::Gemini)->value);
    }

    #[Computed]
    public function selectedProvider(): AiProvider
    {
        return AiProvider::tryFrom($this->provider) ?? AiProvider::Gemini;
    }

    #[Computed]
    public function activeSetting(): ?AiSetting
    {
        return AiSetting::active();
    }

    /**
     * Switching providers loads that provider's saved settings, so each keeps its own key.
     */
    public function updatedProvider(string $value): void
    {
        $this->loadProvider($value);
    }

    /**
     * Fill the form from the saved settings for a provider, or its defaults.
     */
    protected function loadProvider(string $value): void
    {
        $provider = AiProvider::tryFrom($value) ?? AiProvider::Gemini;
        $setting = AiSetting::where('provider', $provider)->first();

        $this->provider = $provider->value;
        $this->model = $setting?->model ?? $provider->defaultModel();
        $this->baseUrl = $setting?->base_url ?? $provider->defaultBaseUrl();
        $this->apiKey = '';
        $this->hasSavedKey = filled($setting?->api_key);
        $this->testMessage = null;
        $this->savedMessage = null;
        $this->availableModels = [];
        $this->resetValidation();
        unset($this->selectedProvider);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $provider = $this->selectedProvider;

        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'model' => ['required', 'string', 'max:255'],
            'apiKey' => [
                Rule::requiredIf($provider->requiresApiKey() && ! $this->hasSavedKey),
                'nullable',
                'string',
                'max:1000',
            ],
            'baseUrl' => [
                Rule::requiredIf($provider->hasEditableBaseUrl()),
                'nullable',
                'url:http,https',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'apiKey.required' => 'Enter your API key for this provider.',
        ];
    }

    /**
     * Build a setting from the form without saving it, reusing the saved key when none was typed.
     */
    protected function settingFromForm(): AiSetting
    {
        $saved = AiSetting::where('provider', $this->provider)->first();

        return new AiSetting([
            'provider' => $this->selectedProvider,
            'model' => trim($this->model),
            'api_key' => filled($this->apiKey) ? trim($this->apiKey) : $saved?->api_key,
            'base_url' => $this->selectedProvider->hasEditableBaseUrl() ? trim($this->baseUrl) : null,
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $form = $this->settingFromForm();

        $setting = AiSetting::updateOrCreate(
            ['provider' => $form->provider],
            [
                'model' => $form->model,
                'api_key' => $form->api_key,
                'base_url' => $form->base_url,
            ],
        );

        $setting->activate();

        $this->apiKey = '';
        $this->hasSavedKey = filled($setting->api_key);
        $this->testMessage = null;
        $this->savedMessage = "{$setting->provider->label()} is now used for AI suggestions.";
        unset($this->activeSetting);
    }

    public function test(AiTextGenerator $generator): void
    {
        $this->validate();
        $this->savedMessage = null;

        try {
            $reply = $generator->generate(
                'You are a connectivity check. Reply with a short friendly greeting of at most eight words.',
                'Say hello.',
                $this->settingFromForm(),
            );

            $this->testPassed = true;
            $this->testMessage = 'Connected. The model replied: "'.str($reply)->limit(120).'"';
        } catch (AiRequestException $exception) {
            $this->testPassed = false;
            $this->testMessage = $exception->getMessage();
        }
    }

    /**
     * Fetch the models this key can use so the user can pick one instead of typing it.
     */
    public function loadModels(AiTextGenerator $generator): void
    {
        $this->validateOnly('apiKey');
        $this->validateOnly('baseUrl');
        $this->savedMessage = null;

        try {
            $this->availableModels = $generator->listModels($this->settingFromForm());

            $this->testPassed = true;
            $this->testMessage = 'Found '.count($this->availableModels).' '.str('model')->plural(count($this->availableModels)).'. Pick one from the list below the model field.';
        } catch (AiRequestException $exception) {
            $this->availableModels = [];
            $this->testPassed = false;
            $this->testMessage = $exception->getMessage();
        }
    }

    public function removeKey(): void
    {
        AiSetting::where('provider', $this->provider)->update(['api_key' => null]);

        $this->apiKey = '';
        $this->hasSavedKey = false;
        $this->testMessage = null;
        $this->savedMessage = 'API key removed.';
    }

    public function disable(): void
    {
        AiSetting::query()->update(['is_active' => false]);

        $this->savedMessage = 'AI suggestions are turned off.';
        unset($this->activeSetting);
    }
};
?>

<div class="max-w-3xl space-y-6">
    <x-ui.page-header
        title="Settings"
        description="Connect your own AI provider to generate descriptions and suggestions."
        icon="cog-6-tooth"
        gradient="from-sky-500 to-indigo-500 shadow-sky-500/30"
    />

    <div class="card flex flex-wrap items-center justify-between gap-3 p-5">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                <x-heroicon-o-sparkles class="size-5" />
            </span>
            <div>
                <p class="text-sm font-medium">AI suggestions</p>
                <p class="text-muted text-xs">
                    @if ($this->activeSetting)
                        Using {{ $this->activeSetting->provider->label() }} · {{ $this->activeSetting->resolvedModel() }}
                    @else
                        Off — choose a provider below and save.
                    @endif
                </p>
            </div>
        </div>
        @if ($this->activeSetting)
            <button type="button" wire:click="disable" class="btn-secondary data-loading:opacity-70">
                Turn off
            </button>
        @endif
    </div>

    <form wire:submit="save" class="card space-y-5 p-5">
        <div>
            <label class="field-label" for="ai-provider">Provider</label>
            <select id="ai-provider" wire:model.live="provider" class="input-field">
                @foreach (\App\Enums\AiProvider::cases() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }} — {{ $option->pricing() }}</option>
                @endforeach
            </select>
            <p class="text-muted mt-1.5 flex items-center gap-1 text-xs">
                <x-heroicon-m-information-circle class="size-3.5 shrink-0" />
                {{ $this->selectedProvider->setupHint() }}
            </p>
        </div>

        <div wire:key="ai-fields-{{ $provider }}" class="space-y-5">
            @if ($this->selectedProvider->hasEditableBaseUrl())
                <div>
                    <label class="field-label" for="ai-base-url">Base URL</label>
                    <input
                        id="ai-base-url"
                        type="url"
                        wire:model="baseUrl"
                        class="input-field"
                        placeholder="{{ $this->selectedProvider->baseUrlPlaceholder() }}"
                    >
                    @error('baseUrl') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="field-label" for="ai-api-key">
                    API key
                    @unless ($this->selectedProvider->requiresApiKey())
                        <span class="text-muted font-normal">(optional)</span>
                    @endunless
                </label>
                <input
                    id="ai-api-key"
                    type="password"
                    wire:model="apiKey"
                    class="input-field"
                    autocomplete="off"
                    placeholder="{{ $hasSavedKey ? '•••••••• saved — type a new key to replace it' : 'Paste your API key' }}"
                >
                @error('apiKey') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
                @if ($hasSavedKey)
                    <button type="button" wire:click="removeKey" wire:confirm="Remove the saved API key for this provider?" class="mt-1.5 text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                        Remove saved key
                    </button>
                @endif
            </div>

            <div>
                <div class="flex items-center justify-between gap-2">
                    <label class="field-label" for="ai-model">Model</label>
                    @if ($this->selectedProvider->supportsModelListing())
                        <button
                            type="button"
                            wire:click="loadModels"
                            wire:loading.attr="disabled"
                            wire:target="loadModels"
                            class="mb-1.5 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50 data-loading:opacity-60 dark:text-indigo-400 dark:hover:bg-indigo-500/10"
                        >
                            <x-heroicon-o-list-bullet wire:loading.remove wire:target="loadModels" class="size-3.5" />
                            <x-heroicon-o-arrow-path wire:loading wire:target="loadModels" class="size-3.5 animate-spin" />
                            Load models
                        </button>
                    @endif
                </div>
                <input
                    id="ai-model"
                    type="text"
                    wire:model="model"
                    class="input-field"
                    autocomplete="off"
                    placeholder="{{ $this->selectedProvider->defaultModel() ?: 'Model name as shown by the server' }}"
                >
                @if ($availableModels !== [])
                    <select wire:model.live="model" aria-label="Available models" class="input-field mt-2">
                        @unless (in_array($model, $availableModels, true))
                            <option value="{{ $model }}">{{ $model !== '' ? $model.' (typed)' : 'Choose from '.count($availableModels).' available models…' }}</option>
                        @endunless
                        @foreach ($availableModels as $availableModel)
                            <option value="{{ $availableModel }}">{{ $availableModel }}</option>
                        @endforeach
                    </select>
                @endif
                @error('model') <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400"><x-heroicon-m-exclamation-circle class="size-3.5" />{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($testMessage)
            <p @class([
                'flex items-start gap-2 rounded-xl px-3.5 py-2.5 text-sm',
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $testPassed,
                'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' => ! $testPassed,
            ])>
                @if ($testPassed)
                    <x-heroicon-m-check-circle class="mt-0.5 size-4 shrink-0" />
                @else
                    <x-heroicon-m-exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                @endif
                <span>{{ $testMessage }}</span>
            </p>
        @endif

        @if ($savedMessage)
            <p class="flex items-center gap-2 rounded-xl bg-indigo-50 px-3.5 py-2.5 text-sm text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                <x-heroicon-m-check-circle class="size-4 shrink-0" />
                {{ $savedMessage }}
            </p>
        @endif

        <p class="text-muted text-xs">
            Keys are stored encrypted on this computer and only sent to the provider you choose. Project names and descriptions you generate with are sent to that provider.
        </p>

        <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-5 dark:border-white/5">
            <button type="button" wire:click="test" wire:loading.attr="disabled" wire:target="test" class="btn-secondary data-loading:opacity-70">
                <x-heroicon-o-signal wire:loading.remove wire:target="test" class="size-4" />
                <x-heroicon-o-arrow-path wire:loading wire:target="test" class="size-4 animate-spin" />
                Test connection
            </button>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary data-loading:opacity-70">
                <x-heroicon-o-check wire:loading.remove wire:target="save" class="size-4" />
                <x-heroicon-o-arrow-path wire:loading wire:target="save" class="size-4 animate-spin" />
                Save &amp; use
            </button>
        </div>
    </form>
</div>
