# Filament panels: structure and reference

`apps/admin` hosts three panels, each its own `PanelProvider` in `app/Providers/Filament/`, registered conditionally from `AppServiceProvider::register()` via `config/panels.php` (`admin_panel_enabled`, `app_panel_enabled`, `guest_panel_enabled` — flip one off if a project doesn't need that audience):

| Panel | Provider | `id()` / `path()` | `authGuard()` | Model / table |
|---|---|---|---|---|
| Admin | `AdminPanelProvider` | `admin` / `/admin` | `admin` | `App\Models\Admin` / `admins` |
| App (regular users) | `AppPanelProvider` | `app` / `/app` | `web` | `App\Models\User` / `users` |
| Guest (public, unauthenticated) | `GuestPanelProvider` | `guest` / `/` | none (`userMenu(false)`) | — |

Each discovers its own namespaced Resources/Pages/Widgets/Clusters folder (`app/Filament/Admin/Resources`, `app/Filament/App/Resources`, `app/Filament/Guest/Resources`) so panels never leak into each other's navigation even though they're compiled into the same app. `AdminPanelProvider`'s `WithdrawalResource` imports `Acme\Withdrawals\Models\Withdrawal` straight from the shared package — the concrete example of "How this relates to `packages/*`" below.

This panel structure, the `admins`/`notifications` migration, and the plugin stack below were adapted from [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) (MIT), stripped of its branding (logo, "Filakit" naming, `filakit.*` config keys renamed to `panels.*`). `App\Models\User`/`App\Models\Admin` deviate from FilaKit's own shape on purpose: they extend `Acme\Identity\Models\User`/`Admin` (from `packages/identity`, required by `apps/backend` too) instead of `jeffersongoncalves/filament-user`'s and `filament-admin`'s own base Models, and add the `FilamentUser`/`HasAvatar` contracts locally rather than inheriting them — see `architecture.md`, "Two different reasons a Model must live in `packages/*`", for why the `users`/`admins` tables specifically had to be shared instead of left one-per-app like FilaKit itself does.

## Plugins already wired (all three panels, where it makes sense)

| Package | What it adds |
|---|---|
| `jeffersongoncalves/filament-admin`, `filament-user` | The `UserPlugin`/`AdminPlugin` Filament resources for managing users/admins, the `PanelAccess`/`FilamentAdmin` panel-gating helpers, the developer-login/profile config keys. Their own bundled `Models\User`/`Models\Admin` classes are not what `App\Models\User`/`Admin` extend — see the note above |
| `jeffersongoncalves/filament-pwa` + `jeffersongoncalves/laravel-pwa-favicon` | Installable PWA: `/manifest.json`, full icon set, `<head>` metas — see "Vite and the favicon assets" below |
| `jeffersongoncalves/laravel-favicon` | Plain `/favicon.ico` + `/browserconfig.xml` outside the PWA manifest |
| `joaopaulolndev/filament-edit-profile` | The "My Profile" page (locale, theme color, avatar, Sanctum tokens, MFA, browser sessions) |
| `dutchcodingcompany/filament-developer-logins` | One-click login as any seeded user/admin, gated to `app()->environment('local')` |
| `stechstudio/filament-impersonate` | "Log in as this user" action, configured in `AppServiceProvider::configureImpersonate()` |
| `achyutn/filament-log-viewer` | `/admin/logs` — reads `storage/logs/laravel.log` from the panel, admin panel only |
| `jeffersongoncalves/filament-additional-information`, `filament-sensible-defaults` | Misc Filament defaults FilaKit ships with; safe to drop if unused |

## Vite and the favicon assets

`jeffersongoncalves/laravel-favicon`/`laravel-pwa-favicon` resolve every icon through `Vite::asset('resources/favicon/...')`, which means each file under `resources/favicon/` must be its own entry in the Vite build — a plain `<img>`-style reference wouldn't need this, but these packages' PHP-side `Vite::asset()` calls do. `vite.config.js` globs the directory automatically:

```js
import { globSync } from 'glob';
const faviconAssets = globSync('resources/favicon/**/*');
// ...
input: [/* css/js entries */, ...faviconAssets],
```

Run `npm run build` (or `npm run dev`) before booting the app — without a built manifest, every panel throws `Vite manifest not found`.

## Adding another panel

Copy the shape of `GuestPanelProvider` (simplest, no login) or `AppPanelProvider` (authenticated, has profile/plugins) for a new audience — e.g. a `SuppliersPanelProvider` with `id('suppliers')`, `path('suppliers')`, `authGuard('supplier')`, discovering from `app_path('Filament/Suppliers/Resources')`. Register it in `AppServiceProvider::register()` behind a new `config('panels.suppliers_panel_enabled')` flag, and add the matching guard/provider pair to `config/auth.php` (see "Auth guards" below).

## How this relates to `packages/*`

Panels and packages are **orthogonal groupings** of the same underlying domain:

- A **package** (`packages/{module}/`) groups Models/Actions/DTOs by *what business domain they belong to* (products, withdrawals, moderation...).
- A **panel** groups Filament Resources by *who is allowed to see them* (an internal admin, a regular user, the public).

A single panel's Resources can — and usually will — span multiple packages (the admin panel showing both `Withdrawal` and a future `Order` resource, each backed by its own package). The `Resources/` classes themselves are presentation code and live in the Filament app's own `app/Filament/{Panel}/Resources/`, never inside `packages/*` — a package should not know or care that Filament exists. A Resource class imports the package's Model/Action, not the other way around, and any Resource action beyond plain CRUD (approve, cancel, ...) calls the module's Action rather than mutating the Model inline.

## Auth guards

One guard per audience, configured in `apps/admin/config/auth.php` — `admin` (provider `admins`, model `App\Models\Admin`) and `web` (provider `users`, model `App\Models\User`), matched 1:1 with each `PanelProvider`'s `->authGuard()`. This is what keeps, say, a regular user from ever hitting the admin panel's routes — Filament's panel middleware rejects it at the guard level, before any authorization logic in a Resource runs. A new panel for a new audience gets its own guard + provider pair here, not a reused one, even if it reuses the `users` table for simplicity early on.
