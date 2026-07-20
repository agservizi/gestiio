#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== locales via http ==='
for p in \
  locales/it-IT/translation.toml \
  locales/it-IT/common.toml \
  locales/en-GB/translation.toml \
  locales/it_IT/translation.toml
do
  code=$($DOCKER exec stirling-pdf sh -c "curl -sS -m 3 -o /dev/null -w %{http_code} http://127.0.0.1:8080/pdf-tools/$p" || echo 000)
  echo "$p => $code"
done

echo '=== find toml ==='
$DOCKER exec stirling-pdf sh -c 'find / -name "*.toml" 2>/dev/null | head -40'

echo '=== app-config with jwt via php ==='
$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sso = $app->make(App\Http\Services\StirlingSsoService::class);
$jwt = $sso->getJwtForUser(App\Models\User::find(2), true);
$ch = curl_init("http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_HTTPHEADER=>["Authorization: Bearer $jwt","Accept: application/json"], CURLOPT_TIMEOUT=>10]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "code=$code\n";
if (preg_match('/"defaultLocale"\s*:\s*"[^"]*"/', $body, $m)) echo $m[0]."\n";
if (preg_match('/"languages"\s*:\s*\[[^\]]*\]/', $body, $m)) echo $m[0]."\n";
echo substr($body, 0, 800)."\n";
PHP
