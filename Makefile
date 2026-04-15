# HOSTO — Developer commands
# ============================
# Run `make help` to list the available targets.

.DEFAULT_GOAL := help
.PHONY: help install setup serve stop fresh seed test test-arch test-unit test-feature \
        lint lint-fix stan check ci clean tinker routes db-shell redis-shell \
        migrate migrate-fresh migrate-status

# --- Configuration ----------------------------------------------------------

PHP        ?= php
COMPOSER   ?= composer
NPM        ?= npm
ARTISAN    := $(PHP) artisan
PORT       ?= 8010

# Colors for help output
GREEN := \033[0;32m
BLUE  := \033[0;34m
RESET := \033[0m

# --- Help ------------------------------------------------------------------

help: ## Show this help message
	@printf "$(BLUE)HOSTO — Developer commands$(RESET)\n\n"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# --- Setup -----------------------------------------------------------------

install: ## Install PHP and JS dependencies
	$(COMPOSER) install
	$(NPM) install

setup: install ## First-time project setup (install + .env + key + migrate)
	@if [ ! -f .env ]; then cp .env.example .env && echo "Created .env"; fi
	$(ARTISAN) key:generate --ansi
	$(ARTISAN) migrate --graceful --ansi

# --- Server ----------------------------------------------------------------

serve: ## Start the local dev server (port $(PORT))
	$(ARTISAN) serve --port=$(PORT)

stop: ## Stop a dev server running on $(PORT)
	-@kill $$(lsof -ti :$(PORT)) 2>/dev/null && echo "Stopped server on port $(PORT)" || echo "No server on port $(PORT)"

# --- Database --------------------------------------------------------------

migrate: ## Run pending migrations
	$(ARTISAN) migrate

migrate-status: ## Show migration status
	$(ARTISAN) migrate:status

migrate-fresh: ## Drop all tables and re-run migrations (DEV ONLY)
	$(ARTISAN) migrate:fresh

fresh: migrate-fresh ## Alias of migrate-fresh

seed: ## Run database seeders
	$(ARTISAN) db:seed

# --- Quality ---------------------------------------------------------------

lint: ## Check code style with Pint (no fix)
	./vendor/bin/pint --test

lint-fix: ## Auto-fix code style with Pint
	./vendor/bin/pint

stan: ## Run static analysis with PHPStan
	./vendor/bin/phpstan analyse --memory-limit=512M

# --- Tests -----------------------------------------------------------------

test: ## Run the full test suite
	$(ARTISAN) test

test-unit: ## Run only unit tests
	$(ARTISAN) test --testsuite=Unit

test-feature: ## Run only feature tests
	$(ARTISAN) test --testsuite=Feature

test-arch: ## Run only architecture tests
	$(ARTISAN) test --testsuite=Architecture

# --- CI bundle -------------------------------------------------------------

check: lint stan test ## Run lint + static analysis + tests (pre-commit)

ci: check ## CI entry point (alias of check)

# --- Utilities -------------------------------------------------------------

routes: ## List API routes
	$(ARTISAN) route:list --path=api

tinker: ## Open a Laravel REPL
	$(ARTISAN) tinker

db-shell: ## Open a psql shell on the dev database
	@/opt/homebrew/opt/postgresql@17/bin/psql hosto -U hosto

redis-shell: ## Open a redis-cli shell
	@redis-cli

clean: ## Clear caches
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear
