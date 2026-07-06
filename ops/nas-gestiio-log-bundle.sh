#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
OUT_DIR=/Volume1/homes/Carmine/gestiio-log-bundles
bundle_id=$(date "+%Y%m%d-%H%M%S")
work="$OUT_DIR/$bundle_id"
mkdir -p "$work"

cp /home/Carmine/gestiio-healthcheck.log "$work/" 2>/dev/null || true
cp /home/Carmine/gestiio-watchdog.log "$work/" 2>/dev/null || true
cp /home/Carmine/gestiio-backup.log "$work/" 2>/dev/null || true
cp /home/Carmine/gestiio-audit.log "$work/" 2>/dev/null || true
cp /home/Carmine/gestiio-alert.log "$work/" 2>/dev/null || true
cp /home/Carmine/start-all-docker-containers.log "$work/" 2>/dev/null || true

for container in gestiio-db gestiio-app corehost_traefik cloudflared_corehost; do
    "$DOCKER" logs --tail 500 "$container" > "$work/docker-$container.log" 2>&1 || true
done

"$DOCKER" ps -a --format "{{.Names}}|{{.Status}}" > "$work/docker-ps.txt" 2>&1 || true
df -h > "$work/df-h.txt" 2>&1 || true
free -h > "$work/free-h.txt" 2>&1 || true

tar -C "$OUT_DIR" -czf "$OUT_DIR/gestiio-logs-$bundle_id.tar.gz" "$bundle_id"
rm -rf "$work"
find "$OUT_DIR" -maxdepth 1 -name "gestiio-logs-*.tar.gz" -mtime +14 -delete 2>/dev/null || true

echo "$OUT_DIR/gestiio-logs-$bundle_id.tar.gz"
