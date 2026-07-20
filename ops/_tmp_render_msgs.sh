#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(2);
Auth::login($user);

$msgs = App\Models\ChatMessage::with(["allegati","mittente","reazioni","replyTo.mittente","inoltratoDa"])
  ->whereIn("id", [251,252])
  ->get();

foreach ($msgs as $m) {
  foreach ($m->allegati as $a) {
    $rel = ltrim((string)$a->path_filename, "/");
    $local = Illuminate\Support\Facades\Storage::disk("local")->exists($rel);
    $public = Illuminate\Support\Facades\Storage::disk("public")->exists($rel);
    echo "att {$a->id} exists local=".(int)$local." public=".(int)$public."\n";
  }
}

$html = view("Backend.Chat._messages", [
  "messaggi" => $msgs,
  "altroLastReadAt" => null,
])->render();

echo "--- HTML snippet ---\n";
if (preg_match_all("/attachment\\/(16|17)[^\"\\s]*/", $html, $m)) {
  echo "FOUND URLS:\n";
  foreach (array_unique($m[0]) as $u) echo "  $u\n";
} else {
  echo "NO attachment/16|17 URLs in rendered HTML\n";
}
if (str_contains($html, "Allegato non disponibile")) {
  echo "HAS unavailable badge\n";
}
echo "img count=".substr_count($html, "<img")."\n";
'
