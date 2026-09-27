# base_laravel_modular

Laravel modular-monolith **monorepo** template: `apps/backend` (API), `apps/admin` (Filament, three panels), and `packages/{module}` (one Composer package per business module, shared by both apps). `apps/web` is reserved but not scaffolded here — a separate frontend template gets dropped in and referenced later; it only ever talks to `apps/backend`'s HTTP API, never `packages/*`. Meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc) for Flutter.

Detailed conventions live in `.claude/rules/` and are loaded automatically. `packages/withdrawals/` is the reference module — a complete, working example of every convention below (Model, Action, DTO, Enum, Event, Contract, Adapter, migration, tests) — imitate its shape when creating a new module, the same way `auth` is the reference feature in `base_clean_arch_bloc`. Use `.claude/skills/new-module/` to scaffold a new one instead of copying `withdrawals` by hand. `apps/admin`'s `WithdrawalResource` is the reference Filament Resource consuming it.

`packages/identity/` is a second reference module, of a different kind: it holds `User`/`Admin`, required by **both** apps because they must authenticate against and reference the literal same rows, not because it's a service-extraction candidate. See `.claude/rules/architecture.md`, "Two different reasons a Model must live in `packages/*`", before assuming every module needs a Contract/Adapter pair the way `withdrawals` does.

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
      Actions/                 # only place allowed to mutate a Model that has business logic behind it
      DTOs/
      Enums/
      Events/
      Contracts/               # interface the Action talks to, never Eloquent directly for cross-boundary reads
      Adapters/                 # concrete implementation of a Contract (local today, remote later)
      Providers/{Module}ServiceProvider.php
    database/migrations/
    phpstan.neon                # auto-included by apps/backend/phpstan.modules.php
    tests/
  identity/                     # second reference module — see the note above
    src/Models/{User,Admin}.php # required by apps/backend AND apps/admin, Filament-free
Makefile                        # orchestrates both apps — make check/format/test/new-module
```

Why a monorepo, why split into three apps: `.claude/rules/project-shape.md`. Tooling (Makefile targets, PHPStan/Pint/Rector, Laravel Boost) in detail: `.claude/rules/tooling.md`.

## Commands

```bash
make install          # composer/npm install for both apps
make check              # rector --dry-run + pint --test + phpstan (both apps) + pest (both apps)
make format               # rector + pint, applies fixes
make new-module name=X      # scaffold packages/X — see .claude/skills/new-module/

cd apps/backend && php artisan modules:list
cd apps/admin   && npm run build    # required before booting — Filament panels need the Vite manifest
```

## Non-negotiables

- Every module is a real Composer package (`type: library`, PSR-4 autoload, own `ServiceProvider`, own migrations, own `phpstan.neon`) — never a loose folder glued into an app.
- Any write to a Model with business rules behind it goes through that module's `Actions/`, never a raw `->save()`/`->update()` called from outside the package. This is what keeps `apps/backend` and `apps/admin` from drifting out of sync on the same rule.
- Modules likely to become a standalone service later (heavy queue/latency profile — payments, moderation-style workloads) get a `Contract` + `Adapter` pair from day one: the Action talks to the interface, the Adapter is swappable from "local Eloquent" to "remote API client" without touching callers.
- No module reaches into another module's tables directly — no cross-package Eloquent relationship, no raw join. Cross-module reads/writes go through that module's own Action/Contract.
- Every intra-repo `acme/*` module dependency is pinned `^1.0.0` in each app's `composer.json` — never the `*` the generator leaves behind. See `.claude/rules/naming-conventions.md`.
- `apps/web`, whenever it's dropped in, never touches `packages/*` or a database directly — it only calls `apps/backend`'s HTTP API.

## Generator

`make new-module name={name}` wraps [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module`, configured in `apps/backend/config/app-modules.php` to use `../../packages` and an `Acme` placeholder namespace — replace both with your real vendor/namespace). Full step-by-step for what to build by hand afterward (Models, Actions, DTOs, migration, ServiceProvider wiring, tests): `.claude/skills/new-module/SKILL.md`.

## Filament

`apps/admin` has three working panels — `admin`, `app`, `guest` — adapted from [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) (MIT), with login, profile, PWA, impersonation and developer-login plugins already wired. Full detail: `.claude/rules/filament-panels.md`.

## Roadmap (not built yet)

- `apps/web`: [`base_nuxt_modular`](https://github.com/tecrodrigocastro/base_nuxt_modular) exists as its own repo (`login` reference module, `HttpClient` already shaped to match this repo's `apps/backend`) — still needs to be dropped in here as `apps/web`, a sibling of `apps/backend` and `apps/admin`.
- `apps/backend` has no reference API surface yet — no Sanctum, no example JSON endpoint over `packages/withdrawals`. `WithdrawalResource` is the reference for Filament; there's no equivalent "how does `apps/web` actually call this" reference for the API side yet.
- `composer create-project` wizard for bootstrapping a fresh project from this template (rename the `acme` vendor/namespace throughout, prompt for the real one).

Done: this repo is a real, runnable monorepo — `apps/backend` and `apps/admin` both consume `packages/withdrawals` (proving the path-repository + `ServiceProvider` auto-discovery mechanism works across two independent Laravel apps, not just within one); `apps/admin` has three working Filament panels; `make check` runs Pint, Rector, PHPStan (Larastan) and Pest cleanly across both apps and every `packages/*`; `.claude/skills/new-module/` codifies the exact steps used to build `withdrawals` by hand. Not just documented — proven and enforced by `make check`.

See `.claude/rules/naming-conventions.md` before creating new files, `.claude/rules/architecture.md` for the full rationale behind the Action/Contract/Adapter rules, and `.claude/rules/tooling.md` for what `make check` actually runs.
