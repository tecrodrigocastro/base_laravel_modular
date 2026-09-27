# Architecture

## Why packages, not folders

A module living as `app/Modules/Pagamentos/` inside one Laravel app is easy to write and easy to let rot: nothing stops another part of the app from reaching into its Models directly, and there is no boundary left to cut along if that module ever needs to become its own service. Making every module a real Composer package (own `composer.json`, own PSR-4 root, own migrations, own `ServiceProvider`) buys two things immediately:

- **An enforced boundary.** You can only use what the module's `src/` exposes; there is no accidental `use App\Modules\Pagamentos\Models\Split` from outside because there is no `App\Modules\...` namespace to import from — only `Vendor\Pagamentos\...`, whatever that package chooses to autoload.
- **A shape that already looks like the eventual microservice.** Extracting a module later is "give this package its own app and database," not "figure out which files belong to it first."

## The Action rule

Any Model that has business logic behind it (side effects, invariants, notifications, financial calculations) is only ever mutated through that module's `Actions/`. Reads for simple display are fine directly on the Model; writes, and reads that need to enforce an invariant, go through an Action.

```php
// Wrong — bypasses whatever CalcularSplitAction enforces (rounding rules, notifications, idempotency)
$saque->update(['status' => 'pago']);

// Right — the rule lives in one place, called from any consumer (API, admin panel, console command)
app(AprovarSaqueAction::class)->execute($saque);
```

This is what keeps two separate apps (API + admin panel in monorepo-split shape) from drifting: if both call the same Action, both get the same side effects, the same validation, the same events dispatched. Without this rule, an admin panel with direct Eloquent access is a second front door into the same data, and it *will* eventually skip something the API enforces.

## Contract + Adapter for modules likely to become a service

A handful of modules — typically the ones with a heavy queue/latency profile, like payments or a moderation pipeline — are the most likely candidates to be pulled out into their own deployable service once the load justifies it. For those, don't let the Action talk to Eloquent directly. Put an interface (`Contract`) between them, and an `Adapter` that implements it:

```php
interface SplitGatewayContract
{
    public function calcular(Pedido $pedido): SplitResultDTO;
}

final class LocalSplitGatewayAdapter implements SplitGatewayContract
{
    // today: queries the local database directly
}

final class RemoteSplitGatewayAdapter implements SplitGatewayContract
{
    // later: calls the extracted service's HTTP API
    // same method signature, so CalcularSplitAction never changes
}
```

`CalcularSplitAction` depends on `SplitGatewayContract`, never on a concrete Adapter. Extracting the module into its own service becomes: stand up the new service, write `RemoteSplitGatewayAdapter`, swap the binding in the container. Every caller — the API, the admin panel, anything else in `packages/*` — keeps working unmodified.

Modules without this heavy profile (a simple catalog, a settings module) don't need a Contract/Adapter pair up front — that's premature abstraction for something unlikely to ever move. Add it when a module is actually a serious extraction candidate, not by default.

## Cross-module access

A module never queries another module's tables directly — no Eloquent relationship crossing a package boundary, no raw join reaching into a table another package owns. If module `pedidos` needs data that belongs to `produtos`, it calls `produtos`' own Action/Contract, exactly as an external caller would. This is the same discipline as the Action rule, aimed at a different direction: it keeps every module's internal schema free to change without a silent break somewhere else in the monolith, and it means a cross-module call already looks exactly like the network call it may become after extraction.

## Path repositories: how the packages actually get wired in

Each consuming app (`apps/backend`, `apps/admin`, or the single app in single-project shape) declares the packages directory as a Composer path repository and requires the modules it needs like any other dependency:

```json
{
  "repositories": [
    { "type": "path", "url": "../../packages/*" }
  ],
  "require": {
    "vendor/comunidades": "*",
    "vendor/pagamentos": "*"
  }
}
```

Laravel's package auto-discovery picks up each module's `ServiceProvider` (declared under `extra.laravel.providers` in the module's own `composer.json`) without any manual registration in `config/app.php`. Migrations under each module's `database/migrations/` are picked up the same way when the module's service provider calls `$this->loadMigrationsFrom(...)`.
