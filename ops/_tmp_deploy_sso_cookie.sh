#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

# Syntax check
$DOCKER exec gestiio-app php -l /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php

echo DEPLOY_SSO_COOKIE_OK
