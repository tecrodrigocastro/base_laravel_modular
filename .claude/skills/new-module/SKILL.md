---
name: new-module
description: Scaffold a new packages/* business module (Composer package with Models, Actions, DTOs, Enums, Events, migration, ServiceProvider, tests) following this project's modular-monorepo conventions. Use whenever the user asks to add a new module/domain/bounded-context, not just a single class inside an existing one.
---

# New Module Scaffold

Generates every layer of a module the same way `packages/withdrawals/` is built — use `withdrawals` as the live reference whenever a template below is ambiguous. Package/class boundaries and the Action rule are in `.claude/rules/architecture.md`; naming is in `.claude/rules/naming-conventions.md`; how `packages/*` relates to `apps/backend`/`apps/admin` is in `.claude/rules/project-shape.md`. Read all three before scaffolding the first module in a session.

## Step 0: run the generator first

Don't hand-write the package skeleton below from scratch — [`internachi/modular`](https://github.com/InterNACHI/modular) generates it in one shot (composer.json, ServiceProvider, PSR-4 dirs, routes stub, test dir). Run from the repo root:

```bash
make new-module name={module-name}
# equivalent to: cd apps/backend && php artisan make:module {module-name}
```

This writes `packages/{module-name}/` and adds `"acme/{module-name}": "*"` to `apps/backend/composer.json`. Two things to fix immediately after, both by hand — the generator doesn't know either:

1. **Tighten the version constraint.** Open `packages/{module-name}/composer.json`, set `"version": "1.0.0"` (not the generator's `"1.0"`). Then in `apps/backend/composer.json` (and `apps/admin/composer.json` if that app will use this module too), change `"acme/{module-name}": "*"` to `"acme/{module-name}": "^1.0.0"` — never leave a loose `*`/`dev-main` constraint on an intra-repo module, see `naming-conventions.md`.
2. **Run `composer update acme/{module-name}` in each app that requires it** (backend always; admin only if it will consume this module's Models/Actions from a Filament Resource).

```bash
cd apps/backend && composer update acme/{module-name}
# only if apps/admin also needs this module:
cd apps/admin && composer update acme/{module-name}
```

Everything from here down is what to build inside the generated `packages/{module-name}/src/` by hand — there is no further generator for these, follow the shape `withdrawals` already demonstrates.

## Before writing anything

Ask (or infer from context) if unclear:
1. Module name (e.g. `products`, `orders`) — plural, kebab-case for the package/folder, drives the `Acme\{PascalCase}` namespace.
2. What the core Model is and its fields.
3. Which Actions it needs (e.g. `Request{X}`, `Approve{X}`) — don't scaffold CRUD nobody asked for.
4. Whether this module is a serious candidate to become a standalone service later (heavy queue/latency profile — payments-like, moderation-like). Only those get the `Contracts/`+`Adapters/` pair; skip it for a simple catalog/settings-style module (see `architecture.md`, "Contract + Adapter").

## Steps

### 1. Create the src/ subfolders

The generator only creates `src/Providers/`. Add the rest:

```bash
mkdir -p packages/{module-name}/src/{Models,Actions,DTOs,Enums,Events,Exceptions}
# only if this module got a "yes" on question 4 above:
mkdir -p packages/{module-name}/src/{Contracts,Adapters}
```

### 2. Enum (if the Model has a status-like field)

`src/Enums/{Thing}Status.php` — native PHP 8.1+ backed enum, English cases:

```php
<?php

namespace Acme\{Module}\Enums;

enum {Thing}Status: string
{
    case Requested = 'requested';
    case Paid = 'paid';
}
```

### 3. Model

`src/Models/{Thing}.php` — singular noun, no suffix, `$table` explicit, cast the status column to the enum:

```php
<?php

namespace Acme\{Module}\Models;

use Acme\{Module}\Enums\{Thing}Status;
use Illuminate\Database\Eloquent\Model;

class {Thing} extends Model
{
    protected $table = '{things}';

    protected $fillable = ['field_one', 'field_two', 'status'];

    protected $casts = [
        'status' => {Thing}Status::class,
    ];
}
```

### 4. DTO

`src/DTOs/{Verb}{Thing}DTO.php` — `final class`, `readonly` constructor-promoted properties, one per Action input:

```php
<?php

namespace Acme\{Module}\DTOs;

final class {Verb}{Thing}DTO
{
    public function __construct(
        public readonly string $fieldOne,
        public readonly int $fieldTwo,
    ) {}
}
```

### 5. Exception (only if the Action has a real failure case to name)

`src/Exceptions/{Reason}Exception.php` — `RuntimeException`, named static constructor per failure reason, don't create one speculatively:

```php
<?php

namespace Acme\{Module}\Exceptions;

use RuntimeException;

class Invalid{Thing}Exception extends RuntimeException
{
    public static function {reason}(/* args */): self
    {
        return new self('Human-readable message.');
    }
}
```

### 6. Event

`src/Events/{Thing}{PastTenseFact}Event.php` — a fact, not a command; `final class`, holds the affected Model:

```php
<?php

namespace Acme\{Module}\Events;

use Acme\{Module}\Models\{Thing};

final class {Thing}{PastTenseFact}Event
{
    public function __construct(
        public readonly {Thing} ${thing},
    ) {}
}
```

### 7. Contract + Adapter (only for a module flagged in step 0.4)

`src/Contracts/{Capability}Contract.php`:

```php
<?php

