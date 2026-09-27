# Monorepo layout

This template is one Git repository holding multiple apps under `apps/*`, all sharing the same `packages/*` business modules via Composer path repositories. `packages/*` never changes shape — every business module is its own Composer package (Models, Actions, DTOs, Enums, Events, Contracts/Adapters, migrations, `ServiceProvider`) regardless of which app consumes it.

```
repo/
  apps/
    backend/               # Laravel API — the source of truth for business writes
    admin/                 # Laravel + Filament — internal panel(s), see filament-panels.md
      app/Providers/Filament/AdminPanelProvider.php
      app/Filament/Admin/Resources/   # consumes packages/* Models/Actions, never the other way around
    web/                   # not scaffolded here yet — a Nuxt frontend, or a project of its own,
                            # dropped in later; only ever talks to apps/backend's API over HTTP
  packages/
    withdrawals/            # the reference module — see naming-conventions.md
    {module}/
  # each apps/backend and apps/admin composer.json:
  # "repositories": [{ "type": "path", "url": "../../packages/*" }]
```

## Why one repo, several apps

- **`apps/backend`** owns the API and every write path — anything that mutates a `packages/*` Model with business rules behind it goes through this app or through a queue worker it runs, calling the module's `Actions/` (see `architecture.md`). It's the one app every other surface ultimately depends on.
- **`apps/admin`** is a separate Laravel install specifically because an internal panel (Filament) has a different deploy cadence, different auth surface, and different operational profile than the public API — see `filament-panels.md` for how it consumes `packages/*` without ever bypassing the Action rule.
- **`apps/web`** is not scaffolded in this repo yet — whatever ends up there (Nuxt or otherwise) doesn't touch `packages/*` or Composer at all, it's a plain HTTP client of `apps/backend`'s API. It's documented here because it's still part of the monorepo idea: living alongside the backend for the convenience of coordinated changes (a backend endpoint and the frontend page that calls it can land in one commit), not because it shares any PHP mechanism with `packages/*`.
- A single repository (rather than one per app) means a business-rule change that touches both `apps/backend` and `apps/admin` — because both call the same `packages/*` Action — lands in one PR, with no cross-repo version to pin or coordinate.

## If you don't need the split

Nothing about `packages/*` requires multiple apps. A small project with no separate internal panel can fold `apps/admin`'s responsibility into `apps/backend` itself — Filament panel code (`app/Providers/Filament/`, `app/Filament/{Panel}/Resources/`) just lives inside the one app instead of a sibling one, still consuming the same `packages/*` the same way. Start with the split only if you already know a surface needs its own deploy lifecycle; collapsing two apps into one later is easy precisely because `packages/*` never had to change.
