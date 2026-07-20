#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

# Disable lockouts + unlock phone username
perl -pi -e 's/loginAttemptCount:\s*\d+/loginAttemptCount: -1/' /home/Carmine/apps/stirling-pdf/configs/settings.yml
perl -pi -e 's/loginResetTimeMinutes:\s*\d+/loginResetTimeMinutes: 1/' /home/Carmine/apps/stirling-pdf/configs/settings.yml
grep -n 'loginAttemptCount\|loginResetTimeMinutes' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -5

cat > /tmp/unlock_all.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim(config('services.stirling.url'), '/');
$login = Illuminate\Support\Facades\Http::acceptJson()->asJson()
    ->post($base.'/pdf-tools/api/v1/auth/login', [
        'username' => config('services.stirling.admin_user'),
        'password' => config('services.stirling.admin_password'),
    ]);
$jwt = data_get($login->json(), 'session.access_token');
echo "admin_login=".$login->status()."\n";

$names = ['admin','gestiio','3701526228'];
foreach (App\Models\User::query()->orderBy('id')->limit(50)->pluck('id') as $id) {
    $names[] = 'gestiio-'.$id;
}
$names = array_unique($names);
foreach ($names as $name) {
    $r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
        ->post($base.'/pdf-tools/api/v1/user/admin/unlockUser/'.$name);
    if ($r->status() !== 404) {
        echo "unlock $name => ".$r->status()." ".substr($r->body(),0,80)."\n";
    }
}

// Find user with phone-like field
$u = App\Models\User::query()
    ->where('email', 'like', '%3701526228%')
    ->orWhere('name', 'like', '%3701526228%')
    ->orWhere('username', 'like', '%3701526228%')
    ->first();
if (!$u) {
    // try common columns
    foreach (['telefono','cellulare','phone','mobile'] as $col) {
        try {
            $u = App\Models\User::query()->where($col, '3701526228')->orWhere($col, 'like', '%3701526228%')->first();
            if ($u) break;
        } catch (Throwable $e) {}
    }
}
if ($u) {
    echo "matched_user id={$u->id} email={$u->email}\n";
    $sso = app(App\Http\Services\StirlingSsoService::class);
    try {
        $t = $sso->getJwtForUser($u, true);
        echo "matched_jwt_ok len=".strlen($t)." user=".$sso->usernameFor($u)."\n";
    } catch (Throwable $e) {
        echo "matched_jwt_fail ".$e->getMessage()."\n";
    }
} else {
    echo "no_user_match_for_3701526228\n";
    // dump columns
    $sample = App\Models\User::find(2);
    if ($sample) echo "user2_attrs=".implode(',', array_keys($sample->getAttributes()))."\n";
}
PHP

$DOCKER cp /tmp/unlock_all.php gestiio-app:/tmp/unlock_all.php
$DOCKER exec gestiio-app php /tmp/unlock_all.php

# Restart stirling to apply loginAttemptCount -1
cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d
for i in $(seq 1 30); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "health=$st"
  [ "$st" = "healthy" ] && break
  sleep 2
done
curl -sS -m 10 http://127.0.0.1:8091/pdf-tools/api/v1/info/status; echo
