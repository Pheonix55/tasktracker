<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ChecklistItem>
     */
    protected $model = ChecklistItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'label' => ucfirst($this->faker->sentence(random_int(2, 5))),
            'is_completed' => $this->faker->boolean(35),
            'position' => 0,
        ];
    }
}
