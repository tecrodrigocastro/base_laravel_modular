# base_laravel_modular

[Versão em português](README.pt-BR.md)

A **monorepo template** for a Laravel modular monolith: `apps/backend` (API), `apps/admin` (Filament), and one Composer package per business module under `packages/*`, shared by both apps. `apps/web` is reserved but not scaffolded here — a separate frontend template gets dropped in and referenced later; it only ever talks to `apps/backend`'s HTTP API, never `packages/*`.

This repo is meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc), its Flutter counterpart.

> **Status:** early stage, but runnable. `apps/backend` and `apps/admin` are both real Laravel apps, both consuming `packages/withdrawals` as a fully-implemented reference module; `apps/admin` has a working Filament panel over it. What's still missing: a `new-module` skill and the `apps/web` frontend — see the Roadmap section.

```bash
cd apps/backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate
cd apps/admin   && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate

cd apps/backend && ./vendor/bin/pest   # full suite, including every packages/*/tests
```

> **Working with AI assistants**: this project ships a `CLAUDE.md` and `.claude/rules/` so Claude Code (or any assistant that reads `CLAUDE.md`) already knows the architecture and naming conventions before generating anything.

## The idea

Every business module is its own Composer package under `packages/{module}/` — Models, Actions, DTOs, Enums, Events, its own migrations, its own `ServiceProvider`. That package shape never changes regardless of how many apps sit on top of it — here, `apps/backend` (the API, owning every write path) and `apps/admin` (an internal Filament panel) both require the same `packages/*` via Composer path repositories. `apps/web`, whenever it's dropped in, is a plain HTTP client of `apps/backend`'s API — it never touches `packages/*` or a database.

Why a monorepo, why apps are split this way, and what to do if you don't need the split: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

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
      Actions/                 # the only place allowed to mutate a Model with business logic behind it
      DTOs/
      Enums/
      Events/
      Contracts/               # interface an Action talks to, for modules likely to become a service later
      Adapters/                 # concrete implementation of a Contract (local today, remote later)
      Providers/{Module}ServiceProvider.php
    database/migrations/
    tests/
```

## Core rules

- Every module is a real Composer package, never a loose folder glued into an app.
- Any write to a Model with business rules behind it goes through that module's `Actions/` — never a raw `->save()`/`->update()` from outside the package. This is what keeps `apps/backend` and `apps/admin` (or, later, an extracted microservice) from drifting apart on the same rule.
- Modules likely to become a standalone service later (payments, a moderation-style pipeline) get a `Contract` + `Adapter` pair from day one, so swapping "local Eloquent" for "remote API client" doesn't touch any caller.
- No module reaches into another module's tables directly.

Full rationale: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Naming table for every element above: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Generator

Module scaffolding runs on top of [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module {name}` from `apps/backend`) instead of hand-rolled boilerplate, configured in `apps/backend/config/app-modules.php` to use `../../packages` and an `Acme` placeholder namespace — swap both for your real vendor/namespace. Fill in the generated skeleton by hand, following `packages/withdrawals/` as the reference shape.

## Filament

`apps/admin` has a working `admin` panel — details and the multi-panel/multi-guard technique: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Roadmap

- [x] A fully-implemented reference module (`packages/withdrawals`, mirroring `auth` in `base_clean_arch_bloc`) that new modules imitate, consumed by both `apps/backend` and `apps/admin`.
- [x] `apps/admin` with a working Filament panel and a reference Resource over the shared module.
- [ ] `.claude/skills/new-module/` wrapping `make:module` with this template's conventions (Actions/Contracts/Adapters, test stub, wiring) — mirrors `base_clean_arch_bloc`'s `new-feature` skill.
- [ ] `apps/web`: a separate frontend template, built on its own, then referenced/dropped in here as a sibling of `apps/backend` and `apps/admin`.
