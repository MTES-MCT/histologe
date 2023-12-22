#!/bin/bash

if [ "${APP}" = "histologe" ]; then
    echo "This script cannot run in production."
    exit 1
fi

if [ -z "${RUN_SYNC_DB}" ] || [ "${RUN_SYNC_DB}" -ne 1 ]; then
    echo "RUN_SYNC_DB environment variable is not set to 1. The script will not run."
    exit 1
fi

archive_name="backup.tar.gz"

# Install the Scalingo CLI tool in the container:
echo ">>> Install the Scalingo CLI tool in the container"
install-scalingo-cli

# Install additional tools to interact with the database:
echo ">>> Install additional tools to interact with the database"
dbclient-fetcher mysql 5.7

# Login to Scalingo, using the token stored in `DUPLICATE_API_TOKEN`:
echo ">>> Login to Scalingo"
scalingo login --api-token "${DUPLICATE_API_TOKEN}"

# Retrieve the addon id:
echo ">>> Retrieve the addon id"
addon_id="$( scalingo --app "${DUPLICATE_SOURCE_APP}" addons \
             | grep "${DUPLICATE_ADDON_KIND}" \
             | cut -d "|" -f 3 \
             | tr -d " " )"

# Download the latest backup available for the specified addon:
echo ">>> Download the latest backup available for the specified addon"
scalingo --app "${DUPLICATE_SOURCE_APP}" --addon "${addon_id}" \
    backups-download --output "${archive_name}"

# Extract the archive containing the downloaded backup:
echo ">>> Extraction the archive containing the downloaded backup"
backup_file_name="$( tar --extract --verbose --file="${archive_name}" --directory="/app/" \
                     | cut -d " " -f 2 | cut -d "/" -f 2 )"

# Hack to make sure to get sql file
backup_file_name="$(ls /app/*.sql)"

# Remove CREATE and USE instruction from backup
echo ">>> Remove CREATE and USE instruction"
sed -i '/CREATE DATABASE/d' "${backup_file_name}"
sed -i '/^USE/d' "${backup_file_name}"

# Loading database
echo ">>> Loading database"
mysql -u ${DATABASE_USER} --password=${DATABASE_PASSWORD} -h ${DATABASE_HOST} -P ${DATABASE_PORT} ${DATABASE_NAME} < "${backup_file_name}"
echo ">>> Done, thank you"
