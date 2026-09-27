# base_laravel_modular

[English version](README.md)

Um **template** Laravel para monólito modular: um pacote Composer por módulo de negócio, compartilhável entre um ou vários apps Laravel dependendo do formato de projeto escolhido.

Este repositório é feito pra ser clonado/copiado como ponto de partida de um projeto novo, não pra crescer virando um produto em si — mesmo espírito do [`base_clean_arch_bloc`](../base_clean_arch_bloc), seu equivalente em Flutter.

> **Status:** estágio inicial. O que existe hoje é a documentação de arquitetura e as convenções (`CLAUDE.md`, `.claude/rules/`). Ainda não tem app Laravel de verdade rodando, gerador de módulo automatizado nem wizard de criação de projeto — ver a seção Roadmap.

> **Trabalhando com assistentes de IA**: este projeto tem `CLAUDE.md` e `.claude/rules/` pra que o Claude Code (ou qualquer assistente que leia `CLAUDE.md`) já conheça a arquitetura e as convenções de nomenclatura antes de gerar qualquer coisa.

## A ideia

Todo módulo de negócio é seu próprio pacote Composer em `packages/{modulo}/` — Models, Actions, DTOs, Enums, Events, migrations próprias, `ServiceProvider` próprio. Esse formato de pacote nunca muda. O que muda por projeto é quantos apps Laravel existem por cima dele:

- **single-project** — um app Laravel só; um painel admin (ex.: Filament) é só mais um pacote rodando no mesmo processo.
- **monorepo-split** — um repositório Git, vários apps Laravel em `apps/*` (ex.: `apps/backend` pra API, `apps/admin` pra um projeto Filament separado), todos exigindo o mesmo `packages/*`.

Comparação completa e quando escolher cada um: [`.claude/rules/project-shape.md`](.claude/rules/project-shape.md).

## Estrutura

```
packages/
  {modulo}/
    composer.json            # type: library, vendor/nome próprio
    src/
      Models/
      Actions/                # único lugar autorizado a mutar um Model com regra de negócio por trás
      DTOs/
      Enums/
      Events/
      Contracts/              # interface que a Action usa, pros módulos candidatos a virar serviço depois
      Adapters/                 # implementação concreta de um Contract (local hoje, remota depois)
      {Modulo}ServiceProvider.php
    database/
      migrations/
      factories/
    tests/
apps/                          # só existe no formato monorepo-split
  backend/
  admin/
```

## Regras principais

- Todo módulo é um pacote Composer de verdade, nunca uma pasta solta colada dentro de um app Laravel.
- Qualquer escrita num Model com regra de negócio por trás passa pela `Actions/` daquele módulo — nunca um `->save()`/`->update()` cru vindo de fora do pacote. É isso que evita dois apps (ou, depois, dois microsserviços) divergirem na mesma regra.
- Módulos com chance real de virar serviço próprio depois (pagamentos, uma pipeline de moderação) ganham um par `Contract` + `Adapter` desde o início, pra trocar "Eloquent local" por "cliente de API remota" sem mexer em quem consome.
- Nenhum módulo acessa tabela de outro módulo direto.

Justificativa completa: [`.claude/rules/architecture.md`](.claude/rules/architecture.md). Tabela de nomenclatura de cada elemento acima: [`.claude/rules/naming-conventions.md`](.claude/rules/naming-conventions.md).

## Gerador

A geração de módulo é pensada pra rodar em cima do [`internachi/modular`](https://github.com/InterNACHI/modular) (`php artisan module:make {nome}`) em vez de boilerplate feito na mão. Ainda não está plugado neste template.

## Filament

O app que hospeda o Filament usa o [`jeffersongoncalves/filakitv5`](https://github.com/jeffersongoncalves/filakitv5) como base — um starter kit Laravel 13 + Filament 5 com multi-painel e multi-guard já resolvidos. Detalhes: [`.claude/rules/filament-panels.md`](.claude/rules/filament-panels.md).

## Roadmap

- [ ] `.claude/skills/new-module/` encapsulando o `module:make` com as convenções deste template (Actions/Contracts/Adapters, stub de teste, wiring) — espelha o skill `new-feature` do `base_clean_arch_bloc`.
- [ ] Wizard de `composer create-project` (`post-create-project-cmd`, via `laravel/prompts`) perguntando single-project vs monorepo-split na criação e reorganizando a árvore de arquivos de acordo.
- [ ] Um módulo de referência totalmente implementado (espelhando o `auth` do `base_clean_arch_bloc`) que os módulos novos imitam.
