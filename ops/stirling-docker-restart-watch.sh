#!/usr/bin/env bash
# Riavvia il container quando Stirling chiede restart dalla UI Settings (in Docker non può auto-riavviarsi).
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CONTAINER_NAME=stirling-pdf
COMPOSE_DIR=/home/Carmine/apps/stirling-pdf
PATTERN='Admin initiated application restart'
LOG=/home/Carmine/apps/stirling-pdf/logs/restart-watch.log

mkdir -p "$(dirname "$LOG")"
echo "$(date -Is) watcher start" >> "$LOG"

recreate() {
  echo "$(date -Is) detected UI restart request" >> "$LOG"
  cd "$COMPOSE_DIR"
  $DOCKER compose -f docker-compose.stirling.yml up -d --force-recreate stirling-pdf >> "$LOG" 2>&1 || true
  for i in $(seq 1 60); do
    code=$($DOCKER exec stirling-pdf sh -c 'curl -sS -m 2 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/api/v1/info/status' 2>/dev/null || echo 000)
    [ "$code" = "200" ] && break
    sleep 2
  done
  $DOCKER compose -f docker-compose.stirling.yml up -d stirling-lan >> "$LOG" 2>&1 || true
  echo "$(date -Is) recreate done" >> "$LOG"
}

# Loop: dopo un recreate lo stream dei log muore — ripartiamo
while true; do
  if ! $DOCKER inspect "$CONTAINER_NAME" >/dev/null 2>&1; then
    echo "$(date -Is) container missing, waiting..." >> "$LOG"
    sleep 5
    continue
  fi
  $DOCKER logs -f --since 0s "$CONTAINER_NAME" 2>&1 | while IFS= read -r line; do
    if [[ "$line" == *"$PATTERN"* ]]; then
      recreate
      # esci dallo stream: il container è nuovo
      break
    fi
  done || true
  echo "$(date -Is) log follow ended, re-attach in 3s" >> "$LOG"
  sleep 3
done
