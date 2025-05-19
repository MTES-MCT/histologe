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
dbclient-fetcher mysql 8.0

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
echo ">>> Extract the archive containing the downloaded backup"
backup_file_name="$( tar --extract --verbose --file="${archive_name}" --directory="/app/" \
                     | cut -d " " -f 2 | cut -d "/" -f 2 )"

# Hack to make sure to get sql file
backup_file_name="$(ls /app/*.sql)"

# Remove CREATE and USE instruction from backup
echo ">>> Remove CREATE and USE instruction"
sed -i '/CREATE DATABASE/d' "${backup_file_name}"
sed -i '/^USE/d' "${backup_file_name}"
sed -i '/DEFINER/d' "${backup_file_name}"

# Avoids missing silent errors
#echo ">>> Avoids missing silent errors"
#set -e
#set -o pipefail

### TESTS
#echo ">>> Test with cat"
#cat "$backup_file_name" | grep -v '^--' | grep -v '^/' | mysql --no-defaults --force --user="${DATABASE_USER}" --password="${DATABASE_PASSWORD}" \
#      --host="${DATABASE_HOST}" --port="${DATABASE_PORT}" "${DATABASE_NAME}" 2> mysql_error.log
#line=$(tail -n 1 mysql_error.log)
#echo "> mysql log with cat"
#echo $line

# Test import ligne par ligne pour traquer l’erreur
while IFS= read -r line; do
    echo "$line" | mysql -u "${DATABASE_USER}" --password="${DATABASE_PASSWORD}" \
         -h "${DATABASE_HOST}" -P "${DATABASE_PORT}" "${DATABASE_NAME}" 2>> mysql_error_line.log
done < "$backup_file_name"

# Loading database
echo ">>> Loading database"
mysql --connect-timeout=10 --verbose --show-warnings -u ${DATABASE_USER} --password=${DATABASE_PASSWORD} -h ${DATABASE_HOST} -P ${DATABASE_PORT} ${DATABASE_NAME} < "${backup_file_name}" > mysql_full_output.log 2>&1
EXIT_CODE=$?

TITLE="[Metabase] Synchronisation de Bdd"
if [ $EXIT_CODE -ne 0 ]; then
    echo ">>> ERROR: Database sync failed!"

    echo ">>> Import échoué. Dernières lignes du log :"
    tail -n 20 mysql_full_output.log
    
    line=$(tail -n 1 mysql_error.log)
    echo "> mysql log with exec"
    echo $line

    # Capture les logs MySQL pour le diagnostic
    ERROR_MESSAGE="La synchronisation de la bdd a échoué avec le code $EXIT_CODE"
    TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
    HOSTNAME=$(hostname)
    
    # If error, call prod env to send email
    curl -X POST "${SIGNAL_LOGEMENT_PROD_URL}/send-email" \
         -H "Content-Type: application/json" \
         -H "Authorization: Bearer ${SEND_ERROR_EMAIL_TOKEN}" \
         -d "{\"title\": \"$TITLE\", \"timestamp\": \"$TIMESTAMP\", \"host\": \"$HOSTNAME\", \"database\": \"${DATABASE_NAME}\", \"error\": \"$ERROR_MESSAGE\"}"

    echo ">>> Error reported to API."
else
    MESSAGE="base de données a été synchronisée avec succès"
    TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
    HOSTNAME=$(hostname)
    
    # If success, call prod env to send email
    curl -X POST "${SIGNAL_LOGEMENT_PROD_URL}/send-email" \
         -H "Content-Type: application/json" \
         -H "Authorization: Bearer ${SEND_ERROR_EMAIL_TOKEN}" \
         -d "{\"title\": \"$TITLE\", \"timestamp\": \"$TIMESTAMP\", \"host\": \"$HOSTNAME\", \"database\": \"${DATABASE_NAME}\", \"message\": \"$MESSAGE\"}"

    echo ">>> Success reported to API."
fi

echo ">>> Done, thank you"
