#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
SEA_DIR=/home/Carmine/apps/seafile
PASS=SfRootGestiio2026Secure

cd /tmp
rm -rf scf2 && mkdir scf2 && cd scf2
tar -xf /tmp/seafile-compose-fix2.tar
perl -pi -e 's/\r\n/\n/g' ops/docker-compose.seafile.yml
cp ops/docker-compose.seafile.yml "$SEA_DIR/docker-compose.seafile.yml"

cd "$SEA_DIR"
$DOCKER compose -f docker-compose.seafile.yml up -d --force-recreate seafile

echo "Wait HTTP..."
i=0
while [ $i -lt 60 ]; do
  code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
  echo "try=$i code=$code"
  case "$code" in 200|301|302) break ;; esac
  if [ $((i % 5)) -eq 0 ]; then $DOCKER logs seafile --tail 4 2>&1 | tail -3; fi
  i=$((i+1)); sleep 5
done
code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
echo final=$code
[ "$code" = "200" ] || [ "$code" = "301" ] || [ "$code" = "302" ] || { $DOCKER logs seafile --tail 40; exit 1; }

set -a
. "$SEA_DIR/.env"
set +a
export SEAFILE_LIBRARY_NAME=Documenti_Gestiio DOCKER
SEAFILE_LIBRARY_NAME=Documenti_Gestiio sh "$SEA_DIR/seafile-bootstrap.sh" | tee /tmp/seafile-bootstrap.out
REPO_ID=$(grep '^SEAFILE_REPO_ID=' /tmp/seafile-bootstrap.out | tail -1 | cut -d= -f2)
echo REPO_ID=$REPO_ID
test -n "$REPO_ID"

APP=/home/Carmine/apps/gestiio-20260624-2128
ENV_FILE=$APP/.env
add_env(){ k=$1;v=$2; grep -q "^$k=" "$ENV_FILE" 2>/dev/null && sed -i "s|^$k=.*|$k=$v|" "$ENV_FILE" || printf '\n%s=%s\n' "$k" "$v" >> "$ENV_FILE"; }
add_env SEAFILE_URL http://seafile
add_env SEAFILE_PUBLIC_URL https://documenti.agenziaplinio.it
add_env SEAFILE_ADMIN_EMAIL admin@gestiio.local
add_env SEAFILE_ADMIN_PASSWORD "$SEAFILE_ADMIN_PASSWORD"
add_env SEAFILE_AGENTE_EMAIL agente-ro@gestiio.local
add_env SEAFILE_AGENTE_PASSWORD "$SEAFILE_AGENTE_PASSWORD"
add_env SEAFILE_REPO_ID "$REPO_ID"
add_env SEAFILE_TIMEOUT 300
$DOCKER cp "$ENV_FILE" gestiio-app:/var/www/html/.env

[ -f /tmp/remote-deploy-seafile-documenti.sh ] && sh /tmp/remote-deploy-seafile-documenti.sh

$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -60
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -120

$DOCKER exec -e SEAFILE_URL=http://seafile -e SEAFILE_ADMIN_EMAIL=admin@gestiio.local \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" -e SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

echo DBHOST_FIX_DONE
