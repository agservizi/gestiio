#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
SEA_DIR=/home/Carmine/apps/seafile
APP=/home/Carmine/apps/gestiio-20260624-2128

mkdir -p "$SEA_DIR/data" "$SEA_DIR/mysql"
cd /tmp
rm -rf seafile-stack
mkdir seafile-stack && cd seafile-stack
tar -xf /tmp/seafile-stack.tar
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' ops/docker-compose.seafile.yml ops/seafile-bootstrap.sh
cp -f ops/docker-compose.seafile.yml "$SEA_DIR/docker-compose.seafile.yml"
cp -f ops/seafile-bootstrap.sh "$SEA_DIR/seafile-bootstrap.sh"
cp -f /tmp/seafile.env "$SEA_DIR/.env"
chmod +x "$SEA_DIR/seafile-bootstrap.sh"
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' "$SEA_DIR/.env" "$SEA_DIR/seafile-bootstrap.sh"

# Backup gestiio hardlink
cp -al "$APP" "/home/Carmine/apps/gestiio-backup-$(date +%Y%m%d-%H%M%S)-pre-seafile" || true

cd "$SEA_DIR"
$DOCKER compose -f docker-compose.seafile.yml pull || true
$DOCKER compose -f docker-compose.seafile.yml up -d

echo "Waiting for seafile container..."
i=0
while [ $i -lt 90 ]; do
  if $DOCKER ps --filter name=seafile --format '{{.Status}}' | grep -qi up; then
    if $DOCKER exec seafile curl -sf http://127.0.0.1/ >/dev/null 2>&1; then
      echo SEAFILE_HTTP_OK
      break
    fi
  fi
  i=$((i+1))
  sleep 5
done

# Export env for bootstrap
set -a
. "$SEA_DIR/.env"
set +a
export SEAFILE_ADMIN_PASSWORD SEAFILE_AGENTE_PASSWORD SEAFILE_ADMIN_EMAIL SEAFILE_AGENTE_EMAIL SEAFILE_PUBLIC_URL SEAFILE_LIBRARY_NAME
sh "$SEA_DIR/seafile-bootstrap.sh" | tee /tmp/seafile-bootstrap.out

REPO_ID=$(grep '^SEAFILE_REPO_ID=' /tmp/seafile-bootstrap.out | tail -1 | cut -d= -f2)
echo "REPO_ID=$REPO_ID"

# Inject into gestiio .env (host + container)
ENV_FILE="$APP/.env"
add_env() {
  key="$1"; val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}
add_env SEAFILE_URL http://seafile
add_env SEAFILE_PUBLIC_URL "$SEAFILE_PUBLIC_URL"
add_env SEAFILE_ADMIN_EMAIL "$SEAFILE_ADMIN_EMAIL"
add_env SEAFILE_ADMIN_PASSWORD "$SEAFILE_ADMIN_PASSWORD"
add_env SEAFILE_AGENTE_EMAIL "$SEAFILE_AGENTE_EMAIL"
add_env SEAFILE_AGENTE_PASSWORD "$SEAFILE_AGENTE_PASSWORD"
add_env SEAFILE_REPO_ID "$REPO_ID"
add_env SEAFILE_TIMEOUT 300

$DOCKER cp "$ENV_FILE" gestiio-app:/var/www/html/.env

# Deploy PHP code
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' /tmp/remote-deploy-seafile-documenti.sh
chmod +x /tmp/remote-deploy-seafile-documenti.sh
sh /tmp/remote-deploy-seafile-documenti.sh

echo "Dry-run import..."
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -50

echo "Real import (preserves local files)..."
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -80

echo "Smoke..."
$DOCKER exec \
  -e SEAFILE_URL=http://seafile \
  -e SEAFILE_ADMIN_EMAIL="$SEAFILE_ADMIN_EMAIL" \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" \
  -e SEAFILE_AGENTE_EMAIL="$SEAFILE_AGENTE_EMAIL" \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" \
  -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

echo ALL_DONE
