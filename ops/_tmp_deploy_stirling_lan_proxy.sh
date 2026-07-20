#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_STIR=/home/Carmine/apps/stirling-pdf
APP_GEST=/home/Carmine/apps/gestiio-20260624-2128

cp -f /tmp/docker-compose.stirling.yml "$APP_STIR/docker-compose.stirling.yml"
cp -f /tmp/stirling-lan-nginx.conf "$APP_STIR/stirling-lan-nginx.conf"
perl -pi -e 's/\r\n/\n/g' "$APP_STIR/docker-compose.stirling.yml" "$APP_STIR/stirling-lan-nginx.conf"

# Update Gestiio desktop URL
if grep -q '^STIRLING_DESKTOP_URL=' "$APP_GEST/.env"; then
  perl -pi -e 's|^STIRLING_DESKTOP_URL=.*|STIRLING_DESKTOP_URL=http://192.168.1.50:8092|' "$APP_GEST/.env"
else
  echo 'STIRLING_DESKTOP_URL=http://192.168.1.50:8092' >> "$APP_GEST/.env"
fi

cp -f /tmp/services.php "$APP_GEST/config/services.php"
cp -f /tmp/StirlingSsoService.php "$APP_GEST/app/Http/Services/StirlingSsoService.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP_GEST/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/DEPLOY.md "$APP_GEST/DEPLOY.md" 2>/dev/null || true

$DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php
$DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp "$APP_GEST/.env" gestiio-app:/var/www/html/.env

cd "$APP_STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d

for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  lan=$($DOCKER inspect -f '{{.State.Status}}' stirling-lan 2>/dev/null || echo missing)
  echo "stirling=$st lan=$lan"
  [ "$st" = "healthy" ] && [ "$lan" = "running" ] && break
  sleep 2
done

echo '=== smoke LAN desktop proxy 8092 ==='
curl -sS -m 10 http://127.0.0.1:8092/api/v1/info/status; echo
curl -sS -m 5 -o /dev/null -w '8092_root=%{http_code}\n' http://127.0.0.1:8092/
curl -sS -m 5 -o /dev/null -w '8091_pdftools_status=%{http_code}\n' http://127.0.0.1:8091/pdf-tools/api/v1/info/status

$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
echo DESKTOP_URL_OK
