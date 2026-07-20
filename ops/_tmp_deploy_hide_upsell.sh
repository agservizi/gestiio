#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
STIR=/home/Carmine/apps/stirling-pdf

cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/docker-compose.stirling.yml "$STIR/docker-compose.stirling.yml"
perl -pi -e 's/\r\n/\n/g' "$STIR/docker-compose.stirling.yml"

$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php

python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
out = []
for line in text.splitlines(True):
    s = line.lstrip()
    ind = line[: len(line) - len(s)]
    if s.startswith("enableDesktopInstallSlide:"):
        out.append(f"{ind}enableDesktopInstallSlide: false\n")
    elif s.startswith("showUpdate:") and "OnlyAdmin" not in s:
        out.append(f"{ind}showUpdate: false\n")
    elif s.startswith("defaultLocale:"):
        out.append(f'{ind}defaultLocale: "it-IT"\n')
    else:
        out.append(line)
p.write_text("".join(out), encoding="utf-8")
print("settings patched")
PY
grep -nE 'enableDesktopInstallSlide|showUpdate:|defaultLocale:' "$STIR/configs/settings.yml" | head -10

$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php'
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER restart gestiio-app

cd "$STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf

for i in $(seq 1 40); do
  code=$($DOCKER exec stirling-pdf sh -c 'curl -sS -m 2 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/api/v1/info/status' 2>/dev/null || echo 000)
  [ "$code" = "200" ] && break
  sleep 2
done
echo "stirling_status=$code"

# verify settings after recreate
grep -nE 'enableDesktopInstallSlide|showUpdate:' "$STIR/configs/settings.yml" | head -5
$DOCKER exec gestiio-app grep -n '_dismissUpsell\|Salta per ora\|gestiio-stirling-ux' /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php | head -8
echo DONE
