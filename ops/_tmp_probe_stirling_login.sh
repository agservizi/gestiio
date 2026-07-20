#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PASS='uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL'

echo '=== login attempts ==='
for user in gestiio admin Admin administrator; do
  code=$($DOCKER exec gestiio-app curl -sS -o /tmp/login.json -w '%{http_code}' \
    -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"username\":\"$user\",\"password\":\"$PASS\"}" \
    http://stirling-pdf:8080/pdf-tools/api/v1/auth/login || echo err)
  echo "user=$user http=$code body=$(head -c 200 /tmp/login.json 2>/dev/null || $DOCKER exec gestiio-app head -c 200 /tmp/login.json)"
done

echo '=== stirling logs (auth) ==='
$DOCKER logs stirling-pdf 2>&1 | grep -iE 'user|admin|initial|login|created|password' | tail -40

echo '=== db files ==='
ls -la /home/Carmine/apps/stirling-pdf/configs/*.mv.db /home/Carmine/apps/stirling-pdf/configs/*.db 2>/dev/null || true
