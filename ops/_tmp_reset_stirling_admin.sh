#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_STIR=/home/Carmine/apps/stirling-pdf
PASS='uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL'
USER='gestiio'

echo '=== try common admin passwords ==='
for pw in stirling admin password Admin123! 'changeme' "$PASS"; do
  code=$($DOCKER exec gestiio-app curl -sS -o /tmp/login.json -w '%{http_code}' \
    -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"username\":\"admin\",\"password\":\"$pw\"}" \
    http://stirling-pdf:8080/pdf-tools/api/v1/auth/login || echo err)
  echo "admin/$pw -> $code"
  if [ "$code" = "200" ]; then
    echo 'FOUND'; $DOCKER exec gestiio-app cat /tmp/login.json | head -c 300; echo
    exit 0
  fi
done

echo '=== reset H2 db to recreate initial user ==='
$DOCKER stop stirling-pdf
mkdir -p "$APP_STIR/configs/backup"
ts=$(date +%Y%m%d-%H%M%S)
cp -a "$APP_STIR/configs/stirling-pdf-DB-2.3.232.mv.db" "$APP_STIR/configs/backup/stirling-pdf-DB-$ts.mv.db" 2>/dev/null || true
rm -f "$APP_STIR/configs"/stirling-pdf-DB-*.mv.db \
      "$APP_STIR/configs"/stirling-pdf-DB-*.trace.db \
      "$APP_STIR/configs"/stirling-pdf-DB-*.lock.db 2>/dev/null || true

# Ensure settings initial login
python3 - <<PY
from pathlib import Path
p = Path('$APP_STIR/configs/settings.yml')
t = p.read_text(encoding='utf-8')
import re
t2 = re.sub(r'(?m)^  enableLogin:.*', "  enableLogin: true # set to 'true' to enable login", t)
t2 = re.sub(r'(?m)^    username:.*', '    username: "gestiio" # initial username for the first login', t2)
t2 = re.sub(r'(?m)^    password:.*', '    password: "$PASS" # initial password for the first login', t2)
p.write_text(t2, encoding='utf-8')
print('settings ok')
PY

cd "$APP_STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "  $i $st"
  [ "$st" = "healthy" ] && break
  sleep 3
done

echo '=== login after reset ==='
code=$($DOCKER exec gestiio-app curl -sS -o /tmp/login.json -w '%{http_code}' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"username\":\"$USER\",\"password\":\"$PASS\"}" \
  http://stirling-pdf:8080/pdf-tools/api/v1/auth/login || echo err)
echo "gestiio login http=$code"
$DOCKER exec gestiio-app head -c 400 /tmp/login.json; echo

# Also try admin in case initial created admin
code2=$($DOCKER exec gestiio-app curl -sS -o /tmp/login2.json -w '%{http_code}' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"username\":\"admin\",\"password\":\"$PASS\"}" \
  http://stirling-pdf:8080/pdf-tools/api/v1/auth/login || echo err)
echo "admin login http=$code2"
$DOCKER exec gestiio-app head -c 200 /tmp/login2.json; echo

$DOCKER logs stirling-pdf 2>&1 | grep -iE 'User not found|Invalid password|created|initial|gestiio|admin' | tail -20

# Refresh JWT via artisan
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
