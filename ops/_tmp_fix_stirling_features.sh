#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
TD=/home/Carmine/apps/stirling-pdf/tessdata
COMPOSE=/home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml

mkdir -p "$TD"

# Copia i traineddata già presenti nell'immagine (path reale tesseract 5)
echo '=== seed tessdata from image paths ==='
$DOCKER exec stirling-pdf sh -c '
  for d in /usr/share/tesseract-ocr/5/tessdata /usr/share/tesseract-ocr/4.00/tessdata; do
    if [ -d "$d" ]; then
      echo "FROM $d"
      ls "$d"
    fi
  done
'

# Estrai dal filesystem del container i file (prima che il mount vuoto li nasconda non possiamo - sono sotto altro path)
$DOCKER cp stirling-pdf:/usr/share/tesseract-ocr/5/tessdata/. "$TD/" 2>/dev/null || true
ls -la "$TD" | head -20

# Scarica italiano se manca
if [ ! -f "$TD/ita.traineddata" ]; then
  echo 'Downloading ita.traineddata...'
  curl -fsSL -o "$TD/ita.traineddata" \
    https://github.com/tesseract-ocr/tessdata_fast/raw/main/ita.traineddata
fi
if [ ! -f "$TD/eng.traineddata" ]; then
  curl -fsSL -o "$TD/eng.traineddata" \
    https://github.com/tesseract-ocr/tessdata_fast/raw/main/eng.traineddata
fi
if [ ! -f "$TD/osd.traineddata" ]; then
  curl -fsSL -o "$TD/osd.traineddata" \
    https://github.com/tesseract-ocr/tessdata_fast/raw/main/osd.traineddata
fi

echo "tessdata files: $(ls "$TD" | wc -l)"

# Patch settings.yml
python3 - <<'PY'
from pathlib import Path
p = Path('/home/Carmine/apps/stirling-pdf/configs/settings.yml')
t = p.read_text(encoding='utf-8')
repls = [
    ('enableLogin: true # set to \'true\' to enable login',
     'enableLogin: false # set to \'true\' to enable login'),
    ('defaultHideUnavailableTools: true #',
     'defaultHideUnavailableTools: false #'),
    ('defaultHideUnavailableConversions: true #',
     'defaultHideUnavailableConversions: false #'),
]
out = t
for a,b in repls:
    out = out.replace(a,b)
if out != t:
    p.write_text(out, encoding='utf-8')
    print('SETTINGS_PATCHED')
else:
    print('SETTINGS_NO_CHANGE')
PY

echo '=== restart stirling ==='
$DOCKER compose -f "$COMPOSE" up -d
sleep 8
$DOCKER ps --filter name=stirling-pdf --format '{{.Status}}'
$DOCKER exec stirling-pdf sh -c 'ls /usr/share/tessdata | wc -l; tesseract --list-langs 2>&1 | head -20'
$DOCKER exec stirling-pdf printenv | grep -E 'UI_DEFAULT|SECURITY_ENABLE|ENDPOINTS_GROUPS' || true
grep -n 'defaultHideUnavailable\|enableLogin:' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -10
echo DONE
