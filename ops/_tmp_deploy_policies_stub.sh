#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
# opcache: touch + restart apache inside or container restart
$DOCKER exec gestiio-app sh -c 'apachectl graceful 2>/dev/null || kill -USR1 1 2>/dev/null || true'
sleep 2
$DOCKER exec gestiio-app grep -n 'saasOnlyPoliciesStub' /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php | head -3
echo DONE
