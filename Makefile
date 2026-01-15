.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMP   = docker compose
DOCKER_COMP_FILE_TOOLS   = docker-compose.tools.yml
DATABASE_USER = signal_logement
DATABASE_NAME = signal_logement_db
PATH_DUMP_SQL = data/dump.sql
PHPUNIT       = ./vendor/bin/phpunit
SYMFONY       = php bin/console
NPX           = npx
NPM           = npm
METABASE_SYNC_LOCAL_MODE = 1
METABASE_SYNC_IMAGE_NAME     = scalingo-sync-silo-metabase
METABASE_SYNC_SCALINGO_APP = histologe-preprod
METABASE_SYNC_IMAGE_LOCAL    = $(METABASE_SYNC_IMAGE_NAME):local
METABASE_SYNC_SCALINGO_JOB_DOCKERFILE = .docker/scalingo-job/Dockerfile
OVH_SCW_SYNC_DOCKERFILE=.docker/rclone-job/Dockerfile
OVH_SCW_SYNC_IMAGE_NAME=rclone-sync-ovh-scaleway
SCW_REGISTRY   = rg.fr-par.scw.cloud
SCW_NAMESPACE  = signal-logement

help:
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## Service management
build: ## Install local environment (use SKIP_NPM_BUILD=1 to skip npm-build step)
	@if [ "$(SKIP_NPM_BUILD)" = "1" ]; then \
		echo "Skipping npm-build step"; \
		bash -l -c 'make .check .env .destroy .setup run .sleep composer create-db create-db-test npm-ci mock-stop mock-start'; \
	else \
		bash -l -c 'make .check .env .destroy .setup run .sleep composer create-db create-db-test npm-ci npm-build mock-stop mock-start'; \
	fi
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
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpfpm sh'

worker: ## Log to php-worker container
	@echo -e '\e[1;32mLog to phpworker container\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker sh'

mysql: ## Log to mysql container
	@echo -e '\e[1;32mLog to mysql container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_mysql mysql -u signal_logement -psignal_logement signal_logement_db'

redis: ## Log to redis container
	@echo -e '\e[1;32mLog to redis container\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_redis sh'

redis-cli: ## Log to redis-cli
	@echo -e '\e[1;32mLog to redis-cli\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_redis redis-cli'

redis-stat: ## Collect stat redis
	@echo -e '\e[1;32mCollect stat-redis\032[0m'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_redis redis-cli --stat'

worker-status:## Get status worker
	@echo -e '\e[1;32mGet status worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker supervisorctl status all'

worker-start: ## Start worker
	@echo -e '\e[1;32mStart worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker supervisorctl start all'

worker-stop: ## Stop worker
	@echo -e '\e[1;32mStop worker\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker supervisorctl stop all'

worker-exec-failed: ## Consume failed queue
	@echo -e '\e[1;32mConsume failed queue\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker php bin/console messenger:consume failed -vvv'

worker-consume: ## Consume local queue for debug
	@echo -e '\e[1;32mConsume queue\032'
	@bash -l -c '$(DOCKER_COMP) exec -it signal_logement_phpworker php bin/console messenger:consume async_priority_high async -vvv'

mock-start: ## Start Mock server
	@${DOCKER_COMP} start signal_logement_wiremock && sleep 5
	@${DOCKER_COMP} exec -it signal_logement_phpfpm sh -c "cd tools/wiremock/src/Mock && php AppMock.php"

mock-stop: ## Stop Mock server
	@${DOCKER_COMP} stop signal_logement_wiremock

mock-restart: mock-stop mock-start ## Restart Mock server
	@echo "\033[32m✅ Wiremock restarted successfully.\033[0m"

logs: ## Show container logs
	@$(DOCKER_COMP) logs --follow

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

## Database
create-db: ## Create database
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:create --no-interaction"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev messenger:setup-transports --no-interaction"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:fixtures:load --no-interaction"

create-db-test: ## Create test database
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=test doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=test doctrine:database:create --no-interaction"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=test doctrine:migrations:migrate --no-interaction"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=test doctrine:fixtures:load --no-interaction"

drop-db: ## Drop database
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction"

