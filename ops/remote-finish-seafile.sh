#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
CONTAINER=gestiio-app
SEA_DIR=/home/Carmine/apps/seafile

# Locale IT su seahub_settings
SETTINGS=/home/Carmine/apps/seafile/data/seafile/conf/seahub_settings.py
if [ -f "$SETTINGS" ]; then
  if ! grep -q "LANGUAGE_CODE = 'it'" "$SETTINGS"; then
    cat >> "$SETTINGS" <<'EOF'

# --- Gestiio: italiano obbligatorio ---
LANGUAGE_CODE = 'it'
TIME_ZONE = 'Europe/Rome'
LANGUAGES = (
    ('it', 'Italiano'),
)
SITE_NAME = 'Documenti'
SITE_TITLE = 'Documenti Gestiio'
SHARE_LINK_EMAIL_LANGUAGE = 'it'
ENABLE_SETTINGS_VIA_WEB = False
EOF
  fi
  # SERVICE_URL
  if ! grep -q "SERVICE_URL" "$SETTINGS"; then
    echo "SERVICE_URL = 'https://documenti.agenziaplinio.it'" >> "$SETTINGS"
    echo "FILE_SERVER_ROOT = 'https://documenti.agenziaplinio.it/seafhttp'" >> "$SETTINGS"
  fi
  $DOCKER restart seafile
  sleep 15
fi

# Deploy codice da tar
cd /tmp
rm -rf seafile-doc-deploy
mkdir seafile-doc-deploy
cd seafile-doc-deploy
tar -xf /tmp/seafile-doc-deploy.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

$DOCKER exec "$CONTAINER" mkdir -p /var/www/html/resources/views/Backend/Documenti /var/www/html/app/Console/Commands
$DOCKER cp app/Http/Services/SeafileClient.php "$CONTAINER:/var/www/html/app/Http/Services/SeafileClient.php"
$DOCKER cp app/Http/Controllers/Backend/SeafileDocumentiController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php"
$DOCKER cp app/Console/Commands/ImportDocumentiToSeafile.php "$CONTAINER:/var/www/html/app/Console/Commands/ImportDocumentiToSeafile.php"
$DOCKER cp config/services.php "$CONTAINER:/var/www/html/config/services.php"
$DOCKER cp routes/web-backend.php "$CONTAINER:/var/www/html/routes/web-backend.php"
$DOCKER cp resources/views/Backend/Documenti/seafile.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/Documenti/seafile.blade.php"
$DOCKER cp resources/views/Backend/_layout/app-sidebar-menu.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php"
$DOCKER cp database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php "$CONTAINER:/var/www/html/database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php"
$DOCKER cp ops/smoke-seafile-documenti.php "$CONTAINER:/tmp/smoke-seafile-documenti.php"
cp -f app/Http/Services/SeafileClient.php "$APP/app/Http/Services/"
cp -f app/Http/Controllers/Backend/SeafileDocumentiController.php "$APP/app/Http/Controllers/Backend/"
cp -f app/Console/Commands/ImportDocumentiToSeafile.php "$APP/app/Console/Commands/"
cp -f config/services.php "$APP/config/"
cp -f routes/web-backend.php "$APP/routes/"
mkdir -p "$APP/resources/views/Backend/Documenti"
cp -f resources/views/Backend/Documenti/seafile.blade.php "$APP/resources/views/Backend/Documenti/"
cp -f resources/views/Backend/_layout/app-sidebar-menu.blade.php "$APP/resources/views/Backend/_layout/"
cp -f database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php "$APP/database/migrations/"

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Services/SeafileClient.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan migrate --force --path=database/migrations/2026_07_16_220000_add_seafile_path_to_files_table.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan config:clear
$DOCKER exec "$CONTAINER" php /var/www/html/artisan route:clear
$DOCKER restart "$CONTAINER"
sleep 8

set -a
. "$SEA_DIR/.env"
set +a
REPO_ID=$(grep '^SEAFILE_REPO_ID=' "$APP/.env" | cut -d= -f2 | tr -d '\r')
[ -n "$REPO_ID" ] || REPO_ID=4de78a43-320b-4c63-a1c1-ea013f8ba66b

echo "== dry-run =="
$DOCKER exec "$CONTAINER" php /var/www/html/artisan documenti:import-seafile --dry-run | tee /tmp/seafile-import-dry.out | tail -80

echo "== import =="
$DOCKER exec "$CONTAINER" php /var/www/html/artisan documenti:import-seafile | tee /tmp/seafile-import.out | tail -120

echo "== smoke =="
$DOCKER exec \
  -e SEAFILE_URL=http://seafile \
  -e SEAFILE_ADMIN_EMAIL=admin@gestiio.local \
  -e SEAFILE_ADMIN_PASSWORD="$SEAFILE_ADMIN_PASSWORD" \
  -e SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local \
  -e SEAFILE_AGENTE_PASSWORD="$SEAFILE_AGENTE_PASSWORD" \
  -e SEAFILE_REPO_ID="$REPO_ID" \
  "$CONTAINER" php /tmp/smoke-seafile-documenti.php || true

echo "== counts =="
$DOCKER exec "$CONTAINER" php /var/www/html/artisan tinker --execute="echo 'files='.\\App\\Models\\File::count().' imported='.\\App\\Models\\File::whereNotNull('seafile_path')->count().PHP_EOL;"

echo FINISH_OK
