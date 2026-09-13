# Task Tracker

A comprehensive, offline-first task management app built with Laravel and Livewire. Organize work into **Projects**, track **Tasks** on a drag-and-drop **Kanban board**, and break each task down into a **Checklist**.

There is no login/signup — this is a single-user, local application, designed to eventually ship as a desktop app via [NativePHP](https://nativephp.com/).

## Features

- **Projects** — color/icon customization, favorites, archiving, and per-project completion progress.
- **Kanban board** — four-column workflow (To Do → In Progress → In Review → Done) with drag-and-drop reordering and status changes (`wire:sort`).
- **Tasks** — priority levels (Low/Medium/High/Urgent), due dates with overdue highlighting, and a detail slide-over for full editing.
- **Checklists** — add, toggle, reorder, and remove sub-items on any task, with a live completion count.
- **Tags** — reusable, color-coded labels shared across projects; filter a board by tag.
- **Trash** — projects and tasks are soft-deleted, with a dedicated screen to restore or permanently delete them.
- **Dashboard** — at-a-glance stats (open/overdue/completed tasks), a "Due Soon" list, and favorite-project shortcuts.
- **Dark mode** — toggleable, persisted per browser.
- **Loading states** — skeleton placeholders and inline spinners on every action that hits the server, for responsive-feeling UX.

## Tech Stack

- [Laravel 13](https://laravel.com/docs) (PHP 8.3+)
- [Livewire 4](https://livewire.laravel.com/docs/4.x) — full-page and nested components (SFC/MFC), no separate JS framework
- [Alpine.js](https://alpinejs.dev/) — bundled with Livewire, used for the dark-mode toggle and small UI interactions
- [Tailwind CSS v4](https://tailwindcss.com/) — CSS-first configuration (`resources/css/app.css`)
- [Blade Heroicons](https://github.com/blade-ui-kit/blade-heroicons) — icon set
- SQLite — zero-config, file-based database, well suited to an offline desktop app
- [Pest](https://pestphp.com/) — test suite

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm

## Getting Started

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite file already exists at database/database.sqlite)
php artisan migrate --seed

# Build frontend assets
npm run build
```

Then visit the app at whatever URL your local environment serves it from (e.g. via Laragon, Valet, or `php artisan serve`).

## Development

Run the server, queue listener, and Vite dev server together:

```bash
composer run dev
```

Or individually:

```bash
php artisan serve
npm run dev
```

Re-seed the database with fresh demo data (a handful of projects, tasks, checklists, and tags, including some pre-trashed records) at any time:

```bash
php artisan migrate:fresh --seed
```

## Testing & Code Quality

```bash
php artisan test --compact     # Pest test suite
vendor/bin/pint                # Code style (Laravel Pint)
composer run types:check       # Static analysis (Larastan)
```

## Project Structure

Livewire 4 components follow the single-file/multi-file component (SFC/MFC) convention — PHP logic and Blade markup live together, prefixed with `⚡`:

```
app/
  Enums/            TaskStatus, TaskPriority
  Models/           Project, Task, ChecklistItem, Tag

resources/views/
  layouts/app.blade.php          Sidebar layout, dark mode toggle
  pages/                         Full-page components (routed via Route::livewire())
    ⚡dashboard.blade.php
    ⚡trash.blade.php
    projects/⚡index.blade.php
    projects/⚡show/               Kanban board (multi-file component)
    tags/⚡index.blade.php
  components/                   Nested/reusable components
    projects/⚡form-modal.blade.php
    tasks/⚡detail-modal.blade.php
    checklist/⚡manager.blade.php
    tags/⚡picker.blade.php
    kanban/task-card.blade.php   Presentational (plain Blade)
    ui/                          Badges, chips, skeletons
```

## Roadmap

- Package as a desktop app with [NativePHP](https://nativephp.com/) for offline use.
