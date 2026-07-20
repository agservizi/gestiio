#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/PdfToolsIndex.blade.php "$APP/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php

# Fix settings languages to [] (app-config aveva [it_IT] e bloccava)
python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
out = []
for line in text.splitlines(True):
    s = line.lstrip()
    ind = line[: len(line) - len(s)]
    if s.startswith("defaultLocale:"):
        out.append(f'{ind}defaultLocale: "it-IT"\n')
    elif s.startswith("languages:"):
        out.append(f'{ind}languages: []\n')
    else:
        out.append(line)
p.write_text("".join(out), encoding="utf-8")
print("settings ok")
PY
grep -nE 'defaultLocale|languages:' /home/Carmine/apps/stirling-pdf/configs/settings.yml

$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php'
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER restart gestiio-app

for i in $(seq 1 30); do
  code=$(curl -sS -m 3 -o /dev/null -w '%{http_code}' http://127.0.0.1:8090/login || echo 000)
  echo "login=$code"
  [ "$code" = "200" ] && break
  sleep 2
done

$DOCKER exec gestiio-app grep -n 'i18nextLng-source\|italianForceScript\|v6' /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php /var/www/html/resources/views/Backend/PdfTools/index.blade.php | head -15
echo DONE
