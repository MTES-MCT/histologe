#!/bin/bash

echo "Executing migration..."
php bin/console doctrine:migrations:migrate --no-interaction
composer dump-autoload --no-dev --classmap-authoritative
php bin/console c:c

if [[ -z "${COMPOSER_DEV}" ]];
then
    echo "No data fixtures to load!"
else
    echo "Load data fixtures..."
    composer dump-env dev && php bin/console -e dev doctrine:fixtures:load --no-interaction
fi
