#!/bin/bash

echo "[SISH]Launch esabora status synchronisation..."
php bin/console app:sync-esabora-sish --profile
echo "[SISH]Launch esabora intervention synchronisation..."
php bin/console app:sync-esabora-sish-intervention --profile
