# base_laravel_modular

[English version](README.md)

Um **template de monorepo** pra um monólito modular Laravel: `apps/backend` (API), `apps/admin` (Filament, três painéis), e um pacote Composer por módulo de negócio em `packages/*`, compartilhado entre os dois apps. O `apps/web` está reservado mas ainda não foi montado aqui — um template de frontend separado entra depois e é referenciado; ele só fala com a API do `apps/backend` por HTTP, nunca com `packages/*`.

Este repositório é feito pra ser clonado/copiado como ponto de partida de um projeto novo, não pra crescer virando um produto em si — mesmo espírito do [`base_clean_arch_bloc`](../base_clean_arch_bloc), seu equivalente em Flutter.

> **Status:** estágio inicial, mas já roda e é verificado. `apps/backend` e `apps/admin` são dois apps Laravel de verdade, os dois consumindo `packages/withdrawals` como módulo de referência totalmente implementado; `apps/admin` tem três painéis Filament funcionando (adaptados do FilaKit). `make check` roda Pint, Rector, PHPStan e Pest limpos nos dois apps. Ainda falta: o frontend do `apps/web` — ver a seção Roadmap.

```bash
make install     # composer/npm install nos dois apps
make check         # rector --dry-run + pint --test + phpstan + pest, nos dois apps
```

> **Trabalhando com assistentes de IA**: este projeto tem `CLAUDE.md` e `.claude/rules/` pra que o Claude Code (ou qualquer assistente que leia `CLAUDE.md`) já conheça a arquitetura e as convenções de nomenclatura antes de gerar qualquer coisa.

## A ideia

Todo módulo de negócio é seu próprio pacote Composer em `packages/{modulo}/` — Models, Actions, DTOs, Enums, Events, migrations próprias, `ServiceProvider` próprio. Esse formato de pacote nunca muda, independente de quantos apps existem por cima dele — aqui, `apps/backend` (a API, dona de todo caminho de escrita) e `apps/admin` (painel interno em Filament) exigem o mesmo `packages/*` via path repository do Composer. O `apps/web`, quando entrar, vai ser só um cliente HTTP comum da API do `apps/backend` — nunca toca em `packages/*` nem em banco de dados.

Por que monorepo, por que os apps são divididos assim, e o que fazer se você não precisar dessa divisão: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

## Estrutura

```
apps/
  backend/                     # Laravel — a API, dona de todo caminho de escrita
  admin/                       # Laravel + Filament — 3 painéis (admin/app/guest), ver filament-panels.md
    app/Providers/Filament/{Admin,App,Guest}PanelProvider.php
    app/Filament/Admin/Resources/WithdrawalResource.php   # importa Acme\Withdrawals\Models\Withdrawal
  web/                          # ainda não montado aqui — template de frontend entra depois, só HTTP
packages/
  withdrawals/                  # módulo de referência
    composer.json               # type: library, vendor/nome próprio, versão pinada ^1.0.0 pelos apps
    src/
      Models/
      Actions/                  # único lugar autorizado a mutar um Model com regra de negócio por trás
      DTOs/
      Enums/
      Events/
      Contracts/                # interface que a Action usa, pros módulos candidatos a virar serviço depois
      Adapters/                  # implementação concreta de um Contract (local hoje, remota depois)
      Providers/{Modulo}ServiceProvider.php
    database/migrations/
    phpstan.neon                 # incluído automaticamente por apps/backend/phpstan.modules.php
    tests/
Makefile                         # orquestra os dois apps
```

## Regras principais

- Todo módulo é um pacote Composer de verdade, nunca uma pasta solta colada dentro de um app.
- Qualquer escrita num Model com regra de negócio por trás passa pela `Actions/` daquele módulo — nunca um `->save()`/`->update()` cru vindo de fora do pacote. É isso que evita `apps/backend` e `apps/admin` (ou, depois, um microsserviço extraído) divergirem na mesma regra.
- Módulos com chance real de virar serviço próprio depois (pagamentos, uma pipeline de moderação) ganham um par `Contract` + `Adapter` desde o início, pra trocar "Eloquent local" por "cliente de API remota" sem mexer em quem consome.
- Nenhum módulo acessa tabela de outro módulo direto.
- Todo módulo `acme/*` interno do repo é pinado `^1.0.0` no `composer.json` de cada app — nunca o `*` solto que o gerador deixa por padrão.

Justificativa completa: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Tabela de nomenclatura de cada elemento acima: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Gerador

`make new-module name={nome}` encapsula o [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module`, configurado em `apps/backend/config/app-modules.php` pra usar `../../packages` e um namespace placeholder `Acme` — troque os dois pelo vendor/namespace real do seu projeto). Passo a passo completo do que construir na mão depois: [`.claude/skills/new-module/SKILL.md`](.claude/skills/new-module/SKILL.md), seguindo `packages/withdrawals/` como forma de referência.

## Filament

`apps/admin` tem três painéis funcionando — `admin`, `app`, `guest` — adaptados do [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) (MIT), com login, perfil, PWA e impersonate já plugados. Detalhes: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Ferramental

`make check`/`make format` rodam Pint, Rector, PHPStan (Larastan) e Pest nos dois apps de uma vez; PHPStan e Pest incluem automaticamente todo `packages/*`. O [`laravel/boost`](https://laravel.com/docs/boost) está instalado nos dois apps pra geração de guidelines de IA. Detalhes: [`.claude/rules/tooling.md`](.claude/rules/tooling.md).

## Roadmap

- [x] Um módulo de referência totalmente implementado (`packages/withdrawals`, espelhando o `auth` do `base_clean_arch_bloc`) que os módulos novos imitam, consumido por `apps/backend` e `apps/admin`.
- [x] `apps/admin` com três painéis Filament funcionando e um Resource de referência sobre o módulo compartilhado.
- [x] `.claude/skills/new-module/` encapsulando o `make:module` com as convenções deste template (Actions/Contracts/Adapters, stub de teste, wiring) — espelha o skill `new-feature` do `base_clean_arch_bloc`.
- [x] PHPStan (Larastan), Pint, Rector e um `Makefile` raiz orquestrando os dois apps; `laravel/boost` pra guidelines de IA.
- [ ] `apps/web`: um template de frontend separado, construído por conta própria, depois referenciado/colocado aqui como irmão de `apps/backend` e `apps/admin`.
