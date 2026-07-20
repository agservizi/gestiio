#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_STIR=/home/Carmine/apps/stirling-pdf
APP_GEST=/home/Carmine/apps/gestiio-20260624-2128
PASS='uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL'
USER='gestiio'

# Stirling .env
cat > "$APP_STIR/.env" <<EOF
STIRLING_ADMIN_USER=$USER
STIRLING_ADMIN_PASSWORD=$PASS
EOF
chmod 600 "$APP_STIR/.env"

# Patch settings.yml enableLogin true + initialLogin
python3 - <<'PY'
from pathlib import Path
p = Path('/home/Carmine/apps/stirling-pdf/configs/settings.yml')
t = p.read_text(encoding='utf-8')
repls = [
    ("enableLogin: false # set to 'true' to enable login",
     "enableLogin: true # set to 'true' to enable login"),
    ('username: "" # initial username for the first login',
     'username: "gestiio" # initial username for the first login'),
    ('password: "" # initial password for the first login',
     'password: "uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL" # initial password for the first login'),
]
out = t
for a,b in repls:
    out = out.replace(a,b)
p.write_text(out, encoding='utf-8')
print('settings patched enableLogin=', 'enableLogin: true' in out)
PY

# Copy compose
cp -f /tmp/docker-compose.stirling.yml "$APP_STIR/docker-compose.stirling.yml"
perl -pi -e 's/\r\n/\n/g' "$APP_STIR/docker-compose.stirling.yml"

# Recreate stirling
cd "$APP_STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d
echo 'Waiting for healthy...'
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "  $i $st"
  [ "$st" = "healthy" ] && break
  sleep 3
done

# Ensure gestiio .env has credentials
if ! grep -q '^STIRLING_ADMIN_PASSWORD=' "$APP_GEST/.env" 2>/dev/null; then
  printf '\nSTIRLING_URL=http://stirling-pdf:8080\nSTIRLING_PUBLIC_URL=https://gestiio.agenziaplinio.it/pdf-tools\nSTIRLING_ADMIN_USER=%s\nSTIRLING_ADMIN_PASSWORD=%s\n' "$USER" "$PASS" >> "$APP_GEST/.env"
  echo 'APPENDED gestiio .env'
else
  # update password line
  perl -pi -e "s/^STIRLING_ADMIN_USER=.*/STIRLING_ADMIN_USER=$USER/" "$APP_GEST/.env"
  perl -pi -e "s/^STIRLING_ADMIN_PASSWORD=.*/STIRLING_ADMIN_PASSWORD=$PASS/" "$APP_GEST/.env"
  grep -q '^STIRLING_ADMIN_USER=' "$APP_GEST/.env" || echo "STIRLING_ADMIN_USER=$USER" >> "$APP_GEST/.env"
  grep -q '^STIRLING_ADMIN_PASSWORD=' "$APP_GEST/.env" || echo "STIRLING_ADMIN_PASSWORD=$PASS" >> "$APP_GEST/.env"
  echo 'UPDATED gestiio .env'
fi

# Sync .env into container + code
$DOCKER cp "$APP_GEST/.env" gestiio-app:/var/www/html/.env
for f in StirlingSsoService.php PdfToolsController.php services.php web-backend.php PdfToolsIndex.blade.php; do
  :
done

# Deploy PHP/views (uploaded to /tmp)
mkdir -p "$APP_GEST/app/Http/Services"
cp -f /tmp/StirlingSsoService.php "$APP_GEST/app/Http/Services/StirlingSsoService.php"
cp -f /tmp/PdfToolsController.php "$APP_GEST/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/services.php "$APP_GEST/config/services.php"
cp -f /tmp/web-backend.php "$APP_GEST/routes/web-backend.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP_GEST/resources/views/Backend/PdfTools/index.blade.php"

$DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php
$DOCKER cp /tmp/web-backend.php gestiio-app:/var/www/html/routes/web-backend.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php

$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear

echo '=== login probe ==='
sleep 5
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  $jwt = app(App\Http\Services\StirlingSsoService::class)->getServiceJwt(true);
  echo "JWT_OK len=".strlen($jwt)."\n";
} catch (Throwable $e) {
  echo "JWT_FAIL ".$e->getMessage()."\n";
}
'

echo '=== env check ==='
$DOCKER exec stirling-pdf printenv | grep -E 'SECURITY_ENABLE|SECURITY_INITIAL|DISABLE_ADD'
echo DEPLOY_OK
