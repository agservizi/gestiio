#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CFG=/home/Carmine/apps/stirling-pdf/configs/settings.yml

cp -a "$CFG" "${CFG}.bak-locale-$(date +%Y%m%d%H%M%S)"

# defaultLocale with hyphen; languages empty list = all available
python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
out = []
for line in text.splitlines(True):
    if line.lstrip().startswith("defaultLocale:"):
        indent = line[: len(line) - len(line.lstrip())]
        out.append(f'{indent}defaultLocale: it-IT # force Italian\n')
    elif line.lstrip().startswith("languages:"):
        indent = line[: len(line) - len(line.lstrip())]
        out.append(f'{indent}languages: [] # all languages; defaultLocale forces it-IT\n')
    else:
        out.append(line)
p.write_text("".join(out), encoding="utf-8")
print("patched")
PY

grep -nE 'defaultLocale|languages:' "$CFG" | head -5

# Recreate stirling
COMPOSE=/home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml
if [ -f "$COMPOSE" ]; then
  cd /home/Carmine/apps/stirling-pdf
  $DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf
else
  $DOCKER restart stirling-pdf
fi

for i in $(seq 1 30); do
  code=$($DOCKER exec stirling-pdf sh -c 'curl -sS -m 2 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/api/v1/info/status' || echo 000)
  echo "status=$code"
  [ "$code" = "200" ] && break
  sleep 2
done

$DOCKER exec stirling-pdf sh -c 'find /app -iname "*messages*it*" 2>/dev/null | head -20; find / -path "*i18n*" -iname "*it*" 2>/dev/null | head -20'
echo DONE
