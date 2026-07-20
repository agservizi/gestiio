#!/bin/sh
set -e
APP=/home/Carmine/apps/gestiio-20260624-2128
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CONTAINER=gestiio-app

cd /tmp
rm -rf pdf-tools-fix3
mkdir pdf-tools-fix3
cd pdf-tools-fix3
tar -xf /tmp/pdf-tools-fix3.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

php -l app/Http/Controllers/Backend/PdfToolsController.php
grep -n 'app-config' app/Http/Controllers/Backend/PdfToolsController.php

cp -f app/Http/Controllers/Backend/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f ops/smoke-pdf-tools-public.php "$APP/ops/smoke-pdf-tools-public.php"

$DOCKER cp app/Http/Controllers/Backend/PdfToolsController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp ops/smoke-pdf-tools-public.php "$CONTAINER:/tmp/smoke-pdf-tools-public.php"

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan config:clear
$DOCKER restart "$CONTAINER"
sleep 8

echo "=== smoke ==="
$DOCKER exec "$CONTAINER" php /tmp/smoke-pdf-tools-public.php

echo "=== config direct ==="
curl -sk "https://gestiio.agenziaplinio.it/pdf-tools/api/v1/config/app-config" | head -c 400
echo

echo "=== allegati routes in container ==="
$DOCKER exec "$CONTAINER" php /var/www/html/artisan route:list --path=allegati-mobile-scan 2>/dev/null | head -20

echo "=== files present ==="
$DOCKER exec "$CONTAINER" test -f /var/www/html/app/Http/Controllers/Backend/AllegatoMobileScanController.php && echo AllegatoMobileScanController_OK
$DOCKER exec "$CONTAINER" test -f /var/www/html/app/Http/Services/StirlingMobileScannerService.php && echo StirlingMobileScannerService_OK
$DOCKER exec "$CONTAINER" grep -q 'Carica da dispositivo mobile' /var/www/html/resources/views/Backend/_components/dropzoneUx.blade.php && echo dropzoneUx_OK

echo DONE
