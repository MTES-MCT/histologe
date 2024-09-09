#!/bin/bash

echo "Clear older records from entities..."
php bin/console app:clear-entities
echo "Clear tmp folder from object storage S3..."
php bin/console app:clear-storage-tmp-folder