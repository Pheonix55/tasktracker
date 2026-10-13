<?php

namespace App\Enums;

enum AiProvider: string
{
    case Gemini = 'gemini';
    case Groq = 'groq';
    case OpenRouter = 'openrouter';
    case Mistral = 'mistral';
    case Cerebras = 'cerebras';
    case GitHubModels = 'github';
    case CloudflareWorkersAi = 'cloudflare';
    case Anthropic = 'anthropic';
    case OpenAi = 'openai';
    case DeepSeek = 'deepseek';
    case Ollama = 'ollama';
    case LmStudio = 'lmstudio';
    case Custom = 'custom';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Gemini => 'Google Gemini',
            self::Groq => 'Groq',
            self::OpenRouter => 'OpenRouter',
            self::Mistral => 'Mistral',
            self::Cerebras => 'Cerebras',
            self::GitHubModels => 'GitHub Models',
            self::CloudflareWorkersAi => 'Cloudflare Workers AI',
            self::Anthropic => 'Anthropic Claude',
            self::OpenAi => 'OpenAI',
            self::DeepSeek => 'DeepSeek',
            self::Ollama => 'Ollama (local)',
            self::LmStudio => 'LM Studio (local)',
            self::Custom => 'Custom (OpenAI-compatible)',
        };
    }

    /**
     * Short pricing hint shown next to the provider.
     */
    public function pricing(): string
    {
        return match ($this) {
            self::Gemini, self::Groq, self::Mistral, self::Cerebras => 'Free tier',
            self::OpenRouter => 'Free models',
            self::GitHubModels => 'Free (rate-limited)',
            self::CloudflareWorkersAi => 'Free daily allowance',
            self::Ollama, self::LmStudio => 'Free · offline',
            self::Anthropic, self::OpenAi, self::DeepSeek => 'Paid',
            self::Custom => 'Depends on server',
        };
    }

    /**
     * Whether this provider speaks Anthropic's Messages API rather than the OpenAI chat completions API.
     */
    public function usesAnthropicApi(): bool
    {
        return $this === self::Anthropic;
    }

    /**
     * The API base URL used when the user hasn't set their own.
     */
    public function defaultBaseUrl(): string
    {
        return match ($this) {
            self::Gemini => 'https://generativelanguage.googleapis.com/v1beta/openai',
            self::Groq => 'https://api.groq.com/openai/v1',
            self::OpenRouter => 'https://openrouter.ai/api/v1',
            self::Mistral => 'https://api.mistral.ai/v1',
            self::Cerebras => 'https://api.cerebras.ai/v1',
            self::GitHubModels => 'https://models.github.ai/inference',
            self::Anthropic => 'https://api.anthropic.com/v1',
            self::OpenAi => 'https://api.openai.com/v1',
            self::DeepSeek => 'https://api.deepseek.com/v1',
            self::Ollama => 'http://localhost:11434/v1',
            self::LmStudio => 'http://localhost:1234/v1',
            self::CloudflareWorkersAi, self::Custom => '',
        };
    }

    /**
     * Placeholder shown in the base URL field for providers whose URL is account-specific.
     */
    public function baseUrlPlaceholder(): string
    {
        return match ($this) {
            self::CloudflareWorkersAi => 'https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/v1',
            self::Custom => 'https://your-server.example.com/v1',
            default => $this->defaultBaseUrl(),
        };
    }

    /**
     * Whether the user may (or must) change the base URL for this provider.
     */
    public function hasEditableBaseUrl(): bool
    {
        return in_array($this, [self::CloudflareWorkersAi, self::Ollama, self::LmStudio, self::Custom], true);
    }

    /**
     * A sensible starting model; the user can type any model the provider offers.
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::Gemini => 'gemini-2.5-flash',
            self::Groq => 'llama-3.3-70b-versatile',
            self::OpenRouter => 'meta-llama/llama-3.3-70b-instruct:free',
            self::Mistral => 'mistral-small-latest',
            self::Cerebras => 'llama-3.3-70b',
            self::GitHubModels => 'openai/gpt-4.1-mini',
            self::CloudflareWorkersAi => '@cf/meta/llama-3.1-8b-instruct',
            self::Anthropic => 'claude-haiku-5-5',
            self::OpenAi => 'gpt-4.1-mini',
            self::DeepSeek => 'deepseek-chat',
            self::Ollama => 'llama3.2',
            self::LmStudio, self::Custom => '',
        };
    }

    /**
     * Whether the provider exposes a model list the app can fetch.
     */
    public function supportsModelListing(): bool
    {
        return $this !== self::CloudflareWorkersAi;
    }

    /**
     * The endpoint that lists available models, given the resolved base URL.
     */
    public function modelsUrl(string $baseUrl): string
    {
        return match ($this) {
            self::GitHubModels => 'https://models.github.ai/catalog/models',
            self::Anthropic => $baseUrl.'/models?limit=1000',
            default => $baseUrl.'/models',
        };
    }

    /**
     * Whether requests to this provider need an API key.
     */
    public function requiresApiKey(): bool
    {
        return ! in_array($this, [self::Ollama, self::LmStudio, self::Custom], true);
    }

    /**
     * Where the user can get an API key or install the runtime.
     */
    public function setupHint(): string
    {
        return match ($this) {
            self::Gemini => 'Get a key at aistudio.google.com/apikey',
            self::Groq => 'Get a key at console.groq.com/keys',
            self::OpenRouter => 'Get a key at openrouter.ai/keys — models ending in ":free" cost nothing',
            self::Mistral => 'Get a key at console.mistral.ai (choose the free Experiment plan)',
            self::Cerebras => 'Get a key at cloud.cerebras.ai',
            self::GitHubModels => 'Use a GitHub personal access token with the "models:read" permission',
            self::CloudflareWorkersAi => 'Create an API token at dash.cloudflare.com and put your account ID in the base URL',
            self::Anthropic => 'Get a key at console.anthropic.com',
            self::OpenAi => 'Get a key at platform.openai.com/api-keys',
            self::DeepSeek => 'Get a key at platform.deepseek.com',
            self::Ollama => 'Install Ollama from ollama.com, then run "ollama pull llama3.2"',
            self::LmStudio => 'Install LM Studio, load a model, and start its local server',
            self::Custom => 'Any server exposing an OpenAI-compatible /chat/completions endpoint',
        };
    }
}
