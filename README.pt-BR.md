# base_laravel_modular

[English version](README.md)

Um **template de monorepo** pra um monólito modular Laravel: `apps/backend` (API), `apps/admin` (Filament), `apps/web` (Nuxt), e um pacote Composer por módulo de negócio em `packages/*`, compartilhado entre `apps/backend` e `apps/admin`.

Este repositório é feito pra ser clonado/copiado como ponto de partida de um projeto novo, não pra crescer virando um produto em si — mesmo espírito do [`base_clean_arch_bloc`](../base_clean_arch_bloc), seu equivalente em Flutter.

> **Status:** estágio inicial, mas já roda. `apps/backend` e `apps/admin` são dois apps Laravel de verdade, os dois consumindo `packages/withdrawals` como módulo de referência totalmente implementado; `apps/admin` tem um painel Filament funcionando por cima. `apps/web` é só o esqueleto do Nuxt. Ainda falta: um skill `new-module` e uma página de verdade em `apps/web` chamando a API — ver a seção Roadmap.

```bash
cd apps/backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate
cd apps/admin   && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate
cd apps/web     && npm install

cd apps/backend && ./vendor/bin/pest   # suíte completa, incluindo todo packages/*/tests
```

> **Trabalhando com assistentes de IA**: este projeto tem `CLAUDE.md` e `.claude/rules/` pra que o Claude Code (ou qualquer assistente que leia `CLAUDE.md`) já conheça a arquitetura e as convenções de nomenclatura antes de gerar qualquer coisa.

## A ideia

Todo módulo de negócio é seu próprio pacote Composer em `packages/{modulo}/` — Models, Actions, DTOs, Enums, Events, migrations próprias, `ServiceProvider` próprio. Esse formato de pacote nunca muda, independente de quantos apps existem por cima dele — aqui, `apps/backend` (a API, dona de todo caminho de escrita) e `apps/admin` (painel interno em Filament) exigem o mesmo `packages/*` via path repository do Composer. O `apps/web` é só um cliente HTTP comum da API do `apps/backend` — nunca toca em `packages/*` nem em banco de dados.

Por que monorepo, por que três apps, e o que fazer se você não precisar dessa divisão: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

## Estrutura

```
apps/
  backend/                     # Laravel — a API, dona de todo caminho de escrita
  admin/                       # Laravel + Filament — painel(éis) interno(s)
    app/Providers/Filament/AdminPanelProvider.php
    app/Filament/Admin/Resources/WithdrawalResource.php   # importa Acme\Withdrawals\Models\Withdrawal
  web/                          # Nuxt — frontend público, fala com a API do apps/backend só por HTTP
packages/
  withdrawals/                  # módulo de referência
    composer.json               # type: library, vendor/nome próprio
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
    tests/
```

## Regras principais

- Todo módulo é um pacote Composer de verdade, nunca uma pasta solta colada dentro de um app.
- Qualquer escrita num Model com regra de negócio por trás passa pela `Actions/` daquele módulo — nunca um `->save()`/`->update()` cru vindo de fora do pacote. É isso que evita `apps/backend` e `apps/admin` (ou, depois, um microsserviço extraído) divergirem na mesma regra.
- Módulos com chance real de virar serviço próprio depois (pagamentos, uma pipeline de moderação) ganham um par `Contract` + `Adapter` desde o início, pra trocar "Eloquent local" por "cliente de API remota" sem mexer em quem consome.
- Nenhum módulo acessa tabela de outro módulo direto.

Justificativa completa: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Tabela de nomenclatura de cada elemento acima: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Gerador

A geração de módulo roda em cima do [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan make:module {nome}`, a partir de `apps/backend`) em vez de boilerplate feito na mão, configurado em `apps/backend/config/app-modules.php` pra usar `../../packages` e um namespace placeholder `Acme` — troque os dois pelo vendor/namespace real do seu projeto. O esqueleto gerado é preenchido na mão, seguindo `packages/withdrawals/` como forma de referência.

## Filament

`apps/admin` tem um painel `admin` funcionando — detalhes e a técnica de multi-painel/multi-guard: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Roadmap

- [x] Um módulo de referência totalmente implementado (`packages/withdrawals`, espelhando o `auth` do `base_clean_arch_bloc`) que os módulos novos imitam, consumido por `apps/backend` e `apps/admin`.
- [x] `apps/admin` com painel Filament funcionando e um Resource de referência sobre o módulo compartilhado.
- [ ] `.claude/skills/new-module/` encapsulando o `make:module` com as convenções deste template (Actions/Contracts/Adapters, stub de teste, wiring) — espelha o skill `new-feature` do `base_clean_arch_bloc`.
- [ ] Uma página de verdade em `apps/web` chamando a API do `apps/backend` (hoje é só o esqueleto do Nuxt).
