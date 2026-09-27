# Architecture

## Why packages, not folders

A module living as `app/Modules/Withdrawals/` inside one Laravel app is easy to write and easy to let rot: nothing stops another part of the app from reaching into its Models directly, and there is no boundary left to cut along if that module ever needs to become its own service. Making every module a real Composer package (own `composer.json`, own PSR-4 root, own migrations, own `ServiceProvider`) buys two things immediately:

- **An enforced boundary.** You can only use what the module's `src/` exposes; there is no accidental `use App\Modules\Withdrawals\Models\Withdrawal` from outside because there is no `App\Modules\...` namespace to import from — only `Acme\Withdrawals\...`, whatever that package chooses to autoload.
- **A shape that already looks like the eventual microservice.** Extracting a module later is "give this package its own app and database," not "figure out which files belong to it first."

## The Action rule

Any Model that has business logic behind it (side effects, invariants, notifications, financial calculations) is only ever mutated through that module's `Actions/`. Reads for simple display are fine directly on the Model; writes, and reads that need to enforce an invariant, go through an Action.

```php
// Wrong — bypasses whatever ApproveWithdrawalAction enforces (the gateway call, notifications, idempotency)
$withdrawal->update(['status' => 'paid']);

// Right — the rule lives in one place, called from any consumer (API, admin panel, console command)
app(ApproveWithdrawalAction::class)->execute($withdrawal);
```

This is what keeps `apps/backend` and `apps/admin` from drifting: if both call the same Action, both get the same side effects, the same validation, the same events dispatched. Without this rule, an admin panel with direct Eloquent access is a second front door into the same data, and it *will* eventually skip something the API enforces.

## Contract + Adapter for modules likely to become a service

A handful of modules — typically the ones with a heavy queue/latency profile, like payments or a moderation pipeline — are the most likely candidates to be pulled out into their own deployable service once the load justifies it. For those, don't let the Action talk to Eloquent directly. Put an interface (`Contract`) between them, and an `Adapter` that implements it. `packages/withdrawals/` ships a real, working version of this:

```php
interface WithdrawalGatewayContract
{
    public function transfer(Withdrawal $withdrawal): void;
}

final class LocalWithdrawalGatewayAdapter implements WithdrawalGatewayContract
{
    // today: no real gateway wired in, see the class docblock
}

final class RemoteWithdrawalGatewayAdapter implements WithdrawalGatewayContract
{
    // later: calls the extracted service's HTTP API
    // same method signature, so ApproveWithdrawalAction never changes
}
```

`ApproveWithdrawalAction` depends on `WithdrawalGatewayContract`, never on a concrete Adapter — the contract is bound to `LocalWithdrawalGatewayAdapter` in `WithdrawalsServiceProvider::register()`. Extracting the module into its own service becomes: stand up the new service, write `RemoteWithdrawalGatewayAdapter`, swap that binding. Every caller — the API, the admin panel, anything else in `packages/*` — keeps working unmodified. `tests/Actions/ApproveWithdrawalActionTest.php` shows the same seam paying off in tests: it swaps in a mock gateway instead of hitting anything real.

Modules without this heavy profile (a simple catalog, a settings module) don't need a Contract/Adapter pair up front — that's premature abstraction for something unlikely to ever move. Add it when a module is actually a serious extraction candidate, not by default.

## Two different reasons a Model must live in `packages/*`

`withdrawals` and `identity` end up in the same place (`packages/*`) for two different reasons — worth keeping distinct, because the second one is easy to miss:

- **A business module** (`withdrawals`) is a package so its boundary can be enforced and so it's easy to extract later. Nothing stops it from living app-local if a project genuinely only has one app.
- **A shared identity Model** (`identity`'s `User`/`Admin`) is a package because **two apps must authenticate against and reference the literal same row**, not because it's a microservice candidate. `apps/backend` and `apps/admin` each ran their own copy of a `users` migration early in this template's history — two independent tables, two independent schemas, silently diverging (`apps/admin`'s FilaKit-derived migration had `status`/`avatar_url`/`custom_fields`/`locale`/`theme_color` columns `apps/backend`'s default Laravel migration didn't). A user created through the API was invisible to the admin panel. That's not a Contract/Adapter problem — the fix is putting the Model, migration and factory in exactly one package both apps require, the same way `withdrawals` is shared, just for a different reason.

Panel-specific concerns (Filament's `FilamentUser`/`HasAvatar` contracts) still don't belong in the shared package — `apps/admin`'s `App\Models\User`/`App\Models\Admin` are thin subclasses of `Acme\Identity\Models\User`/`Admin` that add those contracts locally, so `acme/identity` itself never depends on Filament and stays safe for `apps/backend` to require too.

When adding a new module, ask both questions independently: *"does this need a Contract/Adapter because it might become a service?"* and *"does this Model represent something more than one app must see identically?"* — a module can need either, both, or neither.

## Cross-module access

A module never queries another module's tables directly — no Eloquent relationship crossing a package boundary, no raw join reaching into a table another package owns. If module `orders` needs data that belongs to `products`, it calls `products`' own Action/Contract, exactly as an external caller would. This is the same discipline as the Action rule, aimed at a different direction: it keeps every module's internal schema free to change without a silent break somewhere else in the monolith, and it means a cross-module call already looks exactly like the network call it may become after extraction.

## Path repositories: how the packages actually get wired in

Each consuming app (`apps/backend`, `apps/admin`) declares the packages directory as a Composer path repository and requires the modules it needs like any other dependency:

```json
{
  "repositories": [
    { "type": "path", "url": "../../packages/*" }
  ],
  "require": {
    "acme/identity": "^1.0.0",
    "acme/withdrawals": "^1.0.0"
  }
}
```

Laravel's package auto-discovery picks up each module's `ServiceProvider` (declared under `extra.laravel.providers` in the module's own `composer.json`) without any manual registration in `config/app.php`. Migrations under each module's `database/migrations/` are picked up the same way when the module's service provider calls `$this->loadMigrationsFrom(...)`.
