#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sso = $app->make(App\Http\Services\StirlingSsoService::class);
$user = App\Models\User::find(2);
$jwt = $sso->getJwtForUser($user, true);

foreach (['policies', 'info/status', 'auth/me'] as $ep) {
  $url = "http://stirling-pdf:8080/pdf-tools/api/v1/{$ep}";
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer ".$jwt, "Accept: application/json"],
    CURLOPT_TIMEOUT => 10,
  ]);
  $body = curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  echo "{$ep} => {$code} body=".substr(preg_replace('/\s+/', ' ', (string)$body), 0, 180)."\n";
}
PHP
