postdeploy: ./scripts/postdeploy.sh
worker: php bin/console messenger:consume async_priority_high async --time-limit=3600
clock: php bin/console app:scheduled-task
