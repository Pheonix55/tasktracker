<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Project>
     */
    protected $model = Project::class;

    /**
     * A fixed palette so generated projects/tags look intentional rather than random hex noise.
     *
     * @var list<string>
     */
    protected array $colors = [
        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316',
        '#eab308', '#22c55e', '#10b981', '#06b6d4', '#3b82f6',
    ];

    /**
     * A curated list of heroicon names (bare, no style prefix) that suit a project.
     *
     * @var list<string>
     */
    protected array $icons = [
        'rectangle-stack', 'folder', 'briefcase', 'rocket-launch', 'light-bulb',
        'code-bracket', 'paint-brush', 'book-open', 'chart-bar', 'globe-alt',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => rtrim(ucfirst($this->faker->sentence(random_int(2, 4))), '.'),
            'description' => $this->faker->optional(0.8)->sentences(random_int(1, 3), true),
            'color' => $this->faker->randomElement($this->colors),
            'icon' => $this->faker->randomElement($this->icons),
            'is_favorite' => false,
            'is_archived' => false,
        ];
    }

    /**
     * Mark the project as favorited.
     */
    public function favorite(): static
    {
        return $this->state(fn (): array => ['is_favorite' => true]);
    }

    /**
     * Mark the project as archived.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => ['is_archived' => true]);
    }
}
