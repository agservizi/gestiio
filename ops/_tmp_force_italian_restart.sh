#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
YML=/home/Carmine/apps/stirling-pdf/configs/settings.yml

cp -a "$YML" "$YML.bak-it-$(date +%Y%m%d%H%M%S)"

# Force Italian defaults (settings.yml — override UI save)
perl -pi -e 's/^(\s*defaultLocale:\s*).*$/${1}"it-IT"/' "$YML"
perl -pi -e 's/^(\s*languages:\s*).*$/${1}["it_IT"]/' "$YML"
perl -pi -e 's/^(\s*enableAnalytics:\s*).*$/${1}false/' "$YML"
perl -pi -e 's/^(\s*enablePosthog:\s*).*$/${1}false/' "$YML"
perl -pi -e 's/^(\s*enableScarf:\s*).*$/${1}false/' "$YML"

echo '=== locale config ==='
grep -n 'defaultLocale\|languages:\|enableAnalytics' "$YML" | head -10

cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "health=$st"
  [ "$st" = "healthy" ] && break
  sleep 2
done
# Keep LAN proxy up
$DOCKER compose -f docker-compose.stirling.yml up -d stirling-lan
curl -sS -m 10 http://127.0.0.1:8091/pdf-tools/api/v1/info/status; echo
curl -sS -m 10 http://127.0.0.1:8092/api/v1/info/status; echo
echo RESTART_OK
