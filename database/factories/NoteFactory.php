<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * A fixed palette of sticky-note colors so generated notes look intentional.
     *
     * @var list<string>
     */
    protected array $colors = [
        '#fde68a', '#fca5a5', '#bbf7d0', '#bfdbfe', '#e9d5ff', '#fed7aa',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'body' => $this->faker->sentence(),
            'color' => $this->faker->randomElement($this->colors),
            'position_x' => $this->faker->numberBetween(0, 400),
            'position_y' => $this->faker->numberBetween(0, 300),
            'is_pinned' => false,
        ];
    }

    /**
     * A general note, not scoped to any project.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes): array => [
            'project_id' => null,
        ]);
    }

    /**
     * A pinned note.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_pinned' => true,
        ]);
    }
}
