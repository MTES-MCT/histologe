#!/bin/bash

if [[ -z "${SKIP_FIRST_MIGRATION}" ]]; # Execute the skip migration in main application
then
    echo "Skip the first migration"
    php bin/console d:m:version 'DoctrineMigrations\Version20220811093406' --add --no-interaction
fi
php bin/console d:m:m --no-interaction # Execute migrations
