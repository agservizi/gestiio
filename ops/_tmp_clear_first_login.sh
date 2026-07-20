#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = (string) config('services.stirling.admin_user', 'admin');
$pass = (string) config('services.stirling.admin_password');
$base = rtrim((string) config('services.stirling.url'), '/');
$http = Illuminate\Support\Facades\Http::timeout(30);

$login = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username'=>$user,'password'=>$pass,
]);
$token = data_get($login->json(), 'session.access_token');
echo "token_ok\n";

$auth = $http->withToken($token)->acceptJson();

// Try endpoints that might clear firstLogin
$candidates = [
  ['PUT', '/pdf-tools/api/v1/user/updateUserSettings', ['firstLogin'=>false]],
  ['POST', '/pdf-tools/api/v1/user/updateUserSettings', ['firstLogin'=>false]],
  ['PUT', '/pdf-tools/api/v1/user/settings', ['firstLogin'=>false]],
  ['POST', '/pdf-tools/api/v1/user/change-password', ['currentPassword'=>$pass,'newPassword'=>$pass]],
  ['POST', '/pdf-tools/api/v1/user/changePassword', ['currentPassword'=>$pass,'newPassword'=>$pass,'confirmPassword'=>$pass]],
  ['POST', '/pdf-tools/api/v1/user/changePassword', ['oldPassword'=>$pass,'newPassword'=>$pass]],
  ['POST', '/pdf-tools/api/v1/auth/change-password', ['currentPassword'=>$pass,'newPassword'=>$pass]],
  ['POST', '/pdf-tools/api/v1/user/admin/saveUser', null], // multipart below
];

foreach ($candidates as [$method, $path, $json]) {
  if ($json === null) continue;
  $r = $method === 'PUT'
    ? $auth->asJson()->put($base.$path, $json)
    : $auth->asJson()->post($base.$path, $json);
  echo "$method $path => ".$r->status()." ".substr(preg_replace('/\s+/',' ', $r->body()),0,120)."\n";
}

// saveUser multipart forceChange false for admin
$r = $auth->asMultipart()->post($base.'/pdf-tools/api/v1/user/admin/saveUser', [
  'username' => $user,
  'password' => $pass,
  'role' => 'ROLE_ADMIN',
  'authType' => 'WEB',
  'forceChange' => 'false',
  'enabled' => 'true',
]);
echo "saveUser => ".$r->status()." ".substr(preg_replace('/\s+/',' ', $r->body()),0,200)."\n";

// openapi path dump for password/firstLogin
$docs = $auth->get($base.'/pdf-tools/v3/api-docs');
$paths = array_keys($docs->json()['paths'] ?? []);
foreach ($paths as $p) {
  if (preg_match('/password|firstLogin|user|settings|change/i', $p)) {
    echo "api $p\n";
  }
}
PHP
