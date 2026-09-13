<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To Do',
            self::InProgress => 'In Progress',
            self::InReview => 'In Review',
            self::Done => 'Done',
        };
    }

    /**
     * Tailwind color stem used for badges, column headers, and status dots.
     */
    public function color(): string
    {
        return match ($this) {
            self::Todo => 'slate',
            self::InProgress => 'blue',
            self::InReview => 'amber',
            self::Done => 'emerald',
        };
    }
}
