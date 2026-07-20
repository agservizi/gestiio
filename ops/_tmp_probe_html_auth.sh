#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

cat > /tmp/probe_html_auth.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(2);
$sso = app(App\Http\Services\StirlingSsoService::class);
$jwt = $sso->getJwtForUser($u, true);

foreach (['/pdf-tools/', '/pdf-tools/login', '/pdf-tools/index.html', '/pdf-tools/assets/'] as $path) {
    $r = Illuminate\Support\Facades\Http::withToken($jwt)
        ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
        ->get('http://stirling-pdf:8080'.$path);
    $body = $r->body();
    $ct = $r->header('Content-Type');
    echo "$path status={$r->status()} ct=$ct len=".strlen($body)." head=".(stripos($body,'<head>')!==false?'Y':'N')." snip=".substr(preg_replace('/\s+/',' ',$body),0,160)."\n";
}

// without token login page
$r = Illuminate\Support\Facades\Http::withHeaders(['Accept'=>'text/html'])->get('http://stirling-pdf:8080/pdf-tools/login');
echo "login_noauth status={$r->status()} len=".strlen($r->body())." snip=".substr(preg_replace('/\s+/',' ',$r->body()),0,300)."\n";

// Find static entry
$r = Illuminate\Support\Facades\Http::get('http://stirling-pdf:8080/pdf-tools/');
echo "root_noauth status={$r->status()} snip=".substr($r->body(),0,200)."\n";
PHP
$DOCKER cp /tmp/probe_html_auth.php gestiio-app:/tmp/probe_html_auth.php
$DOCKER exec gestiio-app php /tmp/probe_html_auth.php

echo '=== find static files in stirling ==='
$DOCKER exec stirling-pdf sh -c 'ls /; ls /usr/share/nginx/html 2>/dev/null; ls /app/static 2>/dev/null; ls /BO-server 2>/dev/null; find / -name index.html 2>/dev/null | head -20'
