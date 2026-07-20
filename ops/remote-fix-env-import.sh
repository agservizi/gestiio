#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
ENV_FILE=$APP/.env
SEA_DIR=/home/Carmine/apps/seafile

# Backup broken env
cp -f "$ENV_FILE" "$ENV_FILE.bak-seafile-$(date +%H%M%S)"

# Remove broken SEAFILE_* lines and garbage
perl -ni -e 'print unless /^(SEAFILE_|n#)/' "$ENV_FILE"
# Also drop empty weird lines that start with invalid keys
perl -ni -e 'print if /^(?:[A-Za-z_][A-Za-z0-9_]*=|$|#)/' "$ENV_FILE"

set -a
. "$SEA_DIR/.env"
set +a
REPO_ID=4de78a43-320b-4c63-a1c1-ea013f8ba66b

printf '\n%s\n' \
  "SEAFILE_URL=http://seafile" \
  "SEAFILE_PUBLIC_URL=https://documenti.agenziaplinio.it" \
  "SEAFILE_ADMIN_EMAIL=admin@gestiio.local" \
  "SEAFILE_ADMIN_PASSWORD=$SEAFILE_ADMIN_PASSWORD" \
  "SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local" \
  "SEAFILE_AGENTE_PASSWORD=$SEAFILE_AGENTE_PASSWORD" \
  "SEAFILE_REPO_ID=$REPO_ID" \
  "SEAFILE_TIMEOUT=300" \
  >> "$ENV_FILE"

perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' "$ENV_FILE"
$DOCKER cp "$ENV_FILE" gestiio-app:/var/www/html/.env

$DOCKER exec gestiio-app php -r 'echo "env_ok\n";'
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan migrate --force --path=database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php
$DOCKER restart gestiio-app
sleep 8

echo "== dry-run =="
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -80
echo "== import =="
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -150
echo "== smoke =="
$DOCKER exec \
  -e SEAFILE_URL=http://seafile \
  -e SEAFILE_ADMIN_EMAIL=admin@gestiio.local \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" \
  -e SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" \
  -e SEAFILE_REPO_ID="$REPO_ID" \
  gestiio-app php /tmp/smoke-seafile-documenti.php || true

$DOCKER exec gestiio-app php /var/www/html/artisan tinker --execute="echo 'files='.\\App\\Models\\File::count().' imported='.\\App\\Models\\File::whereNotNull('seafile_path')->count().PHP_EOL;"

echo ENV_FIXED_OK