load-data: ## Load database from dump
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:drop --force --no-interaction || true"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:database:create --no-interaction"
	@pv $(PATH_DUMP_SQL) | $(DOCKER_COMP) exec -T signal_logement_mysql mysql -u $(DATABASE_USER) -psignal_logement $(DATABASE_NAME)
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"

migration: ## Generate migration
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev make:migration --no-interaction"

generate-migration: ## Generate empty migration
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:generate"

load-migrations: ## Play migrations
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:migrate --no-interaction"

execute-migration: ## Execute migration: make execute-migration name=Version20231027135554 direction=up
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:migrations:execute DoctrineMigrations\\\$(name) --$(direction) --no-interaction"

load-fixtures: ## Load database from fixtures
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(SYMFONY) --env=dev doctrine:fixtures:load --no-interaction"

## Executable
composer: ## Install composer dependencies
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer install --no-interaction --optimize-autoloader
	@echo "\033[33mInstall tools dependencies ...\033[0m"
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer install --working-dir=tools/php-cs-fixer  --no-interaction --optimize-autoloader
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer install --working-dir=tools/wiremock  --no-interaction --optimize-autoloader

require: ## Symfony require
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer require $(ARGS)

update: ## Symfony require
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer update $(ARGS)

npm-ci: ## Install the dependencies in the local node_modules folder
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(NPM) ci

npm-build: ## Build the dependencies in the local node_modules folder
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(NPM) run build

npm-watch: ## Watch files for changes
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(NPM) run watch

clear-cache: ## Clear cache prod: make-clear-cache env=[dev|prod|test]
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(SYMFONY) c:c --env=$(env)

npm-newman-install: ## Install newman CLI
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm sh -c 'cd tools/newman && $(NPM) ci'

cc: clear-cache

clear-pool: ## Clear cache pool: make clear-pool pool=[pool_name]
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(SYMFONY) cache:pool:clear $(pool)

console: ## Execute application command
	@echo $(SYMFONY) app:$(app)
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(SYMFONY) app:$(app)

symfony: ## Execute symfony command: make symfony cmd="make:entity Signalement"
	@echo $(SYMFONY) $(cmd)
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm $(SYMFONY) $(cmd)

upload: ## Push objects to S3 Bucket
	./scripts/upload-s3.sh $(action) $(zip) $(debug)

sync-sish: mock-restart ## Synchronize SISH status and intervention (use make sync-sish tiers=1 to update profile_declarant)
	@if [ "$(tiers)" = "1" ]; then \
		echo "\033[33mUpdating profile_declarant to 'TIERS_PRO' for reference 2023-12 and 2023-10...\033[0m"; \
		$(DOCKER_COMP) exec -T signal_logement_phpfpm \
			php bin/console doctrine:query:sql \
			"UPDATE signalement SET profile_declarant = 'TIERS_PRO' WHERE reference IN ('2023-12','2023-10');"; \
		echo "\033[32m✅ Update completed successfully.\033[0m"; \
	fi
	@echo "\033[33mSynchronizing SISH...\033[0m"
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh ./scripts/sync-esabora-sish.sh
	@echo "\033[32m✅ SISH synchronization completed.\033[0m"

clean-tasks: ## Clean & Reset task
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh ./scripts/clean.sh

## Quality
test: ## Run all tests
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(PHPUNIT) $(FILE) --stop-on-failure --testdox -d memory_limit=-1"

test-all: ## Run all tests
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "$(PHPUNIT) $(FILE) --testdox -d memory_limit=-1"

test-coverage: ## Generate phpunit coverage report in html
	@$(DOCKER_COMP) exec signal_logement_phpfpm sh -c "XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html coverage -d memory_limit=-1"

test-e2e: ## Run E2E tests
	@cd tests/e2e/ ; $(NPX) playwright test

test-e2e-ui: ## Run E2E tests
	@cd tests/e2e/ ; $(NPX) playwright test --ui

create-test-e2e: ## Open Playwright
	@cd tests/e2e/ ; $(NPX) playwright codegen

playwright-install: ## Open Playwright
	@cd tests/e2e/ ; $(NPX) playwright install

stan: ## Run PHPStan
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer stan $(FILE) 

cs-check: ## Check source code with PHP-CS-Fixer
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer cs-check

cs-fix: ## Fix source code with PHP-CS-Fixer
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm composer cs-fix

es-vue-fix: ## Fix vue source code with es-lint --fix
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm npm run es-vue-fix

