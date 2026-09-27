# base_laravel_modular

[Versão em português](README.pt-BR.md)

A Laravel **template** for a modular monolith: one Composer package per business module, shareable across one or many Laravel apps depending on the project shape you pick.

This repo is meant to be cloned/copied as the starting point for a new project, not extended into a product itself — same spirit as [`base_clean_arch_bloc`](../base_clean_arch_bloc), its Flutter counterpart.

> **Status:** early stage. What exists today is the architecture documentation and conventions (`CLAUDE.md`, `.claude/rules/`). There is no bootstrapped Laravel app, no module generator wired up, and no project-creation wizard yet — see the Roadmap section.

> **Working with AI assistants**: this project ships a `CLAUDE.md` and `.claude/rules/` so Claude Code (or any assistant that reads `CLAUDE.md`) already knows the architecture and naming conventions before generating anything.

## The idea

Every business module is its own Composer package under `packages/{module}/` — Models, Actions, DTOs, Enums, Events, its own migrations, its own `ServiceProvider`. That package shape never changes. What you choose per-project is how many Laravel apps sit on top of it:

- **single-project** — one Laravel app; an admin panel (e.g. Filament) is just another package in the same process.
- **monorepo-split** — one Git repository, multiple Laravel apps under `apps/*` (e.g. `apps/backend` for the API, `apps/admin` for a separate Filament project), all requiring the same `packages/*`.

Full comparison and when to pick each: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

## Structure

```
packages/
  {module}/
    composer.json           # type: library, own vendor/name
    src/
      Models/
      Actions/               # the only place allowed to mutate a Model with business logic behind it
      DTOs/
      Enums/
      Events/
      Contracts/             # interface an Action talks to, for modules likely to become a service later
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

## Core rules

- Every module is a real Composer package, never a loose folder glued into a Laravel app.
- Any write to a Model with business rules behind it goes through that module's `Actions/` — never a raw `->save()`/`->update()` from outside the package. This is what keeps two apps (or, later, two microservices) from drifting apart on the same rule.
- Modules likely to become a standalone service later (payments, a moderation-style pipeline) get a `Contract` + `Adapter` pair from day one, so swapping "local Eloquent" for "remote API client" doesn't touch any caller.
- No module reaches into another module's tables directly.

Full rationale: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Naming table for every element above: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Generator

Module scaffolding is meant to run on top of [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan module:make {name}`) instead of hand-rolled boilerplate. Not wired into this template yet.

## Filament

Whichever app hosts Filament uses [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) as the base — a Laravel 13 + Filament 5 starter kit with multi-panel + multi-guard already wired. Details: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Roadmap

- [ ] `.claude/skills/new-module/` wrapping `module:make` with this template's conventions (Actions/Contracts/Adapters, test stub, wiring) — mirrors `base_clean_arch_bloc`'s `new-feature` skill.
- [ ] `composer create-project` wizard (`post-create-project-cmd`, via `laravel/prompts`) asking single-project vs monorepo-split at creation time and rewiring the tree accordingly.
- [ ] A fully-implemented reference module (mirroring `auth` in `base_clean_arch_bloc`) that new modules imitate.
