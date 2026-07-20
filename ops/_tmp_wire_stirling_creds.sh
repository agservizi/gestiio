#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_GEST=/home/Carmine/apps/gestiio-20260624-2128
APP_STIR=/home/Carmine/apps/stirling-pdf
USER='admin'
PASS='stirling'
NEWPASS='uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL'

# Optionally change password to strong one
TOKEN=$($DOCKER exec gestiio-app curl -sS -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"username\":\"$USER\",\"password\":\"$PASS\"}" \
  http://stirling-pdf:8080/pdf-tools/api/v1/auth/login | python3 -c 'import sys,json; print(json.load(sys.stdin)["session"]["access_token"])')

echo "token_len=${#TOKEN}"

# Try change-password endpoints
for path in \
  /pdf-tools/api/v1/user/change-password \
  /pdf-tools/api/v1/user/password \
  /pdf-tools/api/v1/auth/change-password \
  /pdf-tools/api/v1/users/change-password
 do
  code=$($DOCKER exec gestiio-app curl -sS -o /tmp/chpw.json -w '%{http_code}' \
    -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"currentPassword\":\"$PASS\",\"newPassword\":\"$NEWPASS\",\"password\":\"$NEWPASS\",\"oldPassword\":\"$PASS\"}" \
    "http://stirling-pdf:8080$path" || echo err)
  echo "chpw $path -> $code $($DOCKER exec gestiio-app head -c 120 /tmp/chpw.json 2>/dev/null)"
done

# Prefer strong password if change worked; else keep stirling
FINAL_USER=admin
FINAL_PASS=stirling
code=$($DOCKER exec gestiio-app curl -sS -o /tmp/loginN.json -w '%{http_code}' \
  -H 'Content-Type: application/json' \
  -d "{\"username\":\"admin\",\"password\":\"$NEWPASS\"}" \
  http://stirling-pdf:8080/pdf-tools/api/v1/auth/login || echo err)
if [ "$code" = "200" ]; then
  FINAL_PASS=$NEWPASS
  echo 'USING_STRONG_PASSWORD'
else
  echo 'KEEPING_DEFAULT_STIRLING_PASSWORD'
fi

cat > "$APP_STIR/.env" <<EOF
STIRLING_ADMIN_USER=$FINAL_USER
STIRLING_ADMIN_PASSWORD=$FINAL_PASS
EOF
chmod 600 "$APP_STIR/.env"

# Update gestiio .env
perl -pi -e "s/^STIRLING_ADMIN_USER=.*/STIRLING_ADMIN_USER=$FINAL_USER/" "$APP_GEST/.env"
perl -pi -e "s/^STIRLING_ADMIN_PASSWORD=.*/STIRLING_ADMIN_PASSWORD=$FINAL_PASS/" "$APP_GEST/.env"
grep -q '^STIRLING_ADMIN_USER=' "$APP_GEST/.env" || echo "STIRLING_ADMIN_USER=$FINAL_USER" >> "$APP_GEST/.env"
grep -q '^STIRLING_ADMIN_PASSWORD=' "$APP_GEST/.env" || echo "STIRLING_ADMIN_PASSWORD=$FINAL_PASS" >> "$APP_GEST/.env"
grep -q '^STIRLING_URL=' "$APP_GEST/.env" || echo 'STIRLING_URL=http://stirling-pdf:8080' >> "$APP_GEST/.env"

$DOCKER cp "$APP_GEST/.env" gestiio-app:/var/www/html/.env
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan cache:clear

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

# Update compose initial password for future resets (use FINAL)
# ensure SECURITY_INITIALLOGIN still set in running container - already has gestiio; fine
echo FINAL_USER=$FINAL_USER
echo DONE
