<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tags = Tag::factory()
            ->count(6)
            ->sequence(
                ['name' => 'Bug', 'color' => '#f43f5e'],
                ['name' => 'Feature', 'color' => '#22c55e'],
                ['name' => 'Design', 'color' => '#8b5cf6'],
                ['name' => 'Research', 'color' => '#06b6d4'],
                ['name' => 'Urgent', 'color' => '#f97316'],
                ['name' => 'Backend', 'color' => '#3b82f6'],
            )
            ->create();

        $projects = collect([
            Project::factory()->favorite()->create(['name' => 'Website Redesign']),
            Project::factory()->favorite()->create(['name' => 'Mobile App Launch']),
            Project::factory()->create(['name' => 'Internal Tooling']),
            Project::factory()->create(['name' => 'Marketing Campaign']),
            Project::factory()->create(['name' => 'Personal Errands']),
            Project::factory()->archived()->create(['name' => 'Q1 Retrospective']),
        ]);

        $projects->each(function (Project $project) use ($tags): void {
            $taskCount = random_int(8, 15);
            $statuses = TaskStatus::cases();

            for ($i = 0; $i < $taskCount; $i++) {
                $status = $statuses[$i % count($statuses)];

                $task = match (true) {
                    $i % 7 === 0 => Task::factory()->overdue(),
                    $i % 5 === 0 => Task::factory()->dueSoon(),
                    $status === TaskStatus::Done => Task::factory()->completed(),
                    default => Task::factory(),
                };

                $task = $task->create([
                    'project_id' => $project->id,
                    'status' => $status,
                    'position' => $i,
                ]);

                ChecklistItem::factory()
                    ->count(random_int(0, 5))
                    ->sequence(fn ($sequence) => ['position' => $sequence->index])
                    ->create(['task_id' => $task->id]);

                $task->tags()->attach(
                    $tags->random(random_int(0, 3))->pluck('id')
                );
            }
        });

        // Soft-delete a handful of records so the Trash screen isn't empty on first run.
        $projects->get(4)->delete();
        $projects->last()->tasks()->take(2)->get()->each->delete();
        Task::query()->inRandomOrder()->take(3)->get()->each->delete();
    }
}
