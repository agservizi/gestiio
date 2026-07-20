#!/bin/sh
set -e
APP=/home/Carmine/apps/gestiio-20260624-2128
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CONTAINER=gestiio-app

cd /tmp
rm -rf pdf-tools-fix2
mkdir pdf-tools-fix2
cd pdf-tools-fix2
tar -xf /tmp/pdf-tools-fix2.tar

# CRLF -> LF without touching letter r
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

grep -n 'extends Controller' app/Http/Controllers/Backend/PdfToolsController.php
grep -n 'return true' app/Http/Controllers/Backend/PdfToolsController.php | head -5
php -l app/Http/Controllers/Backend/PdfToolsController.php || true

cp -f app/Http/Controllers/Backend/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f public/.htaccess "$APP/public/.htaccess"
cp -f resources/views/Backend/PdfTools/index.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f ops/smoke-pdf-tools-public.php "$APP/ops/smoke-pdf-tools-public.php"

$DOCKER cp app/Http/Controllers/Backend/PdfToolsController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp public/.htaccess "$CONTAINER:/var/www/html/public/.htaccess"
$DOCKER cp resources/views/Backend/PdfTools/index.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/PdfTools/index.blade.php"
$DOCKER cp ops/smoke-pdf-tools-public.php "$CONTAINER:/tmp/smoke-pdf-tools-public.php"

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan config:clear
$DOCKER restart "$CONTAINER"
sleep 8

echo "=== /pdf-tools/ ==="
curl -sI -k "https://gestiio.agenziaplinio.it/pdf-tools/" | sed -n '1,12p'
echo "=== /pdf-tools ==="
curl -sI -k "https://gestiio.agenziaplinio.it/pdf-tools" | sed -n '1,12p'
echo "=== smoke ==="
$DOCKER exec "$CONTAINER" php /tmp/smoke-pdf-tools-public.php
echo DONE
