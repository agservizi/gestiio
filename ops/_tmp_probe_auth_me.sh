#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

# Check if PHP receives Authorization
$DOCKER exec gestiio-app php -r 'echo "sapi=".PHP_SAPI."\n";'
# nginx config snippets
$DOCKER exec gestiio-app sh -c 'ls /etc/nginx 2>/dev/null; ls /etc/apache2 2>/dev/null; ls /etc/caddy 2>/dev/null; which nginx apache2 httpd 2>/dev/null; ls /etc/nginx/conf.d 2>/dev/null; grep -R Authorization /etc/nginx 2>/dev/null | head -20'

# Test /auth/me with token via internal + via localhost:8090 proxy if possible
cat > /tmp/probe_me.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::find(2);
$sso = app(App\Http\Services\StirlingSsoService::class);
$jwt = $sso->getJwtForUser($u, true);
$r = Illuminate\Support\Facades\Http::withToken($jwt)->acceptJson()
    ->get('http://stirling-pdf:8080/pdf-tools/api/v1/auth/me');
echo 'auth_me status='.$r->status().' body='.substr($r->body(),0,200).PHP_EOL;
echo 'jwt_prefix='.substr($jwt,0,20).PHP_EOL;
PHP
$DOCKER cp /tmp/probe_me.php gestiio-app:/tmp/probe_me.php
$DOCKER exec gestiio-app php /tmp/probe_me.php
