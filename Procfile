postdeploy: ./scripts/postdeploy.sh
worker: php bin/console messenger:consume async
clock: php bin/console app:scheduled-task
