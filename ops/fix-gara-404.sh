#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
ARCHIVE=/tmp/gestiio-gara-404.tar

cd "$(dirname "$0")/.."

cat > public/opcache-reset.php <<'PHP'
<?php
if (!isset($_GET['k']) || $_GET['k'] !== 'gestiio-opcache-20261021') {
    http_response_code(404);
    exit;
}
echo function_exists('opcache_reset') && opcache_reset() ? 'opcache_reset_ok' : 'opcache_unavailable';
PHP

tar -cf "$ARCHIVE" \
  app/Http/Controllers/Backend/GaraController.php \
  app/Models/Gara.php \
  app/Models/GaraSoglia.php \
  app/Models/GaraRisultato.php \
  app/Http/Services/GaraEvalService.php \
  resources/views/Backend/Gara \
  routes/web-backend.php \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  public/opcache-reset.php

base64 "$ARCHIVE" | ssh "$HOST" "base64 -d > $ARCHIVE"

ssh "$HOST" "
set -e
cd $APP
tar -xf $ARCHIVE
$DOCKER cp $APP/app/Http/Controllers/Backend/GaraController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/GaraController.php
$DOCKER cp $APP/app/Models/Gara.php $CONTAINER:/var/www/html/app/Models/Gara.php
$DOCKER cp $APP/app/Models/GaraSoglia.php $CONTAINER:/var/www/html/app/Models/GaraSoglia.php
$DOCKER cp $APP/app/Models/GaraRisultato.php $CONTAINER:/var/www/html/app/Models/GaraRisultato.php
$DOCKER cp $APP/app/Http/Services/GaraEvalService.php $CONTAINER:/var/www/html/app/Http/Services/GaraEvalService.php
$DOCKER cp $APP/resources/views/Backend/Gara $CONTAINER:/var/www/html/resources/views/Backend/Gara
$DOCKER cp $APP/routes/web-backend.php $CONTAINER:/var/www/html/routes/web-backend.php
$DOCKER cp $APP/resources/views/Backend/_layout/app-sidebar-menu.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
$DOCKER cp $APP/public/opcache-reset.php $CONTAINER:/var/www/html/public/opcache-reset.php

$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear; php /var/www/html/artisan route:clear; php /var/www/html/artisan config:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:cache; php /var/www/html/artisan route:cache; php /var/www/html/artisan view:cache' || true

echo OPCACHE
$DOCKER exec $CONTAINER sh -lc 'curl -sk -H \"Host: gestiio.agenziaplinio.it\" \"http://127.0.0.1/opcache-reset.php?k=gestiio-opcache-20261021\"'
echo
$DOCKER exec $CONTAINER sh -lc 'apache2ctl graceful 2>/dev/null || service apache2 reload 2>/dev/null || true'

echo VERIFY
$DOCKER exec $CONTAINER sh -lc 'curl -sk -o /dev/null -w \"gara=%{http_code} redir=%{redirect_url}\\n\" -H \"Host: gestiio.agenziaplinio.it\" http://127.0.0.1/backend/gara'
$DOCKER exec $CONTAINER sh -lc 'curl -sk -o /dev/null -w \"listino=%{http_code} redir=%{redirect_url}\\n\" -H \"Host: gestiio.agenziaplinio.it\" http://127.0.0.1/backend/listino'

$DOCKER exec $CONTAINER rm -f /var/www/html/public/opcache-reset.php
rm -f $APP/public/opcache-reset.php
echo GARA_404_FIX_OK
"

rm -f "$ARCHIVE" public/opcache-reset.php
