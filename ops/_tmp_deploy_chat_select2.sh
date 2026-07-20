#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_HOST=/home/Carmine/apps/gestiio-20260624-2128
SRC=/tmp/chat-index-select2.blade.php

if [ ! -f "$SRC" ]; then
  echo "MISSING $SRC"
  exit 1
fi

# normalize LF
perl -pi -e 's/\r\n/\n/g' "$SRC"

cp -f "$SRC" "$APP_HOST/resources/views/Backend/Chat/index.blade.php"
$DOCKER cp "$SRC" gestiio-app:/var/www/html/resources/views/Backend/Chat/index.blade.php
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app php /var/www/html/artisan cache:clear

echo '--- marker check ---'
$DOCKER exec gestiio-app grep -n 'initChatPageSelect2\|dropdownParent\|Template rapidi' /var/www/html/resources/views/Backend/Chat/index.blade.php | head -20
echo DEPLOY_OK
