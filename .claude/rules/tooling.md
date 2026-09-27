# Tooling

Each app (`apps/backend`, `apps/admin`) owns its own `vendor/bin/{pint,phpstan,rector,pest}` — there is no shared toolchain package, each is a normal `composer require --dev` in that app. The root `Makefile` is the only thing that knows both apps exist and runs commands in each.

## Root Makefile

```bash
make help            # list everything below
make install          # composer/npm install for both apps
make check             # rector --dry-run + pint --test + phpstan (both apps) + pest (both apps) — CI-shaped, no fixing
make format             # rector + pint, applies fixes
make test                # pest in both apps (backend's run also covers packages/*/tests)
make phpstan               # phpstan in both apps (backend's run also covers packages/*)
make new-module name=X       # scaffold packages/X — see .claude/skills/new-module/
make modules-list              # list modules apps/backend has registered
```

Run `make check` before considering any change done — it is the one command that touches everything: both apps' style, both apps' static analysis (backend's PHPStan run auto-includes every `packages/*/phpstan.neon`), and both apps' test suites (backend's Pest run auto-includes every `packages/*/tests`).

## PHPStan (Larastan)

- `apps/backend/phpstan.neon` analyses `app/` at level 5, and includes `phpstan.modules.php` — a small script that globs `../../packages/*/phpstan.neon` so a new module is picked up automatically, no manual include to add. This mirrors `internachi/modular`'s own module-discovery pattern.
- `packages/{module}/phpstan.neon` is minimal by design — just `paths: [src/]`. It only makes sense included from an app (for the Laravel-aware Larastan extension and the app's autoloader), never run standalone.
- `apps/admin/phpstan.neon` analyses only its own `app/` — it does **not** re-include `packages/*`, since `apps/backend`'s run already covers module code and analysing it twice from two different apps buys nothing.

## Pint

Laravel's default `laravel` preset, no project-specific `pint.json` — don't add one unless a real style disagreement comes up. `make format` / `make check` cover both apps.

## Rector

`apps/backend/rector.php` and `apps/admin/rector.php` both target `LevelSetList::UP_TO_PHP_83` + `LaravelSetList::LARAVEL_CODE_QUALITY`, scoped to `app/`, `database/`, `routes/`, `tests/` (never `vendor/`, never `packages/*` — a module's own Rector config, if it ever needs one, lives inside that package). Bump the PHP level here, not per-module, when the project's minimum PHP version changes.

## Laravel Boost

[`laravel/boost`](https://laravel.com/docs/boost) is installed as a dev dependency in both apps and run with **all three flags**: `php artisan boost:install --guidelines --skills --mcp`. Dropping `--skills` is an easy mistake — plenty of installed packages ship their own skill under `resources/boost/skills/` (e.g. `filament/filament`'s `filament-development`, `internachi/modular`'s `modular`), and without the flag they're silently skipped even though the guidelines still install fine.

A package's bundled guideline/skill only gets picked up if it's listed in that app's `boost.json` `"packages"` array — Boost doesn't scan every installed dependency, only ones you tell it about. `apps/backend/boost.json` lists `internachi/modular`; `apps/admin/boost.json` lists `filament/filament` and `achyutn/filament-log-viewer`. Add a package there (and re-run `boost:install`) whenever a newly-required dependency ships boost resources worth pulling in — check with `find vendor -path "*resources/boost/skills*" -iname SKILL.md` after requiring it.

It writes an `AGENTS.md` per app with general Laravel/Pest/Pint guidelines (plus whatever `"packages"` adds) and syncs skills into `.claude/skills/` — regenerate both by re-running the command, never hand-edit either. Claude Code doesn't read `AGENTS.md` on its own, so each app has a one-line `CLAUDE.md` that does `@AGENTS.md` to import it, plus a pointer back to this repo's own `.claude/rules/` for anything project-specific that Boost has no way to know about (the monorepo layout, the Action rule, `packages/*` boundaries).
