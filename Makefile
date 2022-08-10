.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMP   = docker compose
PHPUNIT       = ./vendor/bin/phpunit
DATABASE_USER = histologe
DATABASE_NAME = histologe_db
PATH_DUMP_SQL = data/dump.sql

help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
	| sed -n 's/^\(.*\): \(.*\)##\(.*\)/\1\3/p' \
	| column -t  -s ':' \


run: ## : Start containers
	@echo -e '\e[1;32mStart containers\032'
	@bash -l -c '$(DOCKER_COMP) up -d'
	@echo -e '\e[1;32mContainers runnung\032'

down: ## : Shutdown containers
	@echo -e '\e[1;32mStop containers\032'
	@bash -l -c '$(DOCKER_COMP) down'
	@echo -e '\e[1;32mContainers stopped\032'

sh: ## : Log to phpfpm container
	@echo -e '\e[1;32mLog to phpfpm container\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpfpm sh'


test: ##  : Run all tests
	@$(PHPUNIT) --stop-on-failure

create-db: ## : Create database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "php bin/console --env=dev doctrine:database:create --no-interaction"

drop-db: ## : Drop database
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "php bin/console --env=dev doctrine:database:drop --force --no-interaction"

load-data: ## : Drop database
	@$(DOCKER_COMP) exec -T histologe_mysql mysql -u $(DATABASE_USER) -phistologe $(DATABASE_NAME) < $(PATH_DUMP_SQL)
