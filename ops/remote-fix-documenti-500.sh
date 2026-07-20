#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
C=gestiio-app

cd /tmp
rm -rf fix500 && mkdir fix500 && cd fix500
tar -xf /tmp/seafile-fix500.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

for f in \
  app/Http/Controllers/Backend/CartellaFilesController.php \
  app/Http/Controllers/Backend/DashboardController.php \
  app/Http/Middleware/EncryptCookies.php \
  routes/web-backend.php \
  resources/views/Backend/Dashboard/showAdmin.blade.php \
  resources/views/Backend/Dashboard/showAgente.blade.php
do
  mkdir -p "$APP/$(dirname "$f")"
  cp -f "$f" "$APP/$f"
  $DOCKER cp "$f" "$C:/var/www/html/$f"
done

$DOCKER exec "$C" php -l /var/www/html/app/Http/Controllers/Backend/CartellaFilesController.php
$DOCKER exec "$C" php /var/www/html/artisan view:clear
$DOCKER exec "$C" php /var/www/html/artisan route:clear
$DOCKER restart "$C"
sleep 6
echo FIX500_OK
