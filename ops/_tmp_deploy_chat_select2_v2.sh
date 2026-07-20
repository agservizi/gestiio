#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_HOST=/home/Carmine/apps/gestiio-20260624-2128

perl -pi -e 's/\r\n/\n/g' /tmp/chat-index-select2.blade.php /tmp/chat-messages.blade.php /tmp/ChatController.php

cp -f /tmp/chat-index-select2.blade.php "$APP_HOST/resources/views/Backend/Chat/index.blade.php"
cp -f /tmp/chat-messages.blade.php "$APP_HOST/resources/views/Backend/Chat/_messages.blade.php"
cp -f /tmp/ChatController.php "$APP_HOST/app/Http/Controllers/Backend/ChatController.php"

$DOCKER cp /tmp/chat-index-select2.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/index.blade.php
$DOCKER cp /tmp/chat-messages.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/_messages.blade.php
$DOCKER cp /tmp/ChatController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/ChatController.php

$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
$DOCKER exec gestiio-app php /var/www/html/artisan cache:clear

echo '--- markers ---'
$DOCKER exec gestiio-app grep -n 'priorityBadge\|Select2 OK\|X-Chat-Attachment-Missing\|w-175px' \
  /var/www/html/resources/views/Backend/Chat/index.blade.php \
  /var/www/html/app/Http/Controllers/Backend/ChatController.php \
  /var/www/html/resources/views/Backend/Chat/_messages.blade.php | head -30
echo DEPLOY_OK
