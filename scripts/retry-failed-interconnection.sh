#!/bin/bash

echo "Retry failed push to si-sh"
php bin/console app:retry-failed-push sish
echo "Retry failed push to schs"
php bin/console app:retry-failed-push schs
echo "Retry failed push to idoss"
php bin/console app:retry-failed-push idoss
