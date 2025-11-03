#!/bin/bash

echo "Retry failed push to si-sh"
php bin/console app:retry-failed-push-esabora-sish
echo "Retry failed push to idoss"
php bin/console app:retry-failed-push-idoss
