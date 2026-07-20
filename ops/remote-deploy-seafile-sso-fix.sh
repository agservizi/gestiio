#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
CONTAINER=gestiio-app
SETTINGS=/home/Carmine/apps/seafile/data/seafile/conf/seahub_settings.py

cd /tmp
rm -rf sea-sso2 && mkdir sea-sso2 && cd sea-sso2
tar -xf /tmp/seafile-sso2.tar
find . -type f -exec perl -pi -e 's/\r\n/\n/g; s/\r/\n/g' {} +

cp -f app/Http/Services/SeafileClient.php "$APP/app/Http/Services/"
cp -f app/Http/Controllers/Backend/SeafileDocumentiController.php "$APP/app/Http/Controllers/Backend/"
cp -f resources/views/Backend/Documenti/seafile-sso.blade.php "$APP/resources/views/Backend/Documenti/"

$DOCKER cp app/Http/Services/SeafileClient.php "$CONTAINER:/var/www/html/app/Http/Services/SeafileClient.php"
$DOCKER cp app/Http/Controllers/Backend/SeafileDocumentiController.php "$CONTAINER:/var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php"
$DOCKER cp resources/views/Backend/Documenti/seafile-sso.blade.php "$CONTAINER:/var/www/html/resources/views/Backend/Documenti/seafile-sso.blade.php"

# Seafile: cookie domain + CSRF trusted origin Gestiio
if [ -f "$SETTINGS" ]; then
  if ! grep -q "SESSION_COOKIE_DOMAIN" "$SETTINGS"; then
    cat >> "$SETTINGS" <<'EOF'

# --- Gestiio SSO iframe ---
SESSION_COOKIE_DOMAIN = '.agenziaplinio.it'
CSRF_COOKIE_DOMAIN = '.agenziaplinio.it'
SESSION_COOKIE_SAMESITE = 'None'
SESSION_COOKIE_SECURE = True
CSRF_COOKIE_SAMESITE = 'None'
CSRF_COOKIE_SECURE = True
CSRF_TRUSTED_ORIGINS = ['https://gestiio.agenziaplinio.it', 'https://documenti.agenziaplinio.it']
EOF
  fi
  $DOCKER restart seafile
fi

$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Services/SeafileClient.php
$DOCKER exec "$CONTAINER" php -l /var/www/html/app/Http/Controllers/Backend/SeafileDocumentiController.php
$DOCKER exec "$CONTAINER" php /var/www/html/artisan view:clear
$DOCKER restart "$CONTAINER"
sleep 10

# Smoke login web
$DOCKER exec "$CONTAINER" php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = app(App\Http\Services\SeafileClient::class);
try {
  $cookies = $c->webLogin(config("services.seafile.admin_email"), config("services.seafile.admin_password"));
  $names = array_column($cookies, "name");
  echo in_array("sessionid", $names, true) ? "WEBLOGIN_OK\n" : "WEBLOGIN_NO_SESSION\n";
  echo "cookies=".implode(",", $names)."\n";
} catch (Throwable $e) {
  echo "WEBLOGIN_FAIL ".$e->getMessage()."\n";
  exit(1);
}
'

echo SSO_FIX_OK
