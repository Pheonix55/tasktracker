<?php

namespace App\Services;

use App\Exceptions\AiRequestException;
use App\Models\AiSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Generates short text (and lists available models) with whichever AI provider the user configured.
 *
 * Most providers expose an OpenAI-compatible chat completions endpoint;
 * Anthropic is called through its own Messages API.
 */
class AiTextGenerator
{
    /**
     * Seconds to wait for a provider before giving up (local models can be slow).
     */
    protected const TIMEOUT_SECONDS = 90;

    /**
     * Generate text from the given instructions and prompt.
     *
     * Uses the active provider unless a specific (possibly unsaved) setting is passed.
     *
     * @throws AiRequestException
     */
    public function generate(string $instructions, string $prompt, ?AiSetting $setting = null): string
    {
        $setting ??= AiSetting::active();

        if ($setting === null) {
            throw new AiRequestException('No AI provider is set up yet. Choose one in Settings.');
        }

        $this->ensureConnectable($setting);

        if ($setting->resolvedModel() === '') {
            throw new AiRequestException("Choose a model for {$setting->provider->label()} in Settings.");
        }

        $text = $setting->provider->usesAnthropicApi()
            ? $this->viaAnthropic($setting, $instructions, $prompt)
            : $this->viaOpenAiCompatible($setting, $instructions, $prompt);

        $text = trim($text);

        if ($text === '') {
            throw new AiRequestException('The AI returned an empty response. Try again or pick another model.');
        }

        return $text;
    }

    /**
     * Fetch the model IDs the provider offers for this key, sorted alphabetically.
     *
     * @return list<string>
     *
     * @throws AiRequestException
     */
    public function listModels(AiSetting $setting): array
    {
        if (! $setting->provider->supportsModelListing()) {
            throw new AiRequestException("{$setting->provider->label()} doesn't offer a model list here. Type the model name instead.");
        }

        $this->ensureConnectable($setting);

        $request = $setting->provider->usesAnthropicApi()
            ? $this->anthropicRequest($setting)
            : $this->openAiCompatibleRequest($setting);

        $response = $this->send($setting, fn (): Response => $request->get(
            $setting->provider->modelsUrl($setting->resolvedBaseUrl()),
        ));

        $entries = $response->json('data') ?? $response->json();

        $models = collect(is_array($entries) ? $entries : [])
            ->map(fn (mixed $entry): mixed => is_array($entry) ? ($entry['id'] ?? null) : null)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->map(fn (string $id): string => str_starts_with($id, 'models/') ? substr($id, 7) : $id)
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->all();

        if ($models === []) {
            throw new AiRequestException("{$setting->provider->label()} didn't return any models.");
        }

        return array_values($models);
    }

    /**
     * @throws AiRequestException
     */
    protected function ensureConnectable(AiSetting $setting): void
    {
        if ($setting->provider->requiresApiKey() && blank($setting->api_key)) {
            throw new AiRequestException("Add your {$setting->provider->label()} API key in Settings.");
        }

        if ($setting->resolvedBaseUrl() === '') {
            throw new AiRequestException("Set the base URL for {$setting->provider->label()} in Settings.");
        }
    }

    protected function openAiCompatibleRequest(AiSetting $setting): PendingRequest
    {
        $request = $this->http();

        if (filled($setting->api_key)) {
            $request->withToken($setting->api_key);
        }

        return $request;
    }

    protected function anthropicRequest(AiSetting $setting): PendingRequest
    {
        return $this->http()->withHeaders([
            'x-api-key' => $setting->api_key,
            'anthropic-version' => '2023-06-01',
        ]);
    }

    /**
     * @throws AiRequestException
     */
    protected function viaOpenAiCompatible(AiSetting $setting, string $instructions, string $prompt): string
    {
        $request = $this->openAiCompatibleRequest($setting);

        $response = $this->send($setting, fn (): Response => $request->post($setting->resolvedBaseUrl().'/chat/completions', [
            'model' => $setting->resolvedModel(),
            'messages' => [
                ['role' => 'system', 'content' => $instructions],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]));

        return (string) $response->json('choices.0.message.content', '');
    }

    /**
     * @throws AiRequestException
     */
    protected function viaAnthropic(AiSetting $setting, string $instructions, string $prompt): string
    {
        $request = $this->anthropicRequest($setting);

        $response = $this->send($setting, fn (): Response => $request->post($setting->resolvedBaseUrl().'/messages', [
            'model' => $setting->resolvedModel(),
            'max_tokens' => 4096,
            'system' => $instructions,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]));

        if ($response->json('stop_reason') === 'refusal') {
            throw new AiRequestException('Claude declined this request. Try rewording it.');
        }

        return $response->collect('content')
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(self::TIMEOUT_SECONDS);
    }

    /**
     * Run the request and translate transport and API failures into user-facing errors.
     *
     * @param  callable(): Response  $send
     *
     * @throws AiRequestException
     */
    protected function send(AiSetting $setting, callable $send): Response
    {
        $label = $setting->provider->label();

        try {
            $response = $send();
        } catch (ConnectionException) {
            throw new AiRequestException($setting->provider->requiresApiKey()
                ? "Couldn't reach {$label}. Check your internet connection."
                : "Couldn't reach {$label} at {$setting->resolvedBaseUrl()}. Is it running?");
        }

        if ($response->failed()) {
            $detail = $response->json('error.message')
                ?? $response->json('0.error.message')
                ?? $response->json('message')
                ?? $response->json('errors.0.message');

            $message = match ($response->status()) {
                401, 403 => "{$label} rejected the API key.",
                429 => "{$label} rate limit reached. Wait a moment and try again.",
                default => "{$label} returned an error ({$response->status()}).",
            };

            if (is_string($detail) && $detail !== '') {
                $message .= " {$detail}";
            }

            if ($response->notFound() && $setting->provider->supportsModelListing()) {
                $message .= ' Use "Load models" in Settings to pick a model your key can use.';
            }

            throw new AiRequestException($message);
        }

        return $response;
    }
}
