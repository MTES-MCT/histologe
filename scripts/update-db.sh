#!/bin/bash

echo "Create temp folder..."
if [ ! -d var/temp ];
then
  mkdir -p var/temp
  echo "Folder temp created"
else
  echo "Folder temp already created"
fi

php bin/console doctrine:migrations:migrate --no-interaction

if [[ -z "${COMPOSER_DEV}" ]];
then
    echo "No fixtures to load!"
else
    echo "Fixtures to load!"
    composer dump-env dev && php bin/console -e dev doctrine:fixtures:load --no-interaction
fi
