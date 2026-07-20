#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== who uses 8090 ==='
$DOCKER ps --format 'table {{.Names}}\t{{.Ports}}' | grep -E '8090|8091|stirling' || true
ss -tlnp 2>/dev/null | grep -E ':8090|:8091' || netstat -tlnp 2>/dev/null | grep -E ':8090|:8091' || true

# Prefer free port: try 8091 if 8090 busy
PORT=8090
if ss -tln 2>/dev/null | grep -q ':8090 ' || netstat -tln 2>/dev/null | grep -q ':8090 '; then
  PORT=8091
fi
# Also check docker
if $DOCKER ps --format '{{.Ports}}' | grep -q '0.0.0.0:8090'; then
  PORT=8091
fi
echo "CHOSEN_PORT=$PORT"

# Fix compose to use chosen port
perl -pi -e "s/\"8090:8080\"/\"$PORT:8080\"/" /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml
grep -n 'ports\|809' /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml | head -10

cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "  $i $st"
  [ "$st" = "healthy" ] && break
  sleep 3
done

echo "PORT=$PORT" > /tmp/stirling_lan_port.txt
curl -sS -m 10 "http://127.0.0.1:$PORT/pdf-tools/api/v1/info/status" || echo LAN_FAIL
echo
$DOCKER port stirling-pdf
