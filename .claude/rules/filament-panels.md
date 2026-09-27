# Filament panels: structure and reference

`apps/admin` follows the same technique for every panel it hosts: **one `PanelProvider` per surface/audience**, each with its own `id()`, `path()` and `authGuard()`, discovering its Resources from its own namespaced folder so panels never leak into each other's navigation even though they're compiled into the same app. `apps/admin/app/Providers/Filament/AdminPanelProvider.php` is a real, working instance of this — `id('admin')`, `path('admin')`, `authGuard('admin')` (guard configured in `apps/admin/config/auth.php`), discovering from `app/Filament/Admin/Resources`. `WithdrawalResource` in that folder imports `Acme\Withdrawals\Models\Withdrawal` straight from the shared package — the concrete example of the next paragraph.

## Going further

`apps/admin` here only has a bare `admin` panel wired — no login customization, no profile page, no PWA, no i18n. For a fuller starting point that already solves those, [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) is a working Laravel + Filament kit with multiple panels, each its own auth guard, plus login/profile/PWA/i18n already built. Its `AdminPanelProvider` follows the same shape `apps/admin` does here:

```php
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            // login, profile, plugins, middleware...
            ;
    }
}
```

Repeat this shape once per audience that needs its own panel in `apps/admin` — e.g. a second `PanelProvider` with `id('suppliers')`, `path('suppliers')`, `authGuard('supplier')`, discovering from `app_path('Filament/Suppliers/Resources')`.

## How this relates to `packages/*`

Panels and packages are **orthogonal groupings** of the same underlying domain:

- A **package** (`packages/{module}/`) groups Models/Actions/DTOs by *what business domain they belong to* (products, withdrawals, moderation...).
- A **panel** groups Filament Resources by *who is allowed to see them* (an internal admin, an external partner, a specific role).

A single panel's Resources can — and usually will — span multiple packages (an admin panel showing both `Product` and `Order` resources, each backed by its own package). The `Resources/` classes themselves are presentation code and live in the Filament app's own `app/Filament/{Panel}/Resources/`, never inside `packages/*` — a package should not know or care that Filament exists. A Resource class imports the package's Model/Action, not the other way around.

## Auth guards

One guard per audience, configured in `apps/admin/config/auth.php` (`admin`, and one per additional panel), matched 1:1 with each `PanelProvider`'s `->authGuard()`. This is what keeps, say, a supplier from ever hitting the admin panel's routes — Filament's panel middleware rejects it at the guard level, before any authorization logic in a Resource runs.
