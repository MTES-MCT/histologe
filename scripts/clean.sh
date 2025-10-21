#!/bin/bash

echo "Retry failed Messenger messages before cleaning..."
php bin/console messenger:failed:retry --force || true # if retry failed the delivered_at field will be tagged with 9999-12-31 23:59:59
echo "Clear older records from entities..."
php bin/console app:clear-entities
echo "Clear tmp folder from object storage S3..."
php bin/console app:clear-storage-tmp-folder
echo "Update signalement INJONCTION TO NEED_VALIDATION after weeks"
php bin/console app:reset-injonction-no-response
