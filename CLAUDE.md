# base_laravel_modular

Laravel modular-monolith **monorepo** template: `apps/backend` (API), `apps/admin` (Filament), and `packages/{module}` (one Composer package per business module, shared by both apps). `apps/web` is reserved but not scaffolded here — a separate frontend template gets dropped in and referenced later; it only ever talks to `apps/backend`'s HTTP API, never `packages/*`. Meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc) for Flutter.

Detailed conventions live in `.claude/rules/` and are loaded automatically. `packages/withdrawals/` is the reference module — a complete, working example of every convention below (Model, Action, DTO, Enum, Event, Contract, Adapter, migration, tests) — imitate its shape when creating a new module, the same way `auth` is the reference feature in `base_clean_arch_bloc`. `apps/admin`'s `WithdrawalResource` is the reference Filament Resource consuming it.

## Structure

```
apps/
  backend/                    # Laravel — the API, owns every write path
  admin/                      # Laravel + Filament — internal panel(s)
    app/Providers/Filament/AdminPanelProvider.php
    app/Filament/Admin/Resources/WithdrawalResource.php   # imports Acme\Withdrawals\Models\Withdrawal
  web/                        # not scaffolded here yet — frontend template dropped in later, HTTP-only
packages/
  withdrawals/                 # reference module
    composer.json              # type: library, own vendor/name
    src/
      Models/
      Actions/                 # only place allowed to mutate a Model that has business logic behind it
      DTOs/
      Enums/
      Events/
      Contracts/               # interface the Action talks to, never Eloquent directly for cross-boundary reads
      Adapters/                 # concrete implementation of a Contract (local today, remote later)
      Providers/{Module}ServiceProvider.php
    database/migrations/
    tests/
```

Why a monorepo, why split into three apps: `.claude/rules/project-shape.md`.

## Commands

```bash
cd apps/backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate
cd apps/admin   && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate

cd apps/backend && ./vendor/bin/pest              # full suite, including every packages/*/tests
cd apps/backend && ./vendor/bin/pest ../../packages/withdrawals/tests
cd apps/backend && php artisan make:module {name}  # scaffold a new module (internachi/modular)
cd apps/backend && php artisan modules:list
```

## Non-negotiables

- Every module is a real Composer package (`type: library`, PSR-4 autoload, own `ServiceProvider`, own migrations) — never a loose folder glued into an app.
- Any write to a Model with business rules behind it goes through that module's `Actions/`, never a raw `->save()`/`->update()` called from outside the package. This is what keeps `apps/backend` and `apps/admin` from drifting out of sync on the same rule.
- Modules likely to become a standalone service later (heavy queue/latency profile — payments, moderation-style workloads) get a `Contract` + `Adapter` pair from day one: the Action talks to the interface, the Adapter is swappable from "local Eloquent" to "remote API client" without touching callers.
- No module reaches into another module's tables directly — no cross-package Eloquent relationship, no raw join. Cross-module reads/writes go through that module's own Action/Contract.
- `apps/web`, whenever it's dropped in, never touches `packages/*` or a database directly — it only calls `apps/backend`'s HTTP API.

## Generator

Module scaffolding runs on top of [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module {name}` from `apps/backend`, configured in `apps/backend/config/app-modules.php` to use `../../packages` instead of its default `app-modules/`, and an `Acme` namespace as a placeholder — replace both with your real vendor/namespace). After scaffolding, fill in `src/Models`, `src/Actions`, etc. by hand following `packages/withdrawals/` as the reference shape; there is no further automation on top of `make:module` yet — see roadmap.

## Filament

`apps/admin` has a working `admin` panel (`AdminPanelProvider`: `id('admin')`, `path('admin')`, `authGuard('admin')`) discovering Resources from `app/Filament/Admin/Resources`. For a fuller starting point (login customization, profile, PWA, i18n) beyond this template's bare panel, [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) already solves those on the same multi-panel/multi-guard technique. Full detail: `.claude/rules/filament-panels.md`.

## Roadmap (not built yet)

- `.claude/skills/new-module/` wrapping `make:module` with this template's extra conventions (Actions/Contracts/Adapters, test stub, wiring) — codifying the steps taken by hand to build `packages/withdrawals`.
- Additional Filament panels beyond `admin` (e.g. a second audience), following the same `PanelProvider` shape.
- `apps/web`: a separate frontend template (its own repo, mirroring the spirit of this one and of `base_clean_arch_bloc`) gets built, then referenced/dropped in here as a sibling of `apps/backend` and `apps/admin`.

Done: this repo is a real, runnable monorepo — `apps/backend` and `apps/admin` both consume `packages/withdrawals` (proving the path-repository + `ServiceProvider` auto-discovery mechanism works across two independent Laravel apps, not just within one), and `apps/admin` has a working Filament panel over it. Not just documented — proven.

See `.claude/rules/naming-conventions.md` before creating new files, and `.claude/rules/architecture.md` for the full rationale behind the Action/Contract/Adapter rules.
