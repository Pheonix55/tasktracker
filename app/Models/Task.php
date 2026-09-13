<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property Carbon|null $due_date
 * @property int $position
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['project_id', 'title', 'description', 'status', 'priority', 'due_date', 'position', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The project this task belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The checklist items belonging to this task, ordered by position.
     *
     * @return HasMany<ChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    /**
     * The tags attached to this task.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Whether the task is overdue: has a due date in the past and isn't done.
     *
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== TaskStatus::Done
        );
    }

    /**
     * The task's checklist completion counts.
     *
     * @return Attribute<array{completed: int, total: int}, never>
     */
    protected function checklistProgress(): Attribute
    {
        return Attribute::get(function (): array {
            $items = $this->checklistItems;

            return [
                'completed' => $items->where('is_completed', true)->count(),
                'total' => $items->count(),
            ];
        });
    }

    /**
     * Scope a query to only include overdue tasks.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->where('status', '!=', TaskStatus::Done->value);
    }

    /**
     * Scope a query to only include tasks due within the given number of days (inclusive of today).
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays($days)->endOfDay()])
            ->where('status', '!=', TaskStatus::Done->value);
    }
}
