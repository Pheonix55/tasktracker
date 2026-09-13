# AGENTS.md

## Project context

This repository is a Laravel 13 starter application with Livewire and Vite. The active app conventions are defined in [CLAUDE.md](CLAUDE.md), while the runtime and tooling defaults live in [composer.json](composer.json) and [package.json](package.json).

## Working conventions

- Keep changes aligned with Laravel's standard structure in `app/`, `routes/`, `database/`, `resources/`, and `tests/`.
- Prefer existing patterns over introducing new abstraction layers or new folders.
- Preserve the project's PHP 8.3 + Laravel conventions: typed method signatures, constructor property promotion, and explicit return types.
- For new models, follow Laravel conventions and add useful factories/seeders when relevant.

## Validation and quality checks

- For PHP changes, run the narrowest relevant test first and then rerun after edits.
- Use Pest for tests and prefer feature tests under `tests/Feature` unless the change is purely unit-level.
- Run formatting with `vendor/bin/pint --dirty --format agent` after editing PHP files.
- If a UI or asset change does not appear, run `npm run build` or `composer run dev` as needed.
- If you need to check the app behavior, prefer `php artisan test --compact --filter=...` or `vendor/bin/pest ... --filter=...` over broad suite runs.

## Common pitfalls

- Do not add unnecessary dependencies or rewrite framework conventions just to make a small change.
- Do not create custom verification scripts when the existing test suite is enough.
- Keep instructions and edits concise; the project already contains strong repository-level guidance in [CLAUDE.md](CLAUDE.md).

## When in doubt

Check the existing code and nearby files first, then mirror the local Laravel pattern before introducing new implementation styles.
