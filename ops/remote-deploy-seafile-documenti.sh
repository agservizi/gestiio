#!/bin/sh
# Deploy codice Documenti→Seafile su NAS (dopo Seafile stack up + bootstrap).
set -e
APP=/home/Carmine/apps/gestiio-20260624-2128
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CONTAINER=gestiio-app

cd /tmp
rm -rf seafile-doc-deploy
mkdir seafile-doc-deploy
cd seafile-doc-deploy
tar -xf /tmp/seafile-doc-deploy.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

# Copia host + container
for f in \
  app/Http/Services/SeafileClient.php \
  app/Http/Controllers/Backend/SeafileDocumentiController.php \
  app/Console/Commands/ImportDocumentiToSeafile.php \
  config/services.php \
  routes/web-backend.php \
  resources/views/Backend/Documenti/seafile.blade.php \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php \
  DEPLOY.md \
  ops/smoke-seafile-documenti.php \
  ops/docker-compose.seafile.yml \
  ops/seafile-bootstrap.sh \
  ops/seafile.env.example
do
  mkdir -p "$APP/$(dirname "$f")"
  cp -f "$f" "$APP/$f"
  $DOCKER cp "$f" "$CONTAINER:/var/www/html/$f" 2>/dev/null || true
done

mkdir -p "$APP/resources/views/Backend/Documenti"
$DOCKER exec "$CONTAINER" mkdir -p /var/www/html/resources/views/Backend/Documenti

$DOCKER cp app/Http/Services/SeafileClient.php "$CONTAINER:/var/www/html/app/Http/Services/SeafileClient.php"
$DOCKER cp app/Http/Controllers/Backend/SeafileDocumentiController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php"
$DOCKER cp app/Console/Commands/ImportDocumentiToSeafile.php "$CONTAINER:/var/www/html/app/Console/Commands/ImportDocumentiToSeafile.php"
$DOCKER cp config/services.php "$CONTAINER:/var/www/html/config/services.php"
$DOCKER cp routes/web-backend.php "$CONTAINER:/var/www/html/routes/web-backend.php"
$DOCKER cp resources/views/Backend/Documenti/seafile.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/Documenti/seafile.blade.php"
$DOCKER cp resources/views/Backend/_layout/app-sidebar-menu.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php"
$DOCKER cp database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php "$CONTAINER:/var/www/html/database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php"
$DOCKER cp ops/smoke-seafile-documenti.php "$CONTAINER:/tmp/smoke-seafile-documenti.php"

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Services/SeafileClient.php
$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php
$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Console/Commands/ImportDocumentiToSeafile.php

$DOCKER exec "$CONTAINER" php /var/www/html/artisan migrate --force --path=database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan config:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan route:clear
$DOCKER restart "$CONTAINER"
sleep 6
echo DEPLOY_SEAFILE_DOC_OK
