# Project shape: single-project vs monorepo-split

The one thing that never changes between the two shapes is `packages/*` — every business module is always its own Composer package (Models, Actions, DTOs, Enums, Events, Contracts/Adapters, migrations, ServiceProvider), generated and structured the same way regardless of shape. What differs is only the **apps layer** sitting on top of it: how many Laravel apps exist and how each one requires the shared packages.

## single-project

One Laravel app. Everything — API routes, queue workers, and one or more Filament panels — runs in the same process. Filament panels are not `packages/*` modules; they are ordinary Laravel app code (`app/Providers/Filament/{Panel}PanelProvider.php` + `app/Filament/{Panel}/Resources/`) that consumes the shared packages' Models/Actions. See `filament-panels.md` for the panel/guard structure.

```
repo/
  app/
    Providers/Filament/
      AdminPanelProvider.php
    Filament/
      Admin/Resources/       # consumes packages/* Models/Actions, never the other way around
  packages/
    comunidades/
    pagamentos/
  composer.json              # "repositories": [{ "type": "path", "url": "packages/*" }]
```

Pick this when:
- There is one team, one deploy pipeline, and no near-term reason to release the admin panel independently from the API.
- You want the simplest possible operational footprint — one process to run, one `composer install`, one `.env`.

## monorepo-split

One Git repository, multiple Laravel apps under `apps/*`, each with its own `composer.json`, each requiring the same `packages/*` via a path repository. A non-Laravel frontend (e.g. a Nuxt storefront) can live in the same monorepo too, as a sibling under `apps/*`, even though it doesn't consume the PHP packages directly — it only talks to `apps/backend` over HTTP. `apps/admin` follows the same panel/guard structure as single-project's Filament app (see `filament-panels.md`), just living in its own Laravel install instead of sharing one with the API.

```
repo/
  apps/
    backend/               # Laravel Octane — the API
    admin/                 # Laravel + Filament — a separate project
      app/Providers/Filament/AdminPanelProvider.php
      app/Filament/Admin/Resources/   # consumes packages/* Models/Actions
    web/                   # optional: Nuxt storefront, talks to apps/backend's API
  packages/
    comunidades/
    pagamentos/
  # each apps/*/composer.json:
  # "repositories": [{ "type": "path", "url": "../../packages/*" }]
```

Pick this when:
- The admin panel (or any other surface) genuinely needs its own deploy lifecycle — different release cadence, different team, different scaling profile — from day one.
- You already know two "front doors" (e.g. API + admin) will both mutate the same data and want the Action/Contract discipline (see `architecture.md`) enforced across process boundaries from the start, not bolted on later.

## Why this is a per-project decision, not a permanent one

Start with whichever shape matches today's team size and constraints — single-project is simpler to operate and should be the default unless something in the project already demands otherwise. Moving from single-project to monorepo-split later is mechanical precisely because `packages/*` never changes shape: you add a second `apps/*` folder, give it its own `composer.json` pointing at the same `packages/*`, and move the surface (e.g. the admin panel) into it. The module boundary was never coupled to the apps boundary, so splitting an app doesn't touch a single package.

## How to tell which shape a concrete project uses

Check for an `apps/` directory at the repo root. If it exists, the project is monorepo-split and each subfolder is an independent Laravel (or other) app with its own `composer.json`/lockfile. If there is no `apps/` directory and `packages/*` is required straight from a root-level `composer.json`, the project is single-project.
