# Naming conventions

Replace `acme`/`Acme` below with your organization or project's actual Composer vendor / namespace (configured in `config/app-modules.php`) — it is a placeholder throughout this template, not a literal value to keep. All identifiers, table names and comments are English, regardless of which spoken language the product itself targets — see `packages/withdrawals/` for a complete, working example of every row below.

| Element | Convention | Example (from `packages/withdrawals/`) |
|---|---|---|
| Composer package name | `{vendor}/{module-kebab-case}` | `acme/withdrawals` |
| Root namespace | `{Vendor}\{Module}` (PascalCase, matches PSR-4 root in the module's `composer.json`) | `Acme\Withdrawals` |
| ServiceProvider | `{Module}ServiceProvider` | `WithdrawalsServiceProvider` |
| Model | Singular noun, no suffix | `Withdrawal` |
| Action | `{Verb}{Noun}Action` — one class, one use case, one public `execute()`/`__invoke()` | `RequestWithdrawalAction`, `ApproveWithdrawalAction` |
| DTO | `{Name}DTO` | `RequestWithdrawalDTO` |
| Enum | `{Name}` for native PHP 8.1+ backed enums (the type itself already reads as an enum); `{Name}Enum` only if the codebase also has a non-enum class of the same bare name | `WithdrawalStatus: string` |
| Event | `{Fact}Event`, past tense — an event is something that already happened | `WithdrawalApprovedEvent` |
| Contract | `{Capability}Contract` (interface) | `WithdrawalGatewayContract` |
| Adapter | `{Implementation}{Contract}Adapter` | `LocalWithdrawalGatewayAdapter` |
| Table / migration | snake_case plural table, standard Laravel migration filename | `withdrawals` table, `2025_01_01_000000_create_withdrawals_table.php` |
| Test | mirrors `src/` under `tests/`, one file per class under test | `tests/Actions/RequestWithdrawalActionTest.php` |

## Notes

- **Actions are the only place allowed to change state for a Model with business logic behind it** (see `architecture.md`). Name them by the use case, not by CRUD verb — `ApproveWithdrawalAction`, not `UpdateWithdrawalAction`.
- **Events are facts, not commands.** `WithdrawalApprovedEvent` is correct; `ApproveWithdrawalEvent` describes an intent, which belongs to an Action, not an Event.
- **Contracts/Adapters are opt-in**, not required on every module — only on the ones flagged as extraction candidates in that module's own docs (see `architecture.md`, "Contract + Adapter"). Don't add the pair to a module that will never plausibly leave the monolith; that's premature abstraction.
- Classes are plain `class`, not `final class`, except Adapters (`final class {X}Adapter`) and DTOs (`final class {X}DTO`), which should not be extended.
