#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/PdfToolsController.php "$APP/app/Http/Controllers/Backend/PdfToolsController.php"
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER restart gestiio-app

for i in $(seq 1 30); do
  code=$(curl -sS -m 3 -o /dev/null -w '%{http_code}' http://127.0.0.1:8090/login || echo 000)
  [ "$code" = "200" ] && break
  sleep 2
done

echo '=== verify toml clean (no script inject) ==='
curl -sS -m 5 -o /tmp/it.toml -w 'http=%{http_code} bytes=%{size_download}\n' 'http://127.0.0.1:8090/pdf-tools/locales/it-IT/translation.toml'
head -c 80 /tmp/it.toml; echo
if grep -q '<script>' /tmp/it.toml; then echo 'FAIL still injected'; else echo 'OK clean toml'; fi
grep -m1 'chooseFile' /tmp/it.toml || true

# Also fix Default Locale in settings UI was showing en_US
python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
out=[]
for line in text.splitlines(True):
    s=line.lstrip(); ind=line[:len(line)-len(s)]
    if s.startswith("defaultLocale:"):
        out.append(f'{ind}defaultLocale: "it-IT"\n')
    elif s.startswith("languages:"):
        out.append(f'{ind}languages: []\n')
    else:
        out.append(line)
p.write_text("".join(out), encoding="utf-8")
print("settings locale forced it-IT")
PY
grep -nE 'defaultLocale|languages:' /home/Carmine/apps/stirling-pdf/configs/settings.yml
echo DONE
