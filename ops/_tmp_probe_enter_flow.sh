#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== view enterUrl ==='
$DOCKER exec gestiio-app grep -n 'enterUrl\|iframe.src\|enter' /var/www/html/resources/views/Backend/PdfTools/index.blade.php | head -20

echo '=== route enter ==='
$DOCKER exec gestiio-app php /var/www/html/artisan route:list --name=pdf-tools.enter

echo '=== simulate enter JWT + root with auth ==='
cat > /tmp/probe_enter_flow.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(2);
$sso = app(App\Http\Services\StirlingSsoService::class);
$jwt = $sso->getJwtForUser($u, true);
echo "jwt_len=".strlen($jwt)." user=".$sso->usernameFor($u)."\n";

// HTML root WITH auth (what proxy should get)
$r = Illuminate\Support\Facades\Http::withToken($jwt)
    ->withHeaders(['Accept'=>'text/html'])
    ->get('http://stirling-pdf:8080/pdf-tools/');
echo "root_auth status={$r->status()} len=".strlen($r->body())." head=".(stripos($r->body(),'<head>')!==false?'Y':'N')."\n";

// auth/me
$me = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
    ->get('http://stirling-pdf:8080/pdf-tools/api/v1/auth/me');
echo "me={$me->status()} ".substr($me->body(),0,120)."\n";

// login via API with derived creds (browser style)
$login = Illuminate\Support\Facades\Http::acceptJson()->asJson()
    ->post('http://stirling-pdf:8080/pdf-tools/api/v1/auth/login', [
        'username' => $sso->usernameFor($u),
        'password' => $sso->passwordFor($u),
    ]);
echo "login={$login->status()} keys=".implode(',', array_keys($login->json() ?? []))."\n";
echo "set_cookie=".( $login->header('Set-Cookie') ?: 'none' )."\n";

// app-config with and without
$c1 = Illuminate\Support\Facades\Http::get('http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config');
$c2 = Illuminate\Support\Facades\Http::withToken($jwt)->get('http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config');
echo "app-config noauth={$c1->status()} auth={$c2->status()}\n";
PHP
$DOCKER cp /tmp/probe_enter_flow.php gestiio-app:/tmp/probe_enter_flow.php
$DOCKER exec gestiio-app php /tmp/probe_enter_flow.php

echo '=== apache auth header ==='
$DOCKER exec gestiio-app sh -c 'grep -R "Authorization\|HTTP_AUTHORIZATION\|RewriteRule" /etc/apache2/sites-enabled /etc/apache2/conf-enabled 2>/dev/null | head -30'

echo '=== loginAttempt settings ==='
grep -n 'enableLogin\|loginAttempt' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -10
