#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach ([16,17] as $id) {
  $a = App\Models\ChatMessageAttachment::with("messaggio")->find($id);
  if (!$a) { echo "id=$id MISSING\n"; continue; }
  $m = $a->messaggio;
  echo "id=$id attrs=".json_encode($a->getAttributes())."\n";
  echo "  messaggio_id_rel=".($m? $m->id : "NULL")." thread=".($m? $m->thread_id : "NULL")."\n";
}
'
