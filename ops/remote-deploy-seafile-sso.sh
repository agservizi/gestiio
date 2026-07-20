#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
CONTAINER=gestiio-app

cd /tmp
rm -rf sea-sso && mkdir sea-sso && cd sea-sso
tar -xf /tmp/seafile-sso.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

mkdir -p "$APP/resources/views/Backend/Documenti"
cp -f app/Http/Controllers/Backend/SeafileDocumentiController.php "$APP/app/Http/Controllers/Backend/"
cp -f resources/views/Backend/Documenti/seafile-sso.blade.php "$APP/resources/views/Backend/Documenti/"
cp -f resources/views/Backend/Documenti/seafile.blade.php "$APP/resources/views/Backend/Documenti/" 2>/dev/null || true

$DOCKER exec "$CONTAINER" mkdir -p /var/www/html/resources/views/Backend/Documenti
$DOCKER cp app/Http/Controllers/Backend/SeafileDocumentiController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php"
$DOCKER cp resources/views/Backend/Documenti/seafile-sso.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/Documenti/seafile-sso.blade.php"
if [ -f resources/views/Backend/Documenti/seafile.blade.php ]; then
  $DOCKER cp resources/views/Backend/Documenti/seafile.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/Documenti/seafile.blade.php"
fi

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER restart "$CONTAINER"
sleep 6
echo SSO_DEPLOY_OK
