#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

perl -pi -e 's/\r\n/\n/g' /tmp/chat-messages.blade.php
cp -f /tmp/chat-messages.blade.php "$APP/resources/views/Backend/Chat/_messages.blade.php"
$DOCKER cp /tmp/chat-messages.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/_messages.blade.php
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app grep -n 'fileExists\|Allegato non disponibile' /var/www/html/resources/views/Backend/Chat/_messages.blade.php | head -10
echo DEPLOY_OK
