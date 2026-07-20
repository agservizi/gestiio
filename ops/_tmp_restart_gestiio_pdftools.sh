#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php

$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php'
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

# Restart PHP/Apache to drop opcache
$DOCKER restart gestiio-app
for i in $(seq 1 40); do
  code=$(curl -sS -m 3 -o /dev/null -w '%{http_code}' http://127.0.0.1:8090/login || echo 000)
  echo "login=$code"
  [ "$code" = "200" ] && break
  sleep 2
done

echo '=== verify v4 marker ==='
$DOCKER exec gestiio-app grep -n 'v4\|App Windows\|gestiio-pdf-tools-v3' /var/www/html/resources/views/Backend/PdfTools/index.blade.php || true
echo DONE
