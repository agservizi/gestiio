#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
TAR=/tmp/gestiio-info-varie.tar

cd "$(dirname "$0")/.."

tar -cf "$TAR" \
  resources/views/Backend/Registri/infoSito.blade.php \
  app/Models/TipoContratto.php

base64 "$TAR" | ssh "$HOST" "base64 -d > $TAR"

ssh "$HOST" "
set -e
cd $APP
tar -xf $TAR
$DOCKER cp $APP/resources/views/Backend/Registri/infoSito.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Registri/infoSito.blade.php
$DOCKER cp $APP/app/Models/TipoContratto.php $CONTAINER:/var/www/html/app/Models/TipoContratto.php
$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
$DOCKER exec $CONTAINER grep -n 'telefoniaOfferta' /var/www/html/app/Models/TipoContratto.php | head -3
$DOCKER exec $CONTAINER grep -n 'text-gray-900' /var/www/html/resources/views/Backend/Registri/infoSito.blade.php | head -3
echo INFO_VARIE_500_FIX_OK
"

rm -f "$TAR"
