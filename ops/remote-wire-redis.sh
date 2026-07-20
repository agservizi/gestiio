#!/bin/bash
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
C=gestiio-app

# Network name of gestiio-app (first network)
NET=$("$DOCKER" inspect "$C" | python3 -c 'import sys,json; d=json.load(sys.stdin)[0]; print(next(iter(d["NetworkSettings"]["Networks"])))')
echo "NET=$NET"

# Remove failed/old redis container if any
"$DOCKER" rm -f gestiio-redis 2>/dev/null || true

# Start Redis on app network only (no host port — host already uses 6379)
"$DOCKER" run -d \
  --name gestiio-redis \
  --restart unless-stopped \
  --network "$NET" \
  -v gestiio_redis_data:/data \
  redis:7-alpine \
  redis-server --appendonly yes --bind 0.0.0.0

sleep 2
"$DOCKER" exec gestiio-redis redis-cli ping

# Point app .env to container redis
"$DOCKER" exec "$C" php -r '
$path = "/var/www/html/.env";
$env = file_get_contents($path);
$pairs = [
  "CACHE_DRIVER" => "redis",
  "QUEUE_CONNECTION" => "redis",
  "REDIS_HOST" => "gestiio-redis",
  "REDIS_PORT" => "6379",
  "REDIS_CLIENT" => "predis",
];
foreach ($pairs as $k => $v) {
  $line = $k . "=" . $v;
  if (preg_match("/^" . preg_quote($k, "/") . "=.*/m", $env)) {
    $env = preg_replace("/^" . preg_quote($k, "/") . "=.*/m", $line, $env);
  } else {
    $env .= "\n" . $line;
  }
}
file_put_contents($path, $env);
echo "ENV_OK\n";
'

# Prefer predis if phpredis missing
"$DOCKER" exec "$C" php -r 'echo extension_loaded("redis") ? "PHPREDIS_YES\n" : "PHPREDIS_NO\n";'

"$DOCKER" exec "$C" php /var/www/html/artisan config:clear
"$DOCKER" exec "$C" php /var/www/html/artisan cache:clear || true

# Smoke redis from app
"$DOCKER" exec "$C" php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  Illuminate\Support\Facades\Cache::put("chat_redis_smoke", "ok", 30);
  $v = Illuminate\Support\Facades\Cache::get("chat_redis_smoke");
  echo ($v === "ok") ? "REDIS_CACHE_OK\n" : "REDIS_CACHE_FAIL\n";
} catch (Throwable $e) {
  echo "REDIS_CACHE_ERR: " . $e->getMessage() . "\n";
  exit(1);
}
'

echo REDIS_WIRE_OK
