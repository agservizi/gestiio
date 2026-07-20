#!/bin/bash
set -euo pipefail
CFG=/home/Carmine/apps/stirling-pdf/configs
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
BAK="$CFG/settings.yml.bak-locale-20260717200503"

# Snapshot current before restore
cp -a "$CFG/settings.yml" "$CFG/settings.yml.bak-before-restore-$(date +%Y%m%d%H%M%S)"

python3 - <<'PY'
from pathlib import Path
p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
out = []
for line in text.splitlines(True):
    stripped = line.lstrip()
    indent = line[: len(line) - len(stripped)]
    if stripped.startswith("defaultLocale:"):
        # Formato corretto (trattino). Stamattina era "" (browser IT);
        # forziamo it-IT cosi' non dipende dal browser/localStorage.
        out.append(f'{indent}defaultLocale: "it-IT"\n')
    elif stripped.startswith("languages:"):
        # Come stamattina: tutte le lingue abilitate
        out.append(f'{indent}languages: []\n')
    else:
        out.append(line)
p.write_text("".join(out), encoding="utf-8")
print("settings patched")
PY

echo '=== after patch ==='
grep -nE 'defaultLocale|languages:' "$CFG/settings.yml"

# Assicura env compose
grep -n 'SYSTEM_DEFAULTLOCALE' /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml

cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf

for i in $(seq 1 40); do
  code=$($DOCKER exec stirling-pdf sh -c 'curl -sS -m 2 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/api/v1/info/status' || echo 000)
  echo "status=$code"
  [ "$code" = "200" ] && break
  sleep 2
done

echo '=== settings AFTER container start (Stirling may rewrite) ==='
grep -nE 'defaultLocale|languages:' "$CFG/settings.yml"

echo '=== morning bak for reference ==='
grep -nE 'defaultLocale|languages:' "$BAK"

echo DONE
