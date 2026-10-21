#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
ARCHIVE=/tmp/gestiio-listino-gare-fix.tar

cd "$(dirname "$0")/.."

# bump cache bust
# (scripts.blade.php already patched before pack)

tar -cf "$ARCHIVE" \
  public/assets_backend/js-miei/mieiScript.js \
  assets_backend/js-miei/mieiScript.js \
  public/sw-gestiio-app.js \
  resources/views/Backend/_layout/partials/scripts.blade.php \
  resources/views/Backend/Listino/tabella.blade.php \
  resources/views/Backend/Listino/index.blade.php \
  resources/views/Backend/Listino/edit.blade.php \
  resources/views/Backend/Listino/show.blade.php \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  resources/views/Backend/Gara \
  app/Http/Controllers/Backend/GaraController.php \
  routes/web-backend.php

base64 "$ARCHIVE" | ssh "$HOST" "base64 -d > $ARCHIVE"

ssh "$HOST" "
set -e
cd $APP
tar -xf $ARCHIVE
$DOCKER cp $APP/public/assets_backend/js-miei/mieiScript.js $CONTAINER:/var/www/html/public/assets_backend/js-miei/mieiScript.js
$DOCKER cp $APP/assets_backend/js-miei/mieiScript.js $CONTAINER:/var/www/html/assets_backend/js-miei/mieiScript.js
$DOCKER cp $APP/public/sw-gestiio-app.js $CONTAINER:/var/www/html/public/sw-gestiio-app.js
$DOCKER cp $APP/resources/views/Backend/_layout/partials/scripts.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/partials/scripts.blade.php
$DOCKER cp $APP/resources/views/Backend/Listino/tabella.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Listino/tabella.blade.php
$DOCKER cp $APP/resources/views/Backend/Listino/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Listino/index.blade.php
$DOCKER cp $APP/resources/views/Backend/Listino/edit.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Listino/edit.blade.php
$DOCKER cp $APP/resources/views/Backend/Listino/show.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Listino/show.blade.php
$DOCKER cp $APP/resources/views/Backend/_layout/app-sidebar-menu.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
$DOCKER cp $APP/resources/views/Backend/Gara $CONTAINER:/var/www/html/resources/views/Backend/Gara
$DOCKER cp $APP/app/Http/Controllers/Backend/GaraController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/GaraController.php
$DOCKER cp $APP/routes/web-backend.php $CONTAINER:/var/www/html/routes/web-backend.php

$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear; php /var/www/html/artisan route:clear; php /var/www/html/artisan config:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:cache; php /var/www/html/artisan route:cache; php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true

echo MARKERS
$DOCKER exec $CONTAINER sh -lc 'grep -n \"mieiScript.js\" /var/www/html/resources/views/Backend/_layout/partials/scripts.blade.php | head -1'
$DOCKER exec $CONTAINER sh -lc 'grep -c \"data-toggle\" /var/www/html/resources/views/Backend/Listino/tabella.blade.php || echo 0'
$DOCKER exec $CONTAINER sh -lc 'grep -n Gare /var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php | head -2'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan route:list --path=gara --columns=method,uri,name,action 2>/dev/null | head -20 || php /var/www/html/artisan route:list 2>/dev/null | grep -i gara | head -10'
echo FIX_LISTINO_GARE_OK
"

rm -f "$ARCHIVE"