es-js-fix: ## Fix vanilla js source code with es-lint --fix
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm npm run es-js-fix
	@$(DOCKER_COMP) exec -it signal_logement_phpfpm npm run es-js-prettier-fix

## Tools
tools-build: ## [Tools] Install tools (Matomo, ...) local environement
	@bash -l -c 'make .check .tools-destroy .tools-setup tools-run'

tools-run: ## [Tools] Start tools containers
	@echo -e '\e[1;32mStart tools containers\032'
	@bash -l -c '$(DOCKER_COMP) -f $(DOCKER_COMP_FILE_TOOLS) up -d'
	@echo -e '\e[1;32mContainers tools running\032'

tools-down: ## [Tools] Shutdown tools containers
	@echo -e '\e[1;32mStop tools containers\032'
	@bash -l -c '$(DOCKER_COMP) -f $(DOCKER_COMP_FILE_TOOLS) down'
	@echo -e '\e[1;32mContainers tools stopped\032'

tools-logs: ## [Tools] Show container-tools logs
	@$(DOCKER_COMP) -f $(DOCKER_COMP_FILE_TOOLS) logs --follow

matomo-disable-ssl: ## Disable ssl use for matomo local instance
	@docker exec -it signal_logement-matomo_app-1 sh /var/www/html/update-config-ini.sh

scalingo-update-cli: ## Install/Update Scalingo CLI
	@bash -l -c 'curl -O https://cli-dl.scalingo.com/install && bash install && scalingo --version'

run-concurrency-request: ## Run concurrency request based postman collection ex: make run-concurrency-request nb=5 envName=local|demo
	@bash -l -c 'node ./tools/newman/run_concurrency_request.js nb=$(nb) envName=$(envName)'

## Job sync metabase
scalingo-job-build: ## Build Scalingo sync job container
	@echo "\033[33mBuilding Scalingo job image...\033[0m"
	docker build \
		-f $(METABASE_SYNC_SCALINGO_JOB_DOCKERFILE) \
		-t $(METABASE_SYNC_IMAGE_NAME):local \
		.
	@echo "\033[32m✅ Image built: $(METABASE_SYNC_IMAGE_NAME):local\033[0m"
	@echo "\n\033[36m💡 Vous pouvez tester l'image localement en exécutant : make scalingo-job-run\033[0m"
	@echo "\033[33m⚠️  Note : Assurez-vous d'avoir ajouté la variable d'envionnement export SCALINGO_API_TOKEN=xxxxxxxxxxxxxx"
	@echo "   Pour générez un token API : https://dashboard.scalingo.com/account/tokens\033[0m"
	@echo "\n\033[31m/!\ ATTENTION : Vérifiez bien le dernier tag distant avant de pousser /!\ \033[0m"
	@echo "\033[33mTags actuellement présents sur le registry distant https://console.scaleway.com/registry \033[0m"
	@echo "\n\033[35m🚀 RELEASE : Pour pousser l'image, exécutez :"
	@echo "   make scalingo-job-release IMAGE_VERSION=999.999\033[0m"

scalingo-job-run: ## Run Scalingo sync job locally
	@echo "\033[33mRunning Scalingo job locally...\033[0m"
	docker run --rm \
		-e SCALINGO_API_TOKEN=$(METABASE_SYNC_SCALINGO_API_TOKEN) \
		-e METABASE_SYNC_SCALINGO_APP=$(METABASE_SYNC_SCALINGO_APP) \
		-e METABASE_SYNC_LOCAL_MODE=0 \
		$(METABASE_SYNC_IMAGE_NAME):local

scalingo-job-tag: ## Tag the local image with a version for Scaleway registry - make scalingo-job-tag IMAGE_VERSION=1.0.x
	@echo "Tagging image $(METABASE_SYNC_IMAGE_LOCAL) as $(IMAGE_VERSION)"
	docker tag $(METABASE_SYNC_IMAGE_LOCAL) \
		$(SCW_REGISTRY)/$(SCW_NAMESPACE)/$(METABASE_SYNC_IMAGE_NAME):$(IMAGE_VERSION)

scalingo-job-push: ## Push the versioned image to Scaleway registry - make scalingo-job-push IMAGE_VERSION=1.0.x
	@echo "Pushing image version $(IMAGE_VERSION) to Scaleway registry"
	docker push \
		$(SCW_REGISTRY)/$(SCW_NAMESPACE)/$(METABASE_SYNC_IMAGE_NAME):$(IMAGE_VERSION)

