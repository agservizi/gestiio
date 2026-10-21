#!/bin/sh
# Footer v5.5 + badge contrasto Info varie
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
TAR=/tmp/gestiio-footer-ver.tar
RESET=/tmp/gestiio-opcache-reset.php

cd "$(dirname "$0")/.."

# Assicura config locale 5.5
grep -q "'versione' => '5.5'" config/configurazione.php || {
  echo "ERRORE: config locale non e' 5.5"
  exit 1
}

cat > "$RESET" <<'PHP'
<?php
if (!isset($_GET['k']) || $_GET['k'] !== 'gestiio-opcache-20261021') {
    http_response_code(404);
    exit;
}
echo function_exists('opcache_reset') && opcache_reset() ? 'opcache_reset_ok' : 'opcache_unavailable';
PHP

tar -cf "$TAR" \
  config/configurazione.php \
  resources/views/Backend/Registri/infoSito.blade.php \
  resources/views/Backend/_layout/app-footer.blade.php

base64 "$TAR" | ssh "$HOST" "base64 -d > $TAR"
base64 "$RESET" | ssh "$HOST" "base64 -d > /tmp/gestiio-opcache-reset.php"

ssh "$HOST" "
set -e
cd $APP
tar -xf $TAR
$DOCKER cp $APP/config/configurazione.php $CONTAINER:/var/www/html/config/configurazione.php
$DOCKER cp $APP/resources/views/Backend/Registri/infoSito.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Registri/infoSito.blade.php
$DOCKER cp $APP/resources/views/Backend/_layout/app-footer.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-footer.blade.php
$DOCKER cp /tmp/gestiio-opcache-reset.php $CONTAINER:/var/www/html/public/opcache-reset.php

$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear; php /var/www/html/artisan config:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:cache; php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
echo WEB_OPCACHE
$DOCKER exec $CONTAINER sh -lc 'curl -sk -H \"Host: gestiio.agenziaplinio.it\" \"http://127.0.0.1/opcache-reset.php?k=gestiio-opcache-20261021\"'
echo
$DOCKER exec $CONTAINER rm -f /var/www/html/public/opcache-reset.php
rm -f /tmp/gestiio-opcache-reset.php

echo VERIFY
$DOCKER exec $CONTAINER grep versione /var/www/html/config/configurazione.php | head -1
$DOCKER exec $CONTAINER php /var/www/html/artisan tinker --execute=\"echo 'cfg='.config('configurazione.versione');\""
$DOCKER exec $CONTAINER sh -lc 'php -r \"require \\\"/var/www/html/vendor/autoload.php\\\"; \\\$app=require \\\"/var/www/html/bootstrap/app.php\\\"; \\\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap(); echo \\\"boot=\".config(\\\"configurazione.versione\\\").PHP_EOL;\"'
$DOCKER exec $CONTAINER grep -n 'badge-light-primary\\|badge-primary' /var/www/html/resources/views/Backend/Registri/infoSito.blade.php | head -2
echo FOOTER_VER_BADGES_OK
"

rm -f "$TAR" "$RESET"
