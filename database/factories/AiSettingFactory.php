<?php

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Models\AiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSetting>
 */
class AiSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AiSetting>
     */
    protected $model = AiSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => AiProvider::Groq,
            'model' => AiProvider::Groq->defaultModel(),
            'api_key' => 'test-'.$this->faker->sha1(),
            'base_url' => null,
            'is_active' => false,
        ];
    }

    /**
     * Mark the configuration as the active provider.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Use the given provider with its default model.
     */
    public function provider(AiProvider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
            'model' => $provider->defaultModel(),
        ]);
    }
}
