#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ids = [16, 17];
$deleted = App\Models\ChatMessageAttachment::whereIn("id", $ids)->delete();
echo "deleted_rows=$deleted\n";
foreach ($ids as $id) {
  echo "id=$id still=". (App\Models\ChatMessageAttachment::find($id) ? "yes" : "no") ."\n";
}
'
