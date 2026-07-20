#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

# Deploy controller
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER exec gestiio-app sh -c 'apachectl graceful 2>/dev/null || true'

# Deploy + start watcher
cp -f /tmp/stirling-docker-restart-watch.sh /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
perl -pi -e 's/\r\n/\n/g' /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
chmod +x /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh

ps aux | grep '[s]tirling-docker-restart-watch' | awk '{print $2}' | xargs -r kill 2>/dev/null || true
sleep 1
nohup bash /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh >> /home/Carmine/apps/stirling-pdf/logs/restart-watch.log 2>&1 &
echo "watcher_pid=$!"
sleep 1
ps aux | grep '[s]tirling-docker-restart-watch' || echo 'WATCHER_MISSING'
tail -n 5 /home/Carmine/apps/stirling-pdf/logs/restart-watch.log || true
$DOCKER exec gestiio-app grep -n 'isAdminSettingsRestartPath\|202' /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php | head -10
echo DONE
