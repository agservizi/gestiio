#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== deployed controller markers ==='
$DOCKER exec gestiio-app grep -n 'injectJwtIntoHtml\|gestiio_stirling_jwt\|isSpaShellPath' /var/www/html/app/Http/Controllers/Backend/PdfToolsController.php | head -20

echo '=== view markers ==='
$DOCKER exec gestiio-app grep -n 'verifySession\|Accesso automatico\|fetchSsoToken' /var/www/html/resources/views/Backend/PdfTools/index.blade.php | head -20

echo '=== probe SSO + HTML inject ==='
cat > /tmp/probe_sso_html.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::query()->orderBy('id')->get()->first(function ($user) {
    try {
        return $user->hasPermissionTo('admin') || $user->hasPermissionTo('agente');
    } catch (Throwable $e) {
        return false;
    }
}) ?: App\Models\User::find(2);

echo "user_id=".($u->id ?? 'null')."\n";
$sso = app(App\Http\Services\StirlingSsoService::class);
try {
    $jwt = $sso->getJwtForUser($u, true);
    echo "jwt_ok len=".strlen($jwt)." user=".$sso->usernameFor($u)."\n";
} catch (Throwable $e) {
    echo "jwt_fail ".$e->getMessage()."\n";
    exit(1);
}

// Simulate what SPA does: auth/me
$me = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
    ->get('http://stirling-pdf:8080/pdf-tools/api/v1/auth/me');
echo "auth_me=".$me->status()." body=".substr($me->body(),0,180)."\n";

// Fetch HTML shell from Stirling and check if our inject would match
$html = Illuminate\Support\Facades\Http::get('http://stirling-pdf:8080/pdf-tools/')->body();
echo "html_len=".strlen($html)." has_head=".(stripos($html,'<head>')!==false?'yes':'no')."\n";
echo "html_snip=".substr(preg_replace('/\s+/',' ', $html),0,200)."\n";

// Check login path HTML
$htmlLogin = Illuminate\Support\Facades\Http::get('http://stirling-pdf:8080/pdf-tools/login')->body();
echo "login_html_len=".strlen($htmlLogin)." same_as_index=".(md5($html)===md5($htmlLogin)?'yes':'no')."\n";

// Does getSession need more than JWT? Check refresh endpoint
$ref = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
    ->post('http://stirling-pdf:8080/pdf-tools/api/v1/auth/refresh');
echo "refresh=".$ref->status()." ".substr($ref->body(),0,120)."\n";
PHP
$DOCKER cp /tmp/probe_sso_html.php gestiio-app:/tmp/probe_sso_html.php
$DOCKER exec gestiio-app php /tmp/probe_sso_html.php

echo '=== laravel log tail ==='
$DOCKER exec gestiio-app sh -c 'tail -n 40 /var/www/html/storage/logs/laravel.log 2>/dev/null | grep -i stirling | tail -20 || true'
