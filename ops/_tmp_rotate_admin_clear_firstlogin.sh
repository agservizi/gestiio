#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

$user = (string) config('services.stirling.admin_user', 'admin');
$old = (string) config('services.stirling.admin_password');
$base = rtrim((string) config('services.stirling.url'), '/');
$new = rtrim(strtr(base64_encode(random_bytes(15)), '+/', 'Aa'), '=');

$login = Http::timeout(30)->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
    'username' => $user,
    'password' => $old,
]);
if (! $login->successful()) {
    fwrite(STDERR, "login failed ".$login->status()."\n");
    exit(1);
}
$token = data_get($login->json(), 'session.access_token');
$first = data_get($login->json(), 'user.user_metadata.firstLogin');
echo "before firstLogin=".json_encode($first)."\n";

if ($first === true) {
    $r = Http::timeout(30)->withToken($token)->asForm()->post($base.'/pdf-tools/api/v1/user/change-password-on-login', [
        'currentPassword' => $old,
        'newPassword' => $new,
        'confirmPassword' => $new,
    ]);
    echo "on-login=".$r->status()."\n";
    if (! $r->successful()) {
        fwrite(STDERR, $r->body()."\n");
        exit(1);
    }
    $passToStore = $new;
} else {
    echo "already cleared\n";
    $passToStore = $old;
}

$login2 = Http::timeout(30)->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
    'username' => $user,
    'password' => $passToStore,
]);
echo "after firstLogin=".json_encode(data_get($login2->json(), 'user.user_metadata.firstLogin'))."\n";
if (data_get($login2->json(), 'user.user_metadata.firstLogin') === true) {
    fwrite(STDERR, "still firstLogin\n");
    exit(1);
}

$files = [
    '/var/www/html/.env',
    '/home/Carmine/apps/gestiio-20260624-2128/.env', // may not exist in container
];
// Prefer host paths via known docker mounts - only /var/www/html/.env is sure
$envPath = '/var/www/html/.env';
$env = file_exists($envPath) ? file_get_contents($envPath) : '';
if ($env === false) $env = '';
if (preg_match('/^STIRLING_ADMIN_PASSWORD=.*$/m', $env)) {
    $env = preg_replace('/^STIRLING_ADMIN_PASSWORD=.*$/m', 'STIRLING_ADMIN_PASSWORD='.$passToStore, $env, 1);
} else {
    $env .= "\nSTIRLING_ADMIN_PASSWORD=".$passToStore."\n";
}
file_put_contents($envPath, $env);
echo "updated container .env\n";

// Write marker for host sync (password only in file mode 600 via host script)
file_put_contents('/tmp/stirling_admin_pass_new', $passToStore);
chmod('/tmp/stirling_admin_pass_new', 0600);

Cache::forget('stirling_admin_jwt_v2');
echo "OK\n";
PHP

# Copy new password from container to host env files
NEW_PASS=$($DOCKER exec gestiio-app cat /tmp/stirling_admin_pass_new)
$DOCKER exec gestiio-app rm -f /tmp/stirling_admin_pass_new

for f in /home/Carmine/apps/gestiio-20260624-2128/.env /home/Carmine/apps/stirling-pdf/.env; do
  [ -f "$f" ] || continue
  if grep -q '^STIRLING_ADMIN_PASSWORD=' "$f"; then
    sed -i "s|^STIRLING_ADMIN_PASSWORD=.*|STIRLING_ADMIN_PASSWORD=${NEW_PASS}|" "$f"
  else
    echo "STIRLING_ADMIN_PASSWORD=${NEW_PASS}" >> "$f"
  fi
  if grep -q '^SECURITY_INITIALLOGIN_PASSWORD=' "$f"; then
    sed -i "s|^SECURITY_INITIALLOGIN_PASSWORD=.*|SECURITY_INITIALLOGIN_PASSWORD=${NEW_PASS}|" "$f"
  fi
  echo "updated $f"
done
unset NEW_PASS

$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan cache:clear
$DOCKER restart gestiio-app
for i in $(seq 1 25); do
  code=$(curl -sS -m 3 -o /dev/null -w '%{http_code}' http://127.0.0.1:8090/login || echo 000)
  [ "$code" = "200" ] && break
  sleep 2
done

$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Cache::forget('stirling_admin_jwt_v2');
$user = (string) config('services.stirling.admin_user', 'admin');
$pass = (string) config('services.stirling.admin_password');
$base = rtrim((string) config('services.stirling.url'), '/');
$login = Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->asJson()
  ->post($base.'/pdf-tools/api/v1/auth/login', ['username'=>$user,'password'=>$pass]);
echo "final login=".$login->status()." firstLogin=".json_encode(data_get($login->json(), 'user.user_metadata.firstLogin'))."\n";
PHP
echo DONE
