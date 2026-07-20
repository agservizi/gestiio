#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_STIR=/home/Carmine/apps/stirling-pdf
APP_GEST=/home/Carmine/apps/gestiio-20260624-2128
ADMIN_USER=admin
ADMIN_PASS=stirling
USER_SECRET='nd6uiXnXzpSNzLUCAw68yQypth-5qZKawjZfIATZ4FE'

# --- Stirling .env ---
cat > "$APP_STIR/.env" <<EOF
STIRLING_ADMIN_USER=$ADMIN_USER
STIRLING_ADMIN_PASSWORD=$ADMIN_PASS
EOF
chmod 600 "$APP_STIR/.env"

# --- settings.yml: storage + login ---
python3 - <<'PY'
from pathlib import Path
import re
p = Path('/home/Carmine/apps/stirling-pdf/configs/settings.yml')
t = p.read_text(encoding='utf-8')
t2 = re.sub(r'(?m)^  enableLogin:.*', "  enableLogin: true # set to 'true' to enable login", t)
# storage block: enabled: false -> true (first occurrence under storage:)
def enable_storage(text: str) -> str:
    lines = text.splitlines(True)
    out = []
    in_storage = False
    storage_indent = None
    done = False
    for line in lines:
        if re.match(r'^storage:\s*$', line):
            in_storage = True
            storage_indent = 0
            out.append(line)
            continue
        if in_storage and not done:
            m = re.match(r'^(\s*)enabled:\s*(true|false)\b(.*)$', line)
            if m and len(m.group(1)) >= 2:
                out.append(f"{m.group(1)}enabled: true{m.group(3)}\n" if line.endswith('\n') else f"{m.group(1)}enabled: true{m.group(3)}")
                # fix newline
                if not out[-1].endswith('\n'):
                    out[-1] += '\n'
                done = True
                in_storage = False
                continue
            if re.match(r'^[a-zA-Z]', line):
                in_storage = False
        out.append(line)
    return ''.join(out)
t3 = enable_storage(t2)
p.write_text(t3, encoding='utf-8')
print('enableLogin=', 'enableLogin: true' in t3)
print('storage_enabled_true=', bool(re.search(r'(?m)^storage:[\s\S]*?^\s{2}enabled:\s*true', t3)))
# show storage snippet
import itertools
for i,l in enumerate(t3.splitlines()):
    if l.startswith('storage:'):
        print('\n'.join(t3.splitlines()[i:i+8]))
        break
PY

cp -f /tmp/docker-compose.stirling.yml "$APP_STIR/docker-compose.stirling.yml"
perl -pi -e 's/\r\n/\n/g' "$APP_STIR/docker-compose.stirling.yml"

cd "$APP_STIR"
$DOCKER compose -f docker-compose.stirling.yml up -d
echo 'Waiting healthy...'
for i in $(seq 1 45); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "  $i $st"
  [ "$st" = "healthy" ] && break
  sleep 3
done

# --- Gestiio .env ---
touch "$APP_GEST/.env"
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
set_env STIRLING_DESKTOP_URL 'http://192.168.1.50:8090/pdf-tools'
set_env STIRLING_ADMIN_USER "$ADMIN_USER"
set_env STIRLING_ADMIN_PASSWORD "$ADMIN_PASS"
set_env STIRLING_USER_SECRET "$USER_SECRET"
set_env STIRLING_TIMEOUT '300'

# --- Deploy code ---
mkdir -p "$APP_GEST/app/Http/Services" "$APP_GEST/resources/views/Backend/PdfTools"
cp -f /tmp/StirlingSsoService.php "$APP_GEST/app/Http/Services/StirlingSsoService.php"
cp -f /tmp/PdfToolsController.php "$APP_GEST/app/Http/Controllers/Backend/PdfToolsController.php"
cp -f /tmp/services.php "$APP_GEST/config/services.php"
cp -f /tmp/web-backend.php "$APP_GEST/routes/web-backend.php"
cp -f /tmp/PdfToolsIndex.blade.php "$APP_GEST/resources/views/Backend/PdfTools/index.blade.php"
cp -f /tmp/DEPLOY.md "$APP_GEST/DEPLOY.md" 2>/dev/null || true

$DOCKER cp /tmp/StirlingSsoService.php gestiio-app:/var/www/html/app/Http/Services/StirlingSsoService.php
$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER cp /tmp/services.php gestiio-app:/var/www/html/config/services.php
$DOCKER cp /tmp/web-backend.php gestiio-app:/var/www/html/routes/web-backend.php
$DOCKER cp /tmp/PdfToolsIndex.blade.php gestiio-app:/var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER cp "$APP_GEST/.env" gestiio-app:/var/www/html/.env

$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear

echo '=== LAN status ==='
curl -sS -m 10 http://127.0.0.1:8090/pdf-tools/api/v1/info/status || echo LAN_FAIL
echo

echo '=== smoke JWT two users ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sso = app(App\Http\Services\StirlingSsoService::class);
$users = App\Models\User::query()->whereIn("id", [2])->get();
// pick another user with agente/admin if available
$other = App\Models\User::query()->where("id", "<>", 2)->orderBy("id")->first();
if ($other) { $users->push($other); }
foreach ($users as $u) {
  try {
    $jwt = $sso->getJwtForUser($u, true);
    $creds = $sso->desktopCredentials($u);
    echo "user={$u->id} stirling={$creds["username"]} role={$creds["role"]} jwt_len=".strlen($jwt)."\n";
  } catch (Throwable $e) {
    echo "user={$u->id} FAIL ".$e->getMessage()."\n";
  }
}
'

echo '=== storage setting ==='
grep -n -A6 '^storage:' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -10
echo '=== ports ==='
$DOCKER port stirling-pdf || true
echo DEPLOY_OK
