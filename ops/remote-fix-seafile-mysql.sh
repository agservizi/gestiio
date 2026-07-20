#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
SEA_DIR=/home/Carmine/apps/seafile
PASS='SfRootGestiio2026Secure'

cd "$SEA_DIR"
$DOCKER compose -f docker-compose.seafile.yml down || true
rm -rf "$SEA_DIR/mysql" "$SEA_DIR/data"
mkdir -p "$SEA_DIR/mysql" "$SEA_DIR/data"

# Password fissa ASCII (niente CR / interpolazione ambigua)
ADMIN_PASS=$(grep '^SEAFILE_ADMIN_PASSWORD=' "$SEA_DIR/.env" 2>/dev/null | cut -d= -f2- | tr -d '\r\n' || true)
AGENTE_PASS=$(grep '^SEAFILE_AGENTE_PASSWORD=' "$SEA_DIR/.env" 2>/dev/null | cut -d= -f2- | tr -d '\r\n' || true)
[ -n "$ADMIN_PASS" ] || ADMIN_PASS='SfAdminGestiio2026'
[ -n "$AGENTE_PASS" ] || AGENTE_PASS='SfAgenteRoGestiio2026'

printf '%s\n' \
  "SEAFILE_MYSQL_ROOT_PASSWORD=$PASS" \
  "SEAFILE_ADMIN_EMAIL=admin@gestiio.local" \
  "SEAFILE_ADMIN_PASSWORD=$ADMIN_PASS" \
  "SEAFILE_SERVER_HOSTNAME=documenti.agenziaplinio.it" \
  "SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local" \
  "SEAFILE_AGENTE_PASSWORD=$AGENTE_PASS" \
  "SEAFILE_PUBLIC_URL=https://documenti.agenziaplinio.it" \
  "SEAFILE_LIBRARY_NAME=Documenti_Gestiio" \
  > "$SEA_DIR/.env"

# Hardcode anche nel compose runtime via export
export SEAFILE_MYSQL_ROOT_PASSWORD="$PASS"
export SEAFILE_ADMIN_EMAIL=admin@gestiio.local
export SEAFILE_ADMIN_PASSWORD="$ADMIN_PASS"
export SEAFILE_SERVER_HOSTNAME=documenti.agenziaplinio.it

$DOCKER compose -f docker-compose.seafile.yml up -d

echo "MySQL env check:"
$DOCKER exec seafile-mysql printenv MYSQL_ROOT_PASSWORD | od -c | head -3
echo "Seafile DB_ROOT_PASSWD:"
$DOCKER exec seafile printenv DB_ROOT_PASSWD | od -c | head -3

i=0
while [ $i -lt 60 ]; do
  if $DOCKER exec seafile-mysql mariadb-admin ping -h127.0.0.1 -uroot -p"$PASS" --silent 2>/dev/null; then
    echo MYSQL_AUTH_OK
    break
  fi
  echo "mysql wait $i"
  i=$((i+1)); sleep 3
done

# Force grant from inside mysql if needed
$DOCKER exec seafile-mysql mariadb -uroot -p"$PASS" -e "CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '$PASS'; GRANT ALL ON *.* TO 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null || true

$DOCKER restart seafile
sleep 20

i=0
while [ $i -lt 60 ]; do
  code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
  echo "http try=$i code=$code"
  case "$code" in 200|301|302) break ;; esac
  if [ $((i % 5)) -eq 0 ]; then $DOCKER logs seafile --tail 3 2>&1 | tail -2; fi
  i=$((i+1)); sleep 5
done

code=$($DOCKER exec seafile curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ 2>/dev/null || echo 000)
echo final=$code
[ "$code" = "200" ] || [ "$code" = "301" ] || [ "$code" = "302" ] || { $DOCKER logs seafile --tail 50; exit 1; }

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
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -50
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -100

$DOCKER exec -e SEAFILE_URL=http://seafile -e SEAFILE_ADMIN_EMAIL=admin@gestiio.local \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" -e SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

echo FIXED_DONE
