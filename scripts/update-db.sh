#!/bin/bash

php bin/console doctrine:migrations:migrate --no-interaction

if [[ -z "${COMPOSER_DEV}" ]];
then
    echo "No fixtures to load!"
else
    echo "Fixtures to load!"
    composer dump-env dev && php bin/console -e dev doctrine:fixtures:load --no-interaction
fi

mkdir var/temp 2>/dev/null