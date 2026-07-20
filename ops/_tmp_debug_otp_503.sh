#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== containers ==='
$DOCKER ps --format 'table {{.Names}}\t{{.Status}}' | head -30

echo '=== mail/queue env ==='
$DOCKER exec gestiio-app grep -E '^(MAIL_|QUEUE_|CACHE_|REDIS_|SESSION_)' /var/www/html/.env | sed 's/PASSWORD=.*/PASSWORD=***/;s/SECRET=.*/SECRET=***/;s/KEY=.*/KEY=***/' || true

echo '=== recent laravel errors ==='
$DOCKER exec gestiio-app sh -c 'tail -n 200 /var/www/html/storage/logs/laravel.log 2>/dev/null | grep -iE "otp|mail|smtp|2fa|two.factor|503|Swift|Symfony\\\\Component\\\\Mailer" | tail -40'

echo '=== last log errors ==='
$DOCKER exec gestiio-app sh -c 'tail -n 80 /var/www/html/storage/logs/laravel.log 2>/dev/null | tail -60'

echo '=== queue failed ==='
$DOCKER exec gestiio-app php /var/www/html/artisan queue:failed 2>/dev/null | head -20 || true

echo '=== redis ping ==='
$DOCKER exec gestiio-app php -r '
try {
  $h=getenv("REDIS_HOST")?: "127.0.0.1";
  $p=(int)(getenv("REDIS_PORT")?:6379);
  $s=@fsockopen($h,$p,$e,$e,$2);
  echo $s?"REDIS_OK $h:$p\n":"REDIS_FAIL $h:$p\n";
  if($s) fclose($s);
} catch(Throwable $ex) { echo $ex->getMessage(); }
' 2>/dev/null || true
$DOCKER exec gestiio-app grep -E '^REDIS_HOST=' /var/www/html/.env || true
