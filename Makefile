.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMP   = docker-compose
DATABASE_USER = histologe
DATABASE_NAME = histologe_db
PATH_DUMP_SQL = data/dump.sql
PHPSTAN       = ./vendor/bin/phpstan
PHPUNIT       = ./vendor/bin/phpunit

help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
	| sed -n 's/^\(.*\): \(.*\)##\(.*\)/\1\3/p' \
	| column -t  -s ':' \

build: .check .destroy .setup run .sleep composer load-data

run: ## : Start containers
	@echo -e '\e[1;32mStart containers\032'
	@bash -l -c '$(DOCKER_COMP) up -d'
	@echo -e '\e[1;32mContainers running\032'

down: ## : Shutdown containers
	@echo -e '\e[1;32mStop containers\032'
	@bash -l -c '$(DOCKER_COMP) down'
	@echo -e '\e[1;32mContainers stopped\032'

sh: ## : Log to phpfpm container
	@echo -e '\e[1;32mLog to phpfpm container\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpfpm sh'

mysql: ## : Log to mysql container
	@echo -e '\e[1;32mLog to mysql container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_mysql mysql -u histologe -phistologe histologe_db'

create-db: ## : Create database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "php bin/console --env=dev doctrine:database:create --no-interaction"

drop-db: ## : Drop database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "php bin/console --env=dev doctrine:database:drop --force --no-interaction"

load-data: ## : Drop database
	@$(DOCKER_COMP) exec -T histologe_mysql mysql -u $(DATABASE_USER) -phistologe $(DATABASE_NAME) < $(PATH_DUMP_SQL)

composer: ## : Install composer dependencies
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --dev --no-interaction --optimize-autoloader

## Tests

test: ##  : Run all tests
	@$(PHPUNIT) --stop-on-failure

## Coding standards

stan: ## : Run PHPStan
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(PHPSTAN) analyse --memory-limit 1G

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