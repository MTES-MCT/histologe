.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMP   = docker compose
DATABASE_USER = histologe
DATABASE_NAME = histologe_db
PATH_DUMP_SQL = data/dump.sql
PHPUNIT       = ./vendor/bin/phpunit
SYMFONY       = php bin/console
NPX           = npx
NPM           = npm

help:
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

build: ## Install local environement
	@bash -l -c 'make .check .env .destroy .setup run .sleep composer create-db npm-install npm-build mock'

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

worker: ## Log to php-worker container
	@echo -e '\e[1;32mLog to phpworker container\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpworker sh'

mysql: ## Log to mysql container
	@echo -e '\e[1;32mLog to mysql container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_mysql mysql -u histologe -phistologe histologe_db'

redis: ## Log to redis container
	@echo -e '\e[1;32mLog to redis container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_redis sh'

redis-cli: ## Log to redis-cli
	@echo -e '\e[1;32mLog to redis-cli\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_redis redis-cli'

redis-stat: ## Collect stat redis
	@echo -e '\e[1;32mCollect stat-redis\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_redis redis-cli --stat'

worker-status:## Get status worker
	@echo -e '\e[1;32mGet status worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpworker supervisorctl status all'

worker-start: ## Start worker
	@echo -e '\e[1;32mStart worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpworker supervisorctl start all'

worker-stop: ## Stop worker
	@echo -e '\e[1;32mStop worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpworker supervisorctl stop all'

worker-exec-failed: ## Consume failed queue
	@echo -e '\e[1;32mConsume failed queue\032'
	@bash -l -c '$(DOCKER_COMP) exec -it histologe_phpworker php bin/console messenger:consume failed -vvv'

logs: ## Show container logs
	@$(DOCKER_COMP) logs --follow

console: ## Execute application command
	@echo $(SYMFONY) app:$(app)
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(SYMFONY) app:$(app)

composer: ## Install composer dependencies
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --no-interaction --optimize-autoloader
	@echo "\033[33mInstall tools dependencies ...\033[0m"
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --working-dir=tools/php-cs-fixer  --no-interaction --optimize-autoloader
	@$(DOCKER_COMP) exec -it histologe_phpfpm composer install --working-dir=tools/wiremock  --no-interaction --optimize-autoloader

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
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:create --no-interaction"
	@$(DOCKER_COMP) exec -T histologe_mysql mysql -u $(DATABASE_USER) -phistologe $(DATABASE_NAME) < $(PATH_DUMP_SQL)
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"

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
	@$(DOCKER_COMP) exec histologe_phpfpm sh -c "$(PHPUNIT) $(FILE) --stop-on-failure --testdox -d memory_limit=-1"

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
	@${DOCKER_COMP} start histologe_wiremock && sleep 5
	@${DOCKER_COMP} exec -it histologe_phpfpm sh -c "cd tools/wiremock/src/Mock && php AppMock.php"

stop-mock: ## Stop Mock server
	@${DOCKER_COMP} stop histologe_wiremock

npm-install: ## Install the dependencies in the local node_modules folder
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(NPM) install

npm-build: ## Build the dependencies in the local node_modules folder
	@$(DOCKER_COMP) exec -it histologe_phpfpm $(NPM) run build

upload: ## Push objects to S3 Bucket
	./scripts/upload-s3.sh $(action) $(zip) $(debug)

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
