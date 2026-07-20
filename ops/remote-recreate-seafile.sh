#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
SEA_DIR=/home/Carmine/apps/seafile

cd "$SEA_DIR"
$DOCKER compose -f docker-compose.seafile.yml down || true

# Solo dati Seafile (NON tocca gestiio storage)
rm -rf "$SEA_DIR/mysql" "$SEA_DIR/data"
mkdir -p "$SEA_DIR/mysql" "$SEA_DIR/data"

# Rigenera .env pulito LF senza spazi nel library name
ADMIN_PASS=$(grep '^SEAFILE_ADMIN_PASSWORD=' "$SEA_DIR/.env" | cut -d= -f2- | tr -d '\r')
AGENTE_PASS=$(grep '^SEAFILE_AGENTE_PASSWORD=' "$SEA_DIR/.env" | cut -d= -f2- | tr -d '\r')
MYSQL_PASS=$(grep '^SEAFILE_MYSQL_ROOT_PASSWORD=' "$SEA_DIR/.env" | cut -d= -f2- | tr -d '\r')

# Se password contaminate da CR, rigenera
case "$MYSQL_PASS" in *[!A-Za-z0-9]*) MYSQL_PASS=$(head -c 24 /dev/urandom | base64 | tr -dc A-Za-z0-9 | head -c 24);; esac

cat > "$SEA_DIR/.env" <<EOF
SEAFILE_MYSQL_ROOT_PASSWORD=$MYSQL_PASS
SEAFILE_ADMIN_EMAIL=admin@gestiio.local
SEAFILE_ADMIN_PASSWORD=$ADMIN_PASS
SEAFILE_SERVER_HOSTNAME=documenti.agenziaplinio.it
SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local
SEAFILE_AGENTE_PASSWORD=$AGENTE_PASS
SEAFILE_PUBLIC_URL=https://documenti.agenziaplinio.it
SEAFILE_LIBRARY_NAME=Documenti_Gestiio
EOF
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' "$SEA_DIR/.env"

$DOCKER compose -f docker-compose.seafile.yml up -d

echo "Waiting Seafile HTTP..."
i=0
while [ $i -lt 90 ]; do
  code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
  echo "try=$i code=$code"
  case "$code" in
    200|301|302) break ;;
  esac
  # log hint
  if [ $((i % 6)) -eq 0 ]; then
    $DOCKER logs seafile --tail 5 2>&1 | tail -3
  fi
  i=$((i + 1))
  sleep 5
done

code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
echo "final_code=$code"
if [ "$code" != "200" ] && [ "$code" != "301" ] && [ "$code" != "302" ]; then
  echo SEAFILE_STILL_DOWN
  $DOCKER logs seafile --tail 40
  exit 1
fi

set -a
. "$SEA_DIR/.env"
set +a
export SEAFILE_ADMIN_PASSWORD SEAFILE_AGENTE_PASSWORD SEAFILE_ADMIN_EMAIL SEAFILE_AGENTE_EMAIL
export SEAFILE_PUBLIC_URL SEAFILE_LIBRARY_NAME=Documenti_Gestiio
export DOCKER

# Patch bootstrap library name default
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' "$SEA_DIR/seafile-bootstrap.sh"
SEAFILE_LIBRARY_NAME=Documenti_Gestiio sh "$SEA_DIR/seafile-bootstrap.sh" | tee /tmp/seafile-bootstrap.out

REPO_ID=$(grep '^SEAFILE_REPO_ID=' /tmp/seafile-bootstrap.out | tail -1 | cut -d= -f2)
echo "REPO_ID=$REPO_ID"
test -n "$REPO_ID"

APP=/home/Carmine/apps/gestiio-20260624-2128
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
add_env SEAFILE_PUBLIC_URL https://documenti.agenziaplinio.it
add_env SEAFILE_ADMIN_EMAIL admin@gestiio.local
add_env SEAFILE_ADMIN_PASSWORD "$SEAFILE_ADMIN_PASSWORD"
add_env SEAFILE_AGENTE_EMAIL agente-ro@gestiio.local
add_env SEAFILE_AGENTE_PASSWORD "$SEAFILE_AGENTE_PASSWORD"
add_env SEAFILE_REPO_ID "$REPO_ID"
add_env SEAFILE_TIMEOUT 300
perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' "$ENV_FILE"

$DOCKER cp "$ENV_FILE" gestiio-app:/var/www/html/.env

# Deploy code if tar present
if [ -f /tmp/remote-deploy-seafile-documenti.sh ]; then
  perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' /tmp/remote-deploy-seafile-documenti.sh
  sh /tmp/remote-deploy-seafile-documenti.sh
fi

$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -40
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -80

$DOCKER exec \
  -e SEAFILE_URL=http://seafile \
  -e SEAFILE_ADMIN_EMAIL=admin@gestiio.local \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" \
  -e SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" \
  -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

echo RECREATE_DONE