scalingo-job-release: scalingo-job-tag scalingo-job-push ## Tag and push image to Scaleway - make scalingo-job-release IMAGE_VERSION=1.0.x

## Job sync s3
ovh-scw-sync-build: ## Build rclone OVH -> Scaleway sync job image
	@echo "\033[33mBuilding rclone sync job image...\033[0m"
	docker build \
		-f $(OVH_SCW_SYNC_DOCKERFILE) \
		-t $(OVH_SCW_SYNC_IMAGE_NAME):local \
		.
	@echo "\033[32m✅ Image built: $(OVH_SCW_SYNC_IMAGE_NAME):local\033[0m"


ovh-scw-sync-run: ## Run rclone sync job locally in LOCAL MODE (quick check)
	@echo "\033[33mRunning rclone sync job locally (LOCAL MODE)...\033[0m"
	docker run --rm --network=histologe_default \
		-e RCLONE_MAX_DURATION="$(RCLONE_MAX_DURATION)" \
		-e SIGNAL_LOGEMENT_PROD_URL="$(SIGNAL_LOGEMENT_PROD_URL)" \
		-e SEND_ERROR_EMAIL_TOKEN="$(SEND_ERROR_EMAIL_TOKEN)" \
		-e RCLONE_SRC="$(OVH_SCW_SYNC_RCLONE_SRC)" \
		-e RCLONE_DST="$(OVH_SCW_SYNC_RCLONE_DST)" \
		-e RCLONE_CONFIG_OVH_S3_ACCESS_KEY_ID="$(OVH_SCW_SYNC_OVH_ACCESS_KEY_ID)" \
		-e RCLONE_CONFIG_OVH_S3_SECRET_ACCESS_KEY="$(OVH_SCW_SYNC_OVH_SECRET_ACCESS_KEY)" \
		-e RCLONE_CONFIG_OVH_S3_ENDPOINT="$(OVH_SCW_SYNC_OVH_ENDPOINT)" \
		-e RCLONE_CONFIG_SCALEWAY_S3_ACCESS_KEY_ID="$(OVH_SCW_SYNC_SCW_ACCESS_KEY_ID)" \
		-e RCLONE_CONFIG_SCALEWAY_S3_SECRET_ACCESS_KEY="$(OVH_SCW_SYNC_SCW_SECRET_ACCESS_KEY)" \
		-e RCLONE_CONFIG_SCALEWAY_S3_ENDPOINT="$(OVH_SCW_SYNC_SCW_ENDPOINT)" \
		$(OVH_SCW_SYNC_IMAGE_NAME):local

ovh-scw-sync-release: ## Tag and push image to Scaleway registry - make ovh-scw-sync-release IMAGE_VERSION=1.0.x
	@echo "\033[33mTagging & pushing rclone sync image...\033[0m"
	@: "$${IMAGE_VERSION:?Missing IMAGE_VERSION (ex: 1.0.0)}"
	docker tag $(OVH_SCW_SYNC_IMAGE_NAME):local \
		$(SCW_REGISTRY)/$(SCW_NAMESPACE)/$(OVH_SCW_SYNC_IMAGE_NAME):$${IMAGE_VERSION}
	docker push \
		$(SCW_REGISTRY)/$(SCW_NAMESPACE)/$(OVH_SCW_SYNC_IMAGE_NAME):$${IMAGE_VERSION}
	@echo "\033[32m✅ Pushed: $(SCW_REGISTRY)/$(SCW_NAMESPACE)/$(OVH_SCW_SYNC_IMAGE_NAME):$${IMAGE_VERSION}\033[0m"

.tools-destroy:
	@echo "\033[33mRemoving tools containers ...\033[0m"
	@$(DOCKER_COMP) -f $(DOCKER_COMP_FILE_TOOLS) rm -v --force --stop || true
	@echo "\033[32mContainers removed!\033[0m"

.tools-setup:
	@echo "\033[33mBuilding tools containers ...\033[0m"
	@$(DOCKER_COMP) -f $(DOCKER_COMP_FILE_TOOLS) build
	@echo "\033[32mContainers built!\033[0m"

.sleep:
	@sleep 30
