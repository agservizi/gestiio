#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate attachment() missing-file branch for id 16
$attachment = App\Models\ChatMessageAttachment::find(16);
$rel = ltrim((string)$attachment->path_filename, "/");
$local = Illuminate\Support\Facades\Storage::disk("local")->exists($rel);
$public = Illuminate\Support\Facades\Storage::disk("public")->exists($rel);
echo "exists local=$local public=$public\n";

// Also search similar filenames anywhere under storage
$cmd = "find /var/www/html/storage -name \"*rd1NSbCMkV88*\" 2>/dev/null | head";
echo "find: "; passthru($cmd);
$cmd2 = "find /var/www/html/storage -name \"*WA0076*\" 2>/dev/null | head";
echo "find orig: "; passthru($cmd2);

// List chat-allegati dirs
passthru("ls -la /var/www/html/storage/app/chat-allegati 2>/dev/null | head -20 || echo NO_LOCAL_DIR");
passthru("ls -la /var/www/html/storage/app/public/chat-allegati 2>/dev/null | head -20 || echo NO_PUBLIC_DIR");
passthru("ls -la /var/www/html/public/storage/chat-allegati 2>/dev/null | head -10 || echo NO_PUBLIC_LINK");
'

echo '=== route ==='
$DOCKER exec gestiio-app php /var/www/html/artisan route:list --path=chat-interna/attachment 2>/dev/null | head -20
