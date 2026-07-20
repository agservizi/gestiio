#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
YML=/home/Carmine/apps/stirling-pdf/configs/settings.yml
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -a "$YML" "$YML.bak-fixlocale-$(date +%Y%m%d%H%M%S)"

# CRITICAL: defaultLocale must be hyphen it-IT (not it_IT)
# languages whitelist uses underscore it_IT
python3 - <<'PY'
from pathlib import Path
import re
p = Path('/home/Carmine/apps/stirling-pdf/configs/settings.yml')
text = p.read_text(encoding='utf-8')
text2 = re.sub(r'(?m)^(\s*defaultLocale:\s*).*$', r'\1"it-IT"', text)
text2 = re.sub(r'(?m)^(\s*languages:\s*).*$', r'\1["it_IT"]', text2)
if text2 != text:
    p.write_text(text2, encoding='utf-8')
    print('settings.yml patched')
else:
    print('settings.yml already ok or patterns missed')
print('---')
for line in p.read_text(encoding='utf-8').splitlines():
    if 'defaultLocale' in line or line.strip().startswith('languages:'):
        print(line)
PY

# Find how Stirling signals restart
echo '=== restart flag search ==='
$DOCKER exec stirling-pdf sh -c 'find /configs /tmp /app -iname "*restart*" 2>/dev/null | head -40' || true
ls -la /home/Carmine/apps/stirling-pdf/configs/ | head -40

# Deploy restart watcher
cp -f /tmp/stirling-docker-restart-watch.sh /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
perl -pi -e 's/\r\n/\n/g' /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
chmod +x /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh

# Probe stirling source/docs for restart file name inside image if possible
$DOCKER exec stirling-pdf sh -c 'grep -R "restart" /configs 2>/dev/null | head -5; ls /configs' || true

# Recreate stirling with fixed locale
cd /home/Carmine/apps/stirling-pdf
# Ensure env has correct locale format
perl -pi -e 's/SYSTEM_DEFAULTLOCALE:.*/SYSTEM_DEFAULTLOCALE: it-IT/' docker-compose.stirling.yml
perl -pi -e 's/UI_LANGUAGES:.*/UI_LANGUAGES: it_IT/' docker-compose.stirling.yml

$DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "health=$st"
  [ "$st" = "healthy" ] && break
  sleep 2
done
$DOCKER compose -f docker-compose.stirling.yml up -d stirling-lan

# Ensure desktop URL 8092
perl -pi -e 's|^STIRLING_DESKTOP_URL=.*|STIRLING_DESKTOP_URL=http://192.168.1.50:8092|' "$APP/.env"
$DOCKER cp "$APP/.env" gestiio-app:/var/www/html/.env

# Deploy UI files
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/StirlingSsoService.php "$APP/app/Http/Services/StirlingSsoService.php" 2>/dev/null || true
cp -f /tmp/services.php "$APP/config/services.php" 2>/dev/null || true
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
[ -f /tmp/StirlingSsoService.php ] && $DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
[ -f /tmp/services.php ] && $DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php

$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear

# Start restart watcher if not running
pkill -f stirling-docker-restart-watch.sh 2>/dev/null || true
nohup bash /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh >> /home/Carmine/apps/stirling-pdf/logs/restart-watch.log 2>&1 &
echo "watcher_pid=$!"

curl -sS http://127.0.0.1:8091/pdf-tools/api/v1/info/status; echo
curl -sS http://127.0.0.1:8092/api/v1/info/status; echo
grep -n 'defaultLocale\|languages:' "$YML" | head -5
echo FIX_OK
