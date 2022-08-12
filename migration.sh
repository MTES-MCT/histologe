#!/bin/bash
# Skip the first migration
php bin/console d:m:version 'DoctrineMigrations\Version20220811093406' --add --no-interaction 2> /dev/null
# Execute the other migrations
php bin/console d:m:m --no-interaction