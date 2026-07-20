#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_GEST=/home/Carmine/apps/gestiio-20260624-2128
USER_SECRET='nd6uiXnXzpSNzLUCAw68yQypth-5qZKawjZfIATZ4FE'
ADMIN_USER=admin
ADMIN_PASS=stirling
DESKTOP='http://192.168.1.50:8091/pdf-tools'

set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$APP_GEST/.env"; then
    perl -pi -e "s|^${key}=.*|${key}=${val}|" "$APP_GEST/.env"
  else
    printf '%s=%s\n' "$key" "$val" >> "$APP_GEST/.env"
  fi
}
set_env STIRLING_URL 'http://stirling-pdf:8080'
set_env STIRLING_PUBLIC_URL 'https://gestiio.agenziaplinio.it/pdf-tools'
set_env STIRLING_DESKTOP_URL "$DESKTOP"
set_env STIRLING_ADMIN_USER "$ADMIN_USER"
set_env STIRLING_ADMIN_PASSWORD "$ADMIN_PASS"
set_env STIRLING_USER_SECRET "$USER_SECRET"
set_env STIRLING_TIMEOUT '300'

# sync compose comment on NAS
cp -f /tmp/docker-compose.stirling.yml /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml
perl -pi -e 's/\r\n/\n/g' /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml

mkdir -p "$APP_GEST/app/Http/Services" "$APP_GEST/resources/views/Backend/PdfTools"
cp -f /tmp/StirlingSsoService.php "$APP_GEST/app/Http/Services/StirlingSsoService.php"
cp -f /tmp/PdfToolsController.php "$APP_GEST/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/services.php "$APP_GEST/config/services.php"
cp -f /tmp/web-backend.php "$APP_GEST/routes/web-backend.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP_GEST/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/DEPLOY.md "$APP_GEST/DEPLOY.md"

$DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php
$DOCKER cp /tmp/web-backend.php gestiio-app:/var/www/html/routes/web-backend.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp "$APP_GEST/.env" gestiio-app:/var/www/html/.env
$DOCKER cp /tmp/DEPLOY.md gestiio-app:/var/www/html/DEPLOY.md

$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear

echo '=== LAN 8091 ==='
curl -sS -m 10 http://127.0.0.1:8091/pdf-tools/api/v1/info/status; echo

echo '=== storage ==='
grep -n -A3 '^storage:' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -6

echo '=== smoke JWT ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sso = app(App\Http\Services\StirlingSsoService::class);
$ids = App\Models\User::query()->orderBy("id")->limit(5)->pluck("id")->all();
$picked = [];
foreach ($ids as $id) {
  $u = App\Models\User::find($id);
  if (!$u) continue;
  try {
    if (method_exists($u, "hasPermissionTo") && ($u->hasPermissionTo("admin") || $u->hasPermissionTo("agente"))) {
      $picked[] = $u;
    }
  } catch (Throwable $e) {}
  if (count($picked) >= 2) break;
}
if (count($picked) < 1) {
  $picked[] = App\Models\User::find(2);
}
foreach ($picked as $u) {
  if (!$u) continue;
  try {
    $jwt = $sso->getJwtForUser($u, true);
    $c = $sso->desktopCredentials($u);
    echo "OK user={$u->id} stirling={$c["username"]} role={$c["role"]} server={$c["server_url"]} jwt=".strlen($jwt)."\n";
  } catch (Throwable $e) {
    echo "FAIL user={$u->id} ".$e->getMessage()."\n";
  }
}
'
echo DEPLOY_OK
