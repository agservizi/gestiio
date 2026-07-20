#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
perl -pi -e 's/\r\n/\n/g' /tmp/ChatController.php
cp -f /tmp/ChatController.php /home/Carmine/apps/gestiio-20260624-2128/app/Http/Controllers/Backend/ChatController.php
$DOCKER cp /tmp/ChatController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/ChatController.php
$DOCKER exec gestiio-app grep -n 'isImmagineMissing\|X-Chat-Attachment-Missing' /var/www/html/app/Http/Controllers/Backend/ChatController.php | head -10
echo OK
