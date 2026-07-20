#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

# Get admin password from stirling .env / gestiio .env
ADMIN_USER=$(grep -E '^STIRLING_ADMIN_USER=' /home/Carmine/apps/stirling-pdf/.env 2>/dev/null | cut -d= -f2- | tr -d '"' || true)
ADMIN_PASS=$(grep -E '^STIRLING_ADMIN_PASSWORD=' /home/Carmine/apps/stirling-pdf/.env 2>/dev/null | cut -d= -f2- | tr -d '"' || true)
if [ -z "${ADMIN_USER}" ]; then ADMIN_USER=$(grep -E '^STIRLING_ADMIN_USER=' /home/Carmine/apps/gestiio-20260624-2128/.env 2>/dev/null | cut -d= -f2- | tr -d '"' || true); fi
if [ -z "${ADMIN_PASS}" ]; then ADMIN_PASS=$(grep -E '^STIRLING_ADMIN_PASSWORD=' /home/Carmine/apps/gestiio-20260624-2128/.env 2>/dev/null | cut -d= -f2- | tr -d '"' || true); fi
ADMIN_USER=${ADMIN_USER:-admin}
echo "admin_user=$ADMIN_USER pass_len=${#ADMIN_PASS}"

$DOCKER exec -i gestiio-app php <<PHP
<?php
require "/var/www/html/vendor/autoload.php";
\$app = require "/var/www/html/bootstrap/app.php";
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$user = getenv('U') ?: 'admin';
\$pass = getenv('P') ?: '';
if (\$pass === '') {
  \$pass = (string) config('services.stirling.admin_password');
  \$user = (string) config('services.stirling.admin_user', 'admin');
}
\$base = rtrim((string) config('services.stirling.url'), '/');

\$login = Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->asJson()
  ->post(\$base.'/pdf-tools/api/v1/auth/login', ['username'=>\$user,'password'=>\$pass]);
echo "login=".\$login->status()."\n";
\$body = \$login->json();
\$token = data_get(\$body, 'session.access_token') ?: data_get(\$body, 'access_token') ?: data_get(\$body, 'token');
echo "has_token=".((is_string(\$token)&&\$token!=='')?'yes':'no')."\n";
echo "login_keys=".implode(',', array_keys(is_array(\$body)?\$body:[]))."\n";
if (is_array(\$body)) {
  echo "forceChange=".json_encode(data_get(\$body, 'user.forceChange') ?? data_get(\$body, 'forceChange') ?? data_get(\$body, 'user.user_metadata'))."\n";
}

// Probe me
if (is_string(\$token) && \$token !== '') {
  \$me = Illuminate\Support\Facades\Http::timeout(30)->withToken(\$token)->acceptJson()
    ->get(\$base.'/pdf-tools/api/v1/auth/me');
  echo "me=".\$me->status()."\n";
  echo substr(\$me->body(), 0, 800)."\n";
}

// List admin endpoints hints from openapi if any
\$docs = Illuminate\Support\Facades\Http::timeout(30)->withToken((string)\$token)->get(\$base.'/pdf-tools/v3/api-docs');
echo "openapi=".\$docs->status()."\n";
if (\$docs->successful()) {
  \$j = \$docs->json();
  \$paths = array_keys(\$j['paths'] ?? []);
  foreach (\$paths as \$p) {
    if (stripos(\$p, 'user') !== false || stripos(\$p, 'password') !== false || stripos(\$p, 'force') !== false) {
      echo "path \$p\n";
    }
  }
}
PHP
