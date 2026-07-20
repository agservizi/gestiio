#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec -i gestiio-app php <<'PHP'
<?php
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sso = $app->make(App\Http\Services\StirlingSsoService::class);
$base = rtrim((string) config('services.stirling.url'), '/');
$adminUser = (string) config('services.stirling.admin_user', 'admin');
$adminPass = (string) config('services.stirling.admin_password');
$http = Illuminate\Support\Facades\Http::timeout(30);

// Login as gestiio-2 (ROLE_ADMIN) if possible
$u2 = App\Models\User::find(2);
if (!$u2) { echo "no user 2\n"; exit(1); }
// temporarily bypass shared session for credentials of gestiio-2
$username = 'gestiio-2';
$secret = (string) config('services.stirling.user_secret', '');
if ($secret === '') {
  $secret = (string) config('services.stirling.admin_password', '').'|'.(string) config('app.key', 'gestiio');
}
$password = substr(hash_hmac('sha256', 'stirling-user:2', $secret), 0, 32);

$login = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username'=>$username,'password'=>$password,
]);
echo "gestiio-2 login=".$login->status()."\n";
if (!$login->successful()) {
  echo substr($login->body(),0,300)."\n";
  // try unlock via admin then again
  $adminTok = data_get($http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
    'username'=>$adminUser,'password'=>$adminPass,
  ])->json(), 'session.access_token');
  $http->withToken($adminTok)->post($base.'/pdf-tools/api/v1/user/admin/unlockUser/'.$username);
  $login = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
    'username'=>$username,'password'=>$password,
  ]);
  echo "gestiio-2 retry=".$login->status()."\n";
}
$token = data_get($login->json(), 'session.access_token');
if (!is_string($token) || $token==='') { echo "no token\n"; exit(1); }

$r = $http->withToken($token)->asForm()->post($base.'/pdf-tools/api/v1/user/admin/changePasswordForUser', [
  'username' => $adminUser,
  'newPassword' => $adminPass,
  'forcePasswordChange' => 'false',
]);
echo "changePasswordForUser => ".$r->status()." ".substr(preg_replace('/\s+/',' ',$r->body()),0,250)."\n";

// alternate: editUser endpoint?
foreach ([
  '/pdf-tools/api/v1/user/admin/updateUser',
  '/pdf-tools/api/v1/user/admin/editUser',
  '/pdf-tools/api/v1/user/admin/changeUserRole',
] as $path) {
  $x = $http->withToken($token)->asForm()->post($base.$path, [
    'username'=>$adminUser,
    'forceChange'=>'false',
    'forcePasswordChange'=>'false',
    'firstLogin'=>'false',
  ]);
  echo "$path => ".$x->status()." ".substr(preg_replace('/\s+/',' ',$x->body()),0,120)."\n";
}

$loginA = $http->acceptJson()->asJson()->post($base.'/pdf-tools/api/v1/auth/login', [
  'username'=>$adminUser,'password'=>$adminPass,
]);
echo "admin firstLogin=".json_encode(data_get($loginA->json(), 'user.user_metadata.firstLogin'))."\n";
PHP
