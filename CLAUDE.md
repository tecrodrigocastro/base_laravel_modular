# base_laravel_modular

Laravel modular-monolith template: one Composer package per business module (`packages/{module}`), shareable across one or many Laravel apps depending on the chosen project shape. Meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc) for Flutter.

Detailed conventions live in `.claude/rules/` and are loaded automatically. `packages/withdrawals/` is the reference module — a complete, working example of every convention below (Model, Action, DTO, Enum, Event, Contract, Adapter, migration, tests) — imitate its shape when creating a new module, the same way `auth` is the reference feature in `base_clean_arch_bloc`.

## Commands

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
./vendor/bin/pest              # full suite, including every packages/*/tests
./vendor/bin/pest packages/withdrawals/tests
php artisan make:module {name}  # scaffold a new module (internachi/modular)
php artisan modules:list
```

## Two project shapes

Decided per-project, at creation time. Today that choice is manual (copy/remove the pieces you don't need); an automated wizard is on the roadmap, not built yet.

- **single-project** — one Laravel app. API and admin panel (e.g. Filament) live in the same process; each panel/surface is just another package under `packages/*`. Closest to a classic modular monolith.
- **monorepo-split** — one Git repository, multiple Laravel apps under `apps/*` (e.g. `apps/backend` for the API, `apps/admin` for a separate Filament project), all requiring the same `packages/*` via Composer path repositories. Use when a panel/surface genuinely needs its own deploy lifecycle from day one.

Full comparison and decision guidance: `.claude/rules/project-shape.md`.

## Structure

```
packages/
  {module}/
    composer.json           # type: library, own vendor/name
    src/
      Models/
      Actions/               # only place allowed to mutate a Model that has business logic behind it
      DTOs/
      Enums/
      Events/
      Contracts/             # interface the Action talks to, never Eloquent directly for cross-boundary reads
      Adapters/               # concrete implementation of a Contract (local today, remote later)
      {Module}ServiceProvider.php
    database/
      migrations/
      factories/
    tests/
apps/                         # only in monorepo-split shape
  backend/
  admin/
```

## Non-negotiables

- Every module is a real Composer package (`type: library`, PSR-4 autoload, own `ServiceProvider`, own migrations) — never a loose folder glued into the main app.
- Any write to a Model with business rules behind it goes through that module's `Actions/`, never a raw `->save()`/`->update()` called from outside the package. This is what keeps two apps (or, later, two microservices) from drifting out of sync on the same rule.
- Modules likely to become a standalone service later (heavy queue/latency profile — payments, moderation-style workloads) get a `Contract` + `Adapter` pair from day one: the Action talks to the interface, the Adapter is swappable from "local Eloquent" to "remote API client" without touching callers.
- No module reaches into another module's tables directly — no cross-package Eloquent relationship, no raw join. Cross-module reads/writes go through that module's own Action/Contract.

## Generator

Module scaffolding runs on top of [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module {name}`, configured in `config/app-modules.php` to use `packages/` instead of its default `app-modules/`, and an `Acme` namespace as a placeholder — replace both with your real vendor/namespace). After scaffolding, fill in `src/Models`, `src/Actions`, etc. by hand following `packages/withdrawals/` as the reference shape; there is no further automation on top of `make:module` yet — see roadmap.

## Filament

Whichever app hosts Filament (the single app in single-project shape, or `apps/admin` in monorepo-split) uses [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) as the base — a Laravel 13 + Filament 5 starter kit with multi-panel + multi-guard already wired (one `PanelProvider` per audience, each with its own `id()`/`path()`/`authGuard()` and its own discovered `Resources/` namespace). Panels group Resources by *who sees them*; `packages/*` group Models/Actions by *what domain they belong to* — a single panel's Resources routinely span several packages. Full detail: `.claude/rules/filament-panels.md`.

## Roadmap (not built yet)

- `.claude/skills/new-module/` wrapping `make:module` with this template's extra conventions (Actions/Contracts/Adapters, test stub, wiring) — codifying the steps taken by hand to build `packages/withdrawals`.
- `composer create-project` wizard (`post-create-project-cmd`, via `laravel/prompts`) asking single-project vs monorepo-split at creation time and rewiring the tree accordingly.
- Filament wired in, following `.claude/rules/filament-panels.md`.

Done: this repo is a real, runnable Laravel app (single-project shape) with `packages/withdrawals` as the fully-implemented reference module — the mechanism (path repositories, `ServiceProvider` auto-discovery, migration auto-loading, Pest discovering `packages/*/tests`) is proven, not just documented.

See `.claude/rules/naming-conventions.md` before creating new files, and `.claude/rules/architecture.md` for the full rationale behind the Action/Contract/Adapter rules.
