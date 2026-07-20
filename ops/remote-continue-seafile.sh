#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
SEA_DIR=/home/Carmine/apps/seafile
APP=/home/Carmine/apps/gestiio-20260624-2128

# Fix unquoted library name
perl -pi -e 's/^SEAFILE_LIBRARY_NAME=.*/SEAFILE_LIBRARY_NAME="Documenti Gestiio"/' "$SEA_DIR/.env"

echo "==> containers"
$DOCKER ps -a --filter name=seafile

echo "==> wait http"
i=0
while [ $i -lt 60 ]; do
  code=$($DOCKER exec seafile curl -sf -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
  echo "try $i code=$code"
  if [ "$code" = "200" ] || [ "$code" = "302" ] || [ "$code" = "301" ]; then
    break
  fi
  i=$((i + 1))
  sleep 5
done

set -a
# shellcheck disable=SC1091
. "$SEA_DIR/.env"
set +a

export SEAFILE_ADMIN_PASSWORD SEAFILE_AGENTE_PASSWORD SEAFILE_ADMIN_EMAIL SEAFILE_AGENTE_EMAIL
export SEAFILE_PUBLIC_URL SEAFILE_LIBRARY_NAME
export DOCKER CONTAINER=seafile

sh "$SEA_DIR/seafile-bootstrap.sh" | tee /tmp/seafile-bootstrap.out
REPO_ID=$(grep '^SEAFILE_REPO_ID=' /tmp/seafile-bootstrap.out | tail -1 | cut -d= -f2)
echo "REPO_ID=$REPO_ID"
test -n "$REPO_ID"

ENV_FILE="$APP/.env"
add_env() {
  key="$1"; val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    # escape & for sed
    esc=$(printf '%s' "$val" | sed 's/[&|]/\\&/g')
    sed -i "s|^${key}=.*|${key}=${esc}|" "$ENV_FILE"
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

perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' /tmp/remote-deploy-seafile-documenti.sh
chmod +x /tmp/remote-deploy-seafile-documenti.sh
sh /tmp/remote-deploy-seafile-documenti.sh

echo "==> dry-run"
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -60

echo "==> import"
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -100

echo "==> smoke"
$DOCKER exec \
  -e SEAFILE_URL=http://seafile \
  -e SEAFILE_ADMIN_EMAIL="$SEAFILE_ADMIN_EMAIL" \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" \
  -e SEAFILE_AGENTE_EMAIL="$SEAFILE_AGENTE_EMAIL" \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" \
  -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

echo CONTINUE_DONE
