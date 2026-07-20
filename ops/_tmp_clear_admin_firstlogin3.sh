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
  'username' => $user, 'password' => $pass,
]);
$token = data_get($login->json(), 'session.access_token');
echo "before firstLogin=".json_encode(data_get($login->json(), 'user.user_metadata.firstLogin'))."\n";

$r = $http->withToken($token)->asForm()->post($base.'/pdf-tools/api/v1/user/change-password-on-login', [
  'currentPassword' => $pass,
  'newPassword' => $pass,
  'confirmPassword' => $pass,
]);
echo "on-login => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,220)."\n";

$login2 = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username'=>$user,'password'=>$pass,
]);
echo "after firstLogin=".json_encode(data_get($login2->json(), 'user.user_metadata.firstLogin'))."\n";
echo "me=".substr(json_encode(data_get($login2->json(), 'user')),0,300)."\n";
PHP
