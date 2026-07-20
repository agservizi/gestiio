#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== controller snippet ==='
$DOCKER exec gestiio-app sed -n '247,280p' /var/www/html/app/Http/Controllers/Backend/ChatController.php

echo '=== attachments 16/17 ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach ([16,17] as $id) {
  $a = App\Models\ChatMessageAttachment::find($id);
  if (!$a) { echo "id=$id MISSING_ROW\n"; continue; }
  $rel = ltrim((string)$a->path_filename, "/");
  $local = Illuminate\Support\Facades\Storage::disk("local")->exists($rel);
  $public = Illuminate\Support\Facades\Storage::disk("public")->exists($rel);
  echo "id=$id mime={$a->mime_type} blocked=".(int)$a->is_blocked." path={$a->path_filename} local=".(int)$local." public=".(int)$public." msg={$a->chat_message_id}\n";
}
'
