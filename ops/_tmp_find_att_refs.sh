#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== deployed _messages check ==='
$DOCKER exec gestiio-app grep -n 'fileExists\|Allegato non disponibile\|attachment' /var/www/html/resources/views/Backend/Chat/_messages.blade.php | head -40

echo '=== other chat views with attachment ==='
$DOCKER exec gestiio-app sh -c 'grep -rn "attachment\|chat-image\|allegato" /var/www/html/resources/views/Backend/Chat/ --include="*.blade.php" | head -60'

echo '=== DB: messages 251/252 still in thread 1? ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach ([251,252] as $id) {
  $m = App\Models\ChatMessage::with("allegati")->find($id);
  if (!$m) { echo "msg $id gone\n"; continue; }
  echo "msg $id thread={$m->thread_id} deleted=".($m->deleted_at??"null")." allegati=".$m->allegati->count()."\n";
  foreach ($m->allegati as $a) {
    echo "  att {$a->id} path={$a->path_filename}\n";
  }
}
'
