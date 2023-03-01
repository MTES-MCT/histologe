#!/bin/bash

echo "Executing migration..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Construction du contenu du fichier .user.ini"
ini_contents="opcache.preload=/app/config/preload.php\nopcache.preload_user=appsdeck"

echo "Écriture du contenu dans le fichier .user.ini"
echo -e $ini_contents > .user.ini

if [[ -z "${COMPOSER_DEV}" ]];
then
    echo "No data fixtures to load!"
else
    echo "Load data fixtures..."
    composer dump-env dev && php bin/console -e dev doctrine:fixtures:load --no-interaction
fi
