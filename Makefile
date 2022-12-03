.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMP   = docker compose
DATABASE_USER = histologe
DATABASE_NAME = histologe_db
PATH_DUMP_SQL = data/dump.sql
PHPUNIT       = ./vendor/bin/phpunit
SYMFONY       = php bin/console
NPX           = npx

help:
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

build: ## Install local environement
	@bash -l -c 'make .check .env .destroy .setup run .sleep composer create-db'

run: ## Start containers
	@echo -e '\e[1;32mStart containers\032'
	@bash -l -c '$(DOCKER_COMP) up -d'
	@echo -e '\e[1;32mContainers running\032'

down: ## Shutdown containers
	@echo -e '\e[1;32mStop containers\032'
	@bash -l -c '$(DOCKER_COMP) down'
	@echo -e '\e[1;32mContainers stopped\032'

sh: ## Log to phpfpm container
	@echo -e '\e[1;32mLog to phpfpm container\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpfpm sh'

mysql: ## Log to mysql container
	@echo -e '\e[1;32mLog to mysql container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_mysql mysql -u histologe -phistologe histologe_db'

logs: ## Show container logs
	@$(DOCKER_COMP) logs --follow

console: ## Execute application command
	@echo $(SYMFONY) app:$(app)
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(SYMFONY) app:$(app)

composer: ## Install composer dependencies
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --dev --no-interaction --optimize-autoloader
	@echo "\033[33mInstall tools dependencies ...\033[0m"
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --working-dir=tools/php-cs-fixer --dev --no-interaction --optimize-autoloader

clear-cache: ## Clear cache prod: make-clear-cache env=[dev|prod|test]
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(SYMFONY) c:c --env=$(env)

cc: clear-cache

create-db: ## Create database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:create --no-interaction"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:fixtures:load --no-interaction"

drop-db: ## Drop database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction"

load-data: ## Load database from dump
	@$(DOCKER_COMP) exec -T histologe_mysql mysql -u $(DATABASE_USER) -phistologe $(DATABASE_NAME) < $(PATH_DUMP_SQL)

migration: ## Generate migration
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev make:migration --no-interaction"

generate-migration: ## Generate empty migration
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:generate"

load-migrations: ## Play migrations
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"

load-fixtures: ## Load database from fixtures
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:fixtures:load --no-interaction"

create-db-test: ## Create test database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=test doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=test doctrine:database:create --no-interaction"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=test doctrine:migrations:migrate --no-interaction"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=test doctrine:fixtures:load --no-interaction"


test: ## Run all tests
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(PHPUNIT) --stop-on-failure --testdox -d memory_limit=-1"

test-coverage: ## Generate phpunit coverage report in html
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html coverage"

e2e: ## Run E2E tests
	@$(NPX) cypress open


stan: ## Run PHPStan
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer stan

cs-check: ## Check source code with PHP-CS-Fixer
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer cs-check

cs-fix: ## Fix source code with PHP-CS-Fixer
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer cs-fix

mock: ## Start Mock server
	@${DOCKER_COMP} exec -it histologe_phpfpm sh -c "cd wiremock/src/Mock && php AppMock.php"

.env:
	@bash -l -c 'cp .env.sample .env'

.check:
	@echo "\033[31mWARNING!!!\033[0m Executing this script will reinitialize the project and all of its data"
	@( read -p "Are you sure you wish to continue? [y/N]: " sure && case "$$sure" in [yY]) true;; *) false;; esac )

.destroy:
	@echo "\033[33mRemoving containers ...\033[0m"
	@$(DOCKER_COMP) rm -v --force --stop || true
	@echo "\033[32mContainers removed!\033[0m"

.setup:
	@echo "\033[33mBuilding containers ...\033[0m"
	@$(DOCKER_COMP) build
	@echo "\033[32mContainers built!\033[0m"

.sleep:
	@sleep 30
