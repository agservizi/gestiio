#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(2);
if (!$user) { echo "NO_USER_2\n"; exit(1); }
Auth::login($user);

$req = Illuminate\Http\Request::create("/backend/chat-interna/attachment/16", "GET");
$req->setLaravelSession($app["session"]->driver());
$app["session"]->start();
Auth::login($user);

try {
  $response = $kernel->handle($req);
  echo "status=".$response->getStatusCode()."\n";
  echo "ctype=".$response->headers->get("Content-Type")."\n";
  echo "missing=".$response->headers->get("X-Chat-Attachment-Missing")."\n";
  echo "len=".strlen($response->getContent())."\n";
  if ($response->getStatusCode() >= 400) {
    echo substr(strip_tags($response->getContent()), 0, 300)."\n";
  }
} catch (Throwable $e) {
  echo "EX=".$e->getMessage()."\n".$e->getFile().":".$e->getLine()."\n";
}
'
