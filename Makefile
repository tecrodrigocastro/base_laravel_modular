.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-24s\033[0m %s\n", $$1, $$2}'

# --- setup -------------------------------------------------------------

.PHONY: install
install: ## composer/npm install for both apps
	cd apps/backend && composer install
	cd apps/admin && composer install && npm install

.PHONY: fresh
fresh: ## migrate:fresh in both apps (drops all tables)
	cd apps/backend && php artisan migrate:fresh
	cd apps/admin && php artisan migrate:fresh

# --- quality (each app owns its own vendor/bin/*) -----------------------

.PHONY: pint
pint: ## Run Pint on both apps
	cd apps/backend && ./vendor/bin/pint --parallel
	cd apps/admin && ./vendor/bin/pint --parallel

.PHONY: test-pint
test-pint: ## Run Pint in test mode on both apps
	cd apps/backend && ./vendor/bin/pint --parallel --test
	cd apps/admin && ./vendor/bin/pint --parallel --test

.PHONY: rector
rector: ## Run Rector on both apps
	cd apps/backend && ./vendor/bin/rector process
	cd apps/admin && ./vendor/bin/rector process

.PHONY: test-rector
test-rector: ## Run Rector in dry-run mode on both apps
	cd apps/backend && ./vendor/bin/rector process --dry-run
	cd apps/admin && ./vendor/bin/rector process --dry-run

.PHONY: phpstan
phpstan: ## Run PHPStan on both apps (backend's run also covers packages/*)
	cd apps/backend && ./vendor/bin/phpstan analyse --ansi --memory-limit=2G
	cd apps/admin && ./vendor/bin/phpstan analyse --ansi --memory-limit=2G

.PHONY: p
p: phpstan ## Alias for phpstan

.PHONY: format
format: rector pint ## Run Rector and Pint, fixing what they can

.PHONY: f
f: format ## Alias for format

.PHONY: check
check: test-rector test-pint phpstan test ## Everything format checks + PHPStan + tests, no fixing

# --- tests ---------------------------------------------------------------

.PHONY: test
test: ## Run backend + admin test suites (backend's run also covers packages/*/tests)
	cd apps/backend && ./vendor/bin/pest --compact
	cd apps/admin && ./vendor/bin/pest --compact

.PHONY: t
t: test ## Alias for test

.PHONY: test-backend
test-backend: ## Run only apps/backend's suite (includes packages/*/tests)
	cd apps/backend && ./vendor/bin/pest --compact

.PHONY: test-admin
test-admin: ## Run only apps/admin's suite
	cd apps/admin && ./vendor/bin/pest --compact

# --- modules ---------------------------------------------------------------

.PHONY: new-module
new-module: ## Scaffold a new packages/* module: make new-module name=produtos
	cd apps/backend && php artisan make:module $(name)

.PHONY: modules-list
modules-list: ## List modules registered in apps/backend
	cd apps/backend && php artisan modules:list
