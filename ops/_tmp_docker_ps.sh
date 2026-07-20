#!/bin/bash
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
$DOCKER ps --format '{{.Names}}'
echo '---'
$DOCKER exec gestiio-app grep -n 'initChatComposerSelect2\|select2-hidden\|chat-priority' /var/www/html/resources/views/Backend/Chat/index.blade.php | head -20
echo '---'
$DOCKER exec gestiio-app ls -la /var/www/html/storage/framework/views/ | wc -l
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
echo VIEW_CLEARED
