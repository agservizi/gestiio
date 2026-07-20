#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== settings loginAttempt ==='
grep -n -A20 'loginAttempt\|enableLogin\|block' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -40 || true

echo '=== try unlock via admin API ==='
cat > /tmp/unlock_stirling.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$adminUser = config('services.stirling.admin_user');
$adminPass = config('services.stirling.admin_password');
$base = rtrim(config('services.stirling.url'), '/');

$login = Illuminate\Support\Facades\Http::acceptJson()->asJson()
    ->post($base.'/pdf-tools/api/v1/auth/login', [
        'username' => $adminUser,
        'password' => $adminPass,
    ]);
echo "admin_login status=".$login->status()."\n";
if (!$login->successful()) {
    echo substr($login->body(),0,500)."\n";
    exit(1);
}
$jwt = data_get($login->json(), 'session.access_token');
echo "admin_jwt_len=".strlen((string)$jwt)."\n";

// List users / unlock endpoints (probe)
$paths = [
    ['GET', '/pdf-tools/api/v1/user/admin/getAllUsers'],
    ['GET', '/pdf-tools/api/v1/user/admin/users'],
    ['GET', '/pdf-tools/api/v1/admin/users'],
];
foreach ($paths as [$m,$p]) {
    $r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()->send($m, $base.$p);
    echo "$m $p => ".$r->status()." ".substr($r->body(),0,200)."\n";
}

// Try unlock known users
$users = App\Models\User::query()->orderBy('id')->limit(10)->get();
foreach ($users as $u) {
    $name = 'gestiio-'.$u->id;
    foreach ([
        '/pdf-tools/api/v1/user/admin/unlock/'.$name,
        '/pdf-tools/api/v1/user/admin/unlockUser/'.$name,
        '/pdf-tools/api/v1/admin/users/'.$name.'/unlock',
    ] as $p) {
        $r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()->post($base.$p);
        if ($r->status() !== 404) {
            echo "unlock POST $p => ".$r->status()." ".substr($r->body(),0,120)."\n";
        }
    }
}

// Also try phone-like username from screenshot
foreach (['3701526228','admin'] as $name) {
    foreach ([
        '/pdf-tools/api/v1/user/admin/unlock/'.$name,
        '/pdf-tools/api/v1/user/admin/unlockUser?username='.$name,
    ] as $p) {
        $r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()->post($base.$p);
        echo "unlock $name $p => ".$r->status()."\n";
    }
}

// Smoke getJwt for user 2
try {
    $sso = app(App\Http\Services\StirlingSsoService::class);
    $u2 = App\Models\User::find(2);
    $token = $sso->getJwtForUser($u2, true);
    echo "gestiio-2 jwt_ok len=".strlen($token)."\n";
} catch (Throwable $e) {
    echo "gestiio-2 FAIL ".$e->getMessage()."\n";
}
PHP

$DOCKER cp /tmp/unlock_stirling.php gestiio-app:/tmp/unlock_stirling.php
$DOCKER exec gestiio-app php /tmp/unlock_stirling.php
