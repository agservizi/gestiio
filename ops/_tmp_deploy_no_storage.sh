#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
STIR=/home/Carmine/apps/stirling-pdf

cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/StirlingSsoService.php "$APP/app/Http/Services/StirlingSsoService.php"
cp -f /tmp/services.php "$APP/config/services.php"
cp -f /tmp/docker-compose.stirling.yml "$STIR/docker-compose.stirling.yml"
perl -pi -e 's/\r\n/\n/g' "$STIR/docker-compose.stirling.yml"

$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
$DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php

python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
# Toggle only storage.enabled
import re
text2, n = re.subn(
    r'(?m)^(storage:\n(?:  .*\n)*?  )enabled:\s*\w+',
    r'\1enabled: false',
    text,
    count=1,
)
if n == 0:
    text2 = text.replace(
        "  enabled: true # set to 'true' to allow users to store files on the server",
        "  enabled: false # set to 'true' to allow users to store files on the server",
        1,
    )
p.write_text(text2, encoding="utf-8")
print("storage patch ok")
PY
grep -nA3 '^storage:' "$STIR/configs/settings.yml" | head -8

if [ -f "$APP/.env" ]; then
  grep -q '^STIRLING_SHARED_SESSION=' "$APP/.env" || echo 'STIRLING_SHARED_SESSION=true' >> "$APP/.env"
  grep -q '^STIRLING_STORAGE_ENABLED=' "$APP/.env" || echo 'STIRLING_STORAGE_ENABLED=false' >> "$APP/.env"
  sed -i 's/^STIRLING_SHARED_SESSION=.*/STIRLING_SHARED_SESSION=true/' "$APP/.env"
  sed -i 's/^STIRLING_STORAGE_ENABLED=.*/STIRLING_STORAGE_ENABLED=false/' "$APP/.env"
fi

$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php'
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER restart gestiio-app

cd "$STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf

for i in $(seq 1 45); do
  code=$($DOCKER exec stirling-pdf sh -c 'curl -sS -m 2 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/api/v1/info/status' 2>/dev/null || echo 000)
  [ "$code" = "200" ] && break
  sleep 2
done
echo "stirling=$code"
grep -nA2 '^storage:' "$STIR/configs/settings.yml" | head -5
$DOCKER exec gestiio-app grep -n 'I file non vengono salvati\|shared_session\|usesSharedSession' /var/www/html/resources/views/Backend/PdfTools/index.blade.php /var/www/html/config/services.php /var/www/html/app/Http/Services/StirlingSsoService.php | head -12
echo DONE
