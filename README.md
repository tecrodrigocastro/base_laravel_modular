# base_laravel_modular

[Versão em português](README.pt-BR.md)

A **monorepo template** for a Laravel modular monolith: `apps/backend` (API), `apps/admin` (Filament, three panels), and one Composer package per business module under `packages/*`, shared by both apps. `apps/web` is reserved but not scaffolded here — a separate frontend template gets dropped in and referenced later; it only ever talks to `apps/backend`'s HTTP API, never `packages/*`.

This repo is meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc), its Flutter counterpart.

> **Status:** early stage, but runnable and enforced. `apps/backend` and `apps/admin` are both real Laravel apps, both consuming `packages/withdrawals` as a fully-implemented reference module; `apps/admin` has three working Filament panels (adapted from FilaKit). `make check` runs Pint, Rector, PHPStan and Pest cleanly across both apps. What's still missing: the `apps/web` frontend — see the Roadmap section.

```bash
make install     # composer/npm install for both apps
make check         # rector --dry-run + pint --test + phpstan + pest, across both apps
```

> **Working with AI assistants**: this project ships a `CLAUDE.md` and `.claude/rules/` so Claude Code (or any assistant that reads `CLAUDE.md`) already knows the architecture and naming conventions before generating anything.

## The idea

Every business module is its own Composer package under `packages/{module}/` — Models, Actions, DTOs, Enums, Events, its own migrations, its own `ServiceProvider`. That package shape never changes regardless of how many apps sit on top of it — here, `apps/backend` (the API, owning every write path) and `apps/admin` (an internal Filament panel) both require the same `packages/*` via Composer path repositories. `apps/web`, whenever it's dropped in, is a plain HTTP client of `apps/backend`'s API — it never touches `packages/*` or a database.

A package isn't only for business modules with extraction potential (`withdrawals`) — `identity` (`User`/`Admin`) is shared for a different reason: both apps must authenticate against and reference the literal same rows, so the Model/migration can only live in one place. See [`.claude/rules/architecture.md`](.claude/rules/architecture.md), "Two different reasons a Model must live in `packages/*`".

Why a monorepo, why apps are split this way, and what to do if you don't need the split: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

## Structure

```
apps/
  backend/                    # Laravel — the API, owns every write path
  admin/                      # Laravel + Filament — 3 panels (admin/app/guest), see filament-panels.md
    app/Providers/Filament/{Admin,App,Guest}PanelProvider.php
    app/Filament/Admin/Resources/WithdrawalResource.php   # imports Acme\Withdrawals\Models\Withdrawal
  web/                        # not scaffolded here yet — frontend template dropped in later, HTTP-only
packages/
  withdrawals/                 # reference module
    composer.json              # type: library, own vendor/name, version pinned ^1.0.0 by consuming apps
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
    phpstan.neon                # auto-included by apps/backend/phpstan.modules.php
    tests/
  identity/                     # second reference module — required by both apps, Filament-free
    src/Models/{User,Admin}.php
Makefile                        # orchestrates both apps
```

## Core rules

- Every module is a real Composer package, never a loose folder glued into an app.
- Any write to a Model with business rules behind it goes through that module's `Actions/` — never a raw `->save()`/`->update()` from outside the package. This is what keeps `apps/backend` and `apps/admin` (or, later, an extracted microservice) from drifting apart on the same rule.
- Modules likely to become a standalone service later (payments, a moderation-style pipeline) get a `Contract` + `Adapter` pair from day one, so swapping "local Eloquent" for "remote API client" doesn't touch any caller.
- No module reaches into another module's tables directly.
- Every intra-repo `acme/*` module dependency is pinned `^1.0.0` in each app's `composer.json` — never the loose `*` the generator leaves behind.

Full rationale: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Naming table for every element above: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Generator

`make new-module name={name}` wraps [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module`, configured in `apps/backend/config/app-modules.php` to use `../../packages` and an `Acme` placeholder namespace — swap both for your real vendor/namespace). Full step-by-step for what to build by hand afterward: [`.claude/skills/new-module/SKILL.md`](.claude/skills/new-module/SKILL.md), following `packages/withdrawals/` as the reference shape.

## Filament

`apps/admin` has three working panels — `admin`, `app`, `guest` — adapted from [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) (MIT), with login, profile, PWA and impersonation already wired. Details: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Tooling

`make check`/`make format` run Pint, Rector, PHPStan (Larastan) and Pest across both apps in one shot; PHPStan and Pest both auto-include every `packages/*`. [`laravel/boost`](https://laravel.com/docs/boost) is installed in both apps for AI-guideline generation. Full detail: [`.claude/rules/tooling.md`](.claude/rules/tooling.md).

## Roadmap

- [x] A fully-implemented reference module (`packages/withdrawals`, mirroring `auth` in `base_clean_arch_bloc`) that new modules imitate, consumed by both `apps/backend` and `apps/admin`.
- [x] `apps/admin` with three working Filament panels and a reference Resource over the shared module.
- [x] `.claude/skills/new-module/` wrapping `make:module` with this template's conventions (Actions/Contracts/Adapters, test stub, wiring) — mirrors `base_clean_arch_bloc`'s `new-feature` skill.
- [x] PHPStan (Larastan), Pint, Rector and a root `Makefile` orchestrating both apps; `laravel/boost` for AI guidelines.
- [ ] `apps/web`: [`base_nuxt_modular`](https://github.com/tecrodrigocastro/base_nuxt_modular) exists as its own repo (module-per-feature convention, `login` reference module, `HttpClient` already shaped to pair with this repo's `apps/backend`) — still needs to be dropped in here as a sibling of `apps/backend` and `apps/admin`.
