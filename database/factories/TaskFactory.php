<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Task>
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => ucfirst($this->faker->sentence(random_int(3, 6))),
            'description' => $this->faker->optional(0.6)->paragraphs(random_int(1, 2), true),
            'status' => $this->faker->randomElement(TaskStatus::cases()),
            'priority' => $this->faker->randomElement(TaskPriority::cases()),
            'due_date' => $this->faker->optional(0.6)->dateTimeBetween('-2 weeks', '+3 weeks'),
            'position' => 0,
            'completed_at' => null,
        ];
    }

    /**
     * Give the task a due date in the past and a non-done status.
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_date' => now()->subDays(random_int(1, 10)),
            'status' => $this->faker->randomElement([TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::InReview]),
            'completed_at' => null,
        ]);
    }

    /**
     * Give the task a due date within the next few days.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (): array => [
            'due_date' => now()->addDays(random_int(0, 5)),
            'status' => $this->faker->randomElement([TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::InReview]),
            'completed_at' => null,
        ]);
    }

    /**
     * Mark the task as done and completed just now.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
