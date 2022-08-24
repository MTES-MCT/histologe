#!/bin/bash

php bin/console doctrine:migrations:migrate --no-interaction

if [[ -z "${LOAD_FIXTURES}" ]];
then
    echo "No fixtures to load!"
else
    php bin/console doctrine:fixtures:load --no-interaction
fi