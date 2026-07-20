#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER cp /tmp/PdfToolsController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/PdfToolsController.php
cp -f /tmp/PdfToolsController.php /home/Carmine/apps/gestiio-20260624-2128/app/Http/Controllers/Backend/PdfToolsController.php
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

cat > /tmp/probe_stirling_auth.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(2);
$sso = app(App\Http\Services\StirlingSsoService::class);
$jwt = $sso->getJwtForUser($u, true);
echo 'jwt_len=' . strlen($jwt) . PHP_EOL;
echo 'user=' . $sso->usernameFor($u) . PHP_EOL;

$r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
    ->get('http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config');
echo 'with_auth status=' . $r->status() . ' body=' . substr($r->body(), 0, 160) . PHP_EOL;

$r2 = Illuminate\Support\Facades\Http::acceptJson()
    ->get('http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config');
echo 'no_auth status=' . $r2->status() . PHP_EOL;

$r3 = Illuminate\Support\Facades\Http::acceptJson()->asJson()
    ->post('http://stirling-pdf:8080/pdf-tools/api/v1/auth/login', [
        'username' => $sso->usernameFor($u),
        'password' => $sso->passwordFor($u),
    ]);
echo 'login status=' . $r3->status() . PHP_EOL;

// Simula proxy: richiesta con Authorization come farebbe il browser
$r4 = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $jwt,
    'Accept' => 'application/json',
])->get('http://stirling-pdf:8080/pdf-tools/api/v1/config/endpoints-availability');
echo 'endpoints with_auth status=' . $r4->status() . PHP_EOL;
PHP

$DOCKER cp /tmp/probe_stirling_auth.php gestiio-app:/tmp/probe_stirling_auth.php
$DOCKER exec gestiio-app php /tmp/probe_stirling_auth.php
echo FIX_OK
