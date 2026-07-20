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
  'username' => $user,
  'password' => $pass,
]);
$token = data_get($login->json(), 'session.access_token');
echo "login=".$login->status()." firstLogin=".json_encode(data_get($login->json(), 'user.user_metadata.firstLogin'))."\n";

// Clear first-login by "changing" to the same password (multipart form as UI)
$r = $http->withToken($token)
  ->asMultipart()
  ->post($base.'/pdf-tools/api/v1/user/change-password-on-login', [
    'currentPassword' => $pass,
    'newPassword' => $pass,
  ]);
echo "change-password-on-login=".$r->status()." ".substr(preg_replace('/\s+/',' ', $r->body()),0,200)."\n";

// Also try updateUserSettings
$r2 = $http->withToken($token)->asJson()->post($base.'/pdf-tools/api/v1/user/updateUserSettings', [
  'firstLogin' => false,
]);
echo "updateUserSettings=".$r2->status()." ".substr(preg_replace('/\s+/',' ', $r2->body()),0,120)."\n";

// Re-login and check
$login2 = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username' => $user,
  'password' => $pass,
]);
echo "relogin=".$login2->status()." firstLogin=".json_encode(data_get($login2->json(), 'user.user_metadata.firstLogin'))."\n";
$me = $http->withToken((string) data_get($login2->json(), 'session.access_token'))->acceptJson()
  ->get($base.'/pdf-tools/api/v1/auth/me');
echo "me firstLogin=".json_encode(data_get($me->json(), 'user.user_metadata.firstLogin'))."\n";
PHP
