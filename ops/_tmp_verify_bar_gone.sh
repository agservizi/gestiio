#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php'
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app php /var/www/html/artisan cache:clear
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

echo '=== has App Windows in blade? ==='
if $DOCKER exec gestiio-app grep -q 'App Windows Stirling' /var/www/html/resources/views/Backend/PdfTools/index.blade.php; then
  echo STILL_PRESENT
else
  echo REMOVED_OK
fi

echo '=== first 100 lines ==='
$DOCKER exec gestiio-app sed -n '60,100p' /var/www/html/resources/views/Backend/PdfTools/index.blade.php

# Bust: add invisible version meta so we can verify refresh
# (already removed bar - just confirm)

echo DONE
