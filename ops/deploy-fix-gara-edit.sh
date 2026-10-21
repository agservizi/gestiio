#!/bin/bash
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
TAR=/tmp/gestiio-gara-edit.tar

cd "$(dirname "$0")/.."

tar -cf "$TAR" resources/views/Backend/Gara/edit.blade.php
base64 "$TAR" | ssh "$HOST" "base64 -d > $TAR"
ssh "$HOST" "
set -e
cd $APP
tar -xf $TAR
$DOCKER cp $APP/resources/views/Backend/Gara/edit.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Gara/edit.blade.php
$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
grep -n 'prezzo_mese' $APP/resources/views/Backend/Gara/edit.blade.php | head -n 5
$DOCKER exec $CONTAINER grep -n 'prezzo_mese' /var/www/html/resources/views/Backend/Gara/edit.blade.php | head -n 5
echo GARA_EDIT_FIX_OK
"
rm -f "$TAR"