namespace Acme\{Module}\Contracts;

use Acme\{Module}\Models\{Thing};

interface {Capability}Contract
{
    public function {verb}({Thing} ${thing}): void;
}
```

`src/Adapters/Local{Capability}Adapter.php` — today's implementation; a `Remote{Capability}Adapter` gets added later at extraction time without touching any Action:

```php
<?php

namespace Acme\{Module}\Adapters;

use Acme\{Module}\Contracts\{Capability}Contract;
use Acme\{Module}\Models\{Thing};

final class Local{Capability}Adapter implements {Capability}Contract
{
    public function {verb}({Thing} ${thing}): void
    {
        // today: local implementation
    }
}
```

### 8. Actions

One Action per use case, `{Verb}{Thing}Action`, single public `execute()`. A read/validate-only Action takes a DTO and returns the Model; a state-changing Action takes the Model (plus the bound Contract if step 7 applies) and returns it after mutating:

```php
<?php

namespace Acme\{Module}\Actions;

use Acme\{Module}\DTOs\{Verb}{Thing}DTO;
use Acme\{Module}\Enums\{Thing}Status;
use Acme\{Module}\Models\{Thing};

final class {Verb}{Thing}Action
{
    public function execute({Verb}{Thing}DTO $dto): {Thing}
    {
        return {Thing}::create([
            'field_one' => $dto->fieldOne,
            'field_two' => $dto->fieldTwo,
            'status' => {Thing}Status::Requested,
        ]);
    }
}
```

```php
<?php

namespace Acme\{Module}\Actions;

use Acme\{Module}\Contracts\{Capability}Contract;
use Acme\{Module}\Enums\{Thing}Status;
use Acme\{Module}\Events\{Thing}{PastTenseFact}Event;
use Acme\{Module}\Models\{Thing};

final class Approve{Thing}Action
{
    public function __construct(
        private readonly {Capability}Contract $gateway,
    ) {}

    public function execute({Thing} ${thing}): {Thing}
    {
        $this->gateway->{verb}(${thing});

        ${thing}->update(['status' => {Thing}Status::Paid]);

        event(new {Thing}{PastTenseFact}Event(${thing}));

        return ${thing};
    }
}
```

**Never mutate the Model directly from outside an Action** (`->save()`/`->update()` called from a Filament Resource, a controller, anywhere in `apps/*`) once it has business logic behind it — that's the rule the whole package boundary exists to enforce.

### 9. Migration

`database/migrations/{date}_000000_create_{things}_table.php` — standard Laravel migration, `status` as `string` (the enum cast handles the PHP side):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{things}', function (Blueprint $table) {
            $table->id();
            $table->string('field_one');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{things}');
    }
};
```

### 10. Wire the ServiceProvider

The generator's `src/Providers/{Module}ServiceProvider.php` has empty `register()`/`boot()`. Fill in migration loading, and the Contract→Adapter binding if step 7 applies:

```php
<?php

namespace Acme\{Module}\Providers;

use Acme\{Module}\Adapters\Local{Capability}Adapter; // only if step 7 applies
use Acme\{Module}\Contracts\{Capability}Contract;      // only if step 7 applies
use Illuminate\Support\ServiceProvider;

class {Module}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind({Capability}Contract::class, Local{Capability}Adapter::class); // only if step 7 applies
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
```

### 11. PHPStan for the module

`packages/{module-name}/phpstan.neon` — this is all it needs; `apps/backend/phpstan.modules.php` globs it in automatically, nothing else to wire:

```neon
parameters:
    paths:
        - src/
```

### 12. Tests

Mirror `packages/withdrawals/tests/Actions/` — one test file per Action, in `packages/{module-name}/tests/Actions/`:

```php
<?php

use Acme\{Module}\Actions\{Verb}{Thing}Action;
use Acme\{Module}\DTOs\{Verb}{Thing}DTO;

it('creates a {Thing} with the expected fields', function () {
    ${thing} = (new {Verb}{Thing}Action)->execute(new {Verb}{Thing}DTO(
        fieldOne: 'value',
        fieldTwo: 1,
    ));

    expect(${thing}->field_one)->toBe('value');
});
```

For an Action with a Contract dependency, mock the Contract instead of hitting anything real — see `packages/withdrawals/tests/Actions/ApproveWithdrawalActionTest.php`:

```php
$gateway = Mockery::mock({Capability}Contract::class);
$gateway->shouldReceive('{verb}')->once();
app()->instance({Capability}Contract::class, $gateway);
```

### 13. If apps/admin needs a Filament Resource over this module

Follow `.claude/rules/filament-panels.md`. From `apps/admin`:

```bash
php artisan make:filament-resource {Thing} --model-namespace="Acme\{Module}\Models" --resource-namespace="App\Filament\Admin\Resources" --generate
```

The generated `{Thing}Resource.php` should `use Acme\{Module}\Models\{Thing};` — confirm the import landed on the package's Model, not a duplicate one under `App\Models`. Any Resource action beyond plain CRUD (approve, cancel, etc.) must call the module's Action, never mutate the Model inline in the Resource/Table class.

### 14. Verify

```bash
make check   # rector --dry-run + pint --test + phpstan (both apps) + pest (both apps)
```

Or targeted, while iterating on just this module:

```bash
cd apps/backend && ./vendor/bin/pest ../../packages/{module-name}/tests
cd apps/backend && ./vendor/bin/phpstan analyse --ansi --memory-limit=2G
```
