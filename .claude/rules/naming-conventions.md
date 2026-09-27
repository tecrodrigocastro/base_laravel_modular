# Naming conventions

Replace `Vendor`/`vendor` below with your organization or project's actual Composer vendor name — it is a placeholder throughout this template, not a literal value to keep.

| Element | Convention | Example |
|---|---|---|
| Composer package name | `{vendor}/{module-kebab-case}` | `vendor/pagamentos` |
| Root namespace | `{Vendor}\{Module}` (PascalCase, matches PSR-4 root in the module's `composer.json`) | `Vendor\Pagamentos` |
| ServiceProvider | `{Module}ServiceProvider` | `PagamentosServiceProvider` |
| Model | Singular noun, no suffix | `Saque`, `Split`, `Pedido` |
| Action | `{Verb}{Noun}Action` — one class, one use case, one public `execute()`/`__invoke()` | `CalcularSplitAction`, `AprovarSaqueAction` |
| DTO | `{Name}DTO` | `SplitResultDTO`, `CriarPedidoDTO` |
| Enum | `{Name}` for native PHP 8.1+ backed enums (the type itself already reads as an enum); `{Name}Enum` only if the codebase also has a non-enum class of the same bare name | `StatusSaque: string` |
| Event | `{Fact}Event`, past tense — an event is something that already happened | `SaqueAprovadoEvent`, `PedidoPagoEvent` |
| Contract | `{Capability}Contract` (interface) | `SplitGatewayContract` |
| Adapter | `{Implementation}{Contract}Adapter` | `LocalSplitGatewayAdapter`, `AsaasSplitGatewayAdapter` |
| Migration | standard Laravel convention (`{timestamp}_{description}.php`), inside the module's own `database/migrations/` | `2026_09_27_000000_create_saques_table.php` |
| Test | mirrors `src/` under `tests/`, one file per class under test | `tests/Actions/CalcularSplitActionTest.php` |

## Notes

- **Actions are the only place allowed to change state for a Model with business logic behind it** (see `architecture.md`). Name them by the use case, not by CRUD verb — `AprovarSaqueAction`, not `UpdateSaqueAction`.
- **Events are facts, not commands.** `PedidoPagoEvent` is correct; `PagarPedidoEvent` describes an intent, which belongs to an Action, not an Event.
- **Contracts/Adapters are opt-in**, not required on every module — only on the ones flagged as extraction candidates in that module's own docs (see `architecture.md`, "Contract + Adapter"). Don't add the pair to a module that will never plausibly leave the monolith; that's premature abstraction.
- Classes are plain `class`, not `final class`, except Adapters (`final class {X}Adapter`) and DTOs (`final class {X}DTO`), which should not be extended.
