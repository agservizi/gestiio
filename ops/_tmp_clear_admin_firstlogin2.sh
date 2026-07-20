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
echo "firstLogin=".json_encode(data_get($login->json(), 'user.user_metadata.firstLogin'))."\n";

$auth = fn() => Illuminate\Support\Facades\Http::timeout(30)->withToken($token);

// 1) asForm
$r = $auth()->asForm()->post($base.'/pdf-tools/api/v1/user/change-password-on-login', [
  'currentPassword' => $pass,
  'newPassword' => $pass,
]);
echo "asForm => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

// 2) multipart Guzzle style
$r = $auth()->asMultipart()->post($base.'/pdf-tools/api/v1/user/change-password-on-login', [
  ['name'=>'currentPassword','contents'=>$pass],
  ['name'=>'newPassword','contents'=>$pass],
]);
echo "multipart => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

// 3) change-password (non first login)
$r = $auth()->asForm()->post($base.'/pdf-tools/api/v1/user/change-password', [
  'currentPassword' => $pass,
  'newPassword' => $pass,
]);
echo "change-password => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

// 4) admin changePasswordForUser
$r = $auth()->asForm()->post($base.'/pdf-tools/api/v1/user/admin/changePasswordForUser', [
  'username' => $user,
  'newPassword' => $pass,
  'forcePasswordChange' => 'false',
]);
echo "admin changePasswordForUser form => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

$r = $auth()->asMultipart()->post($base.'/pdf-tools/api/v1/user/admin/changePasswordForUser', [
  ['name'=>'username','contents'=>$user],
  ['name'=>'newPassword','contents'=>$pass],
  ['name'=>'forcePasswordChange','contents'=>'false'],
]);
echo "admin changePasswordForUser mp => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

// 5) saveUser with proper multipart
$r = $auth()->asMultipart()->post($base.'/pdf-tools/api/v1/user/admin/saveUser', [
  ['name'=>'username','contents'=>$user],
  ['name'=>'password','contents'=>$pass],
  ['name'=>'role','contents'=>'ROLE_ADMIN'],
  ['name'=>'authType','contents'=>'WEB'],
  ['name'=>'forceChange','contents'=>'false'],
]);
echo "saveUser => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,180)."\n";

$login2 = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username'=>$user,'password'=>$pass,
]);
echo "relogin firstLogin=".json_encode(data_get($login2->json(), 'user.user_metadata.firstLogin'))."\n";
PHP
