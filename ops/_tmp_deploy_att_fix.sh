#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

for f in ChatController.php chat-messages.blade.php chat-index.blade.php web-backend.php; do
  perl -pi -e 's/\r\n/\n/g' /tmp/$f
done

cp -f /tmp/ChatController.php "$APP/app/Http/Controllers/Backend/ChatController.php"
cp -f /tmp/chat-messages.blade.php "$APP/resources/views/Backend/Chat/_messages.blade.php"
cp -f /tmp/chat-index.blade.php "$APP/resources/views/Backend/Chat/index.blade.php"
cp -f /tmp/web-backend.php "$APP/routes/web-backend.php"

$DOCKER cp /tmp/ChatController.php gestiio-app:/var/www/html/app/Http/Controllers/Backend/ChatController.php
$DOCKER cp /tmp/chat-messages.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/_messages.blade.php
$DOCKER cp /tmp/chat-index.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/index.blade.php
$DOCKER cp /tmp/web-backend.php gestiio-app:/var/www/html/routes/web-backend.php

$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear
$DOCKER exec gestiio-app php /var/www/html/artisan route:clear

echo '=== verify attachment 16 as guest-like request via kernel ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::find(2);
Auth::login($user);
foreach (["/backend/chat-interna/attachment/16","/backend/chat-interna/file/16"] as $uri) {
  $req = Illuminate\Http\Request::create($uri, "GET");
  $res = $kernel->handle($req);
  echo $uri." -> ".$res->getStatusCode()." ctype=".$res->headers->get("Content-Type")." miss=".$res->headers->get("X-Chat-Attachment-Missing")."\n";
}
'

echo '=== markers ==='
$DOCKER exec gestiio-app grep -n 'attachmentMissingPlaceholderResponse\|chat-interna/file\|scrubLegacyAttachmentImages\|MutationObserver' \
  /var/www/html/app/Http/Controllers/Backend/ChatController.php \
  /var/www/html/routes/web-backend.php \
  /var/www/html/resources/views/Backend/Chat/index.blade.php | head -25
echo DEPLOY_OK
