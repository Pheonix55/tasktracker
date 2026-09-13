<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A sticky note on the kanban board. A note with a null `project_id` is
 * "general" and shows on every project's board; otherwise it's scoped to
 * that one project.
 *
 * @property int $id
 * @property int|null $project_id
 * @property string $body
 * @property string $color
 * @property int $position_x
 * @property int $position_y
 * @property bool $is_pinned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'body', 'color', 'position_x', 'position_y', 'is_pinned'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * The project this note is scoped to. Null for a general note.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to notes visible on the given project's board: the
     * project's own notes plus every general (project-less) note.
     *
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function scopeVisibleOnProject(Builder $query, int $projectId): Builder
    {
        return $query->where(function (Builder $query) use ($projectId): void {
            $query->where('project_id', $projectId)->orWhereNull('project_id');
        });
    }
}
