#!/bin/bash
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
C=gestiio-app

# Ensure redis container is running on app network
NET=$("$DOCKER" inspect "$C" | python3 -c 'import sys,json; d=json.load(sys.stdin)[0]; print(next(iter(d["NetworkSettings"]["Networks"])))')
echo "NET=$NET"
if ! "$DOCKER" ps --format '{{.Names}}' | grep -qx gestiio-redis; then
  "$DOCKER" rm -f gestiio-redis 2>/dev/null || true
  "$DOCKER" run -d --name gestiio-redis --restart unless-stopped --network "$NET" \
    -v gestiio_redis_data:/data redis:7-alpine redis-server --appendonly yes --bind 0.0.0.0
  sleep 2
fi
"$DOCKER" exec gestiio-redis redis-cli ping

# Safe env first
cat > /tmp/set-safe-env.php <<'PHP'
<?php
$path = '/var/www/html/.env';
$env = file_get_contents($path);
$env = preg_replace('/^CACHE_DRIVER=.*/m', 'CACHE_DRIVER=file', $env);
$env = preg_replace('/^QUEUE_CONNECTION=.*/m', 'QUEUE_CONNECTION=database', $env);
file_put_contents($path, $env);
echo "SAFE_ENV_OK\n";
PHP
"$DOCKER" cp /tmp/set-safe-env.php "$C:/tmp/set-safe-env.php"
"$DOCKER" exec "$C" php /tmp/set-safe-env.php
"$DOCKER" exec "$C" php /var/www/html/artisan config:clear

echo "=== composer require predis ==="
"$DOCKER" exec -w /var/www/html -e COMPOSER_ALLOW_SUPERUSER=1 "$C" \
  composer require predis/predis:^2.0 --no-interaction --no-ansi 2>&1 | tail -50

cat > /tmp/set-redis-env.php <<'PHP'
<?php
$path = '/var/www/html/.env';
$env = file_get_contents($path);
$pairs = [
  'CACHE_DRIVER' => 'redis',
  'QUEUE_CONNECTION' => 'redis',
  'REDIS_HOST' => 'gestiio-redis',
  'REDIS_PORT' => '6379',
  'REDIS_CLIENT' => 'predis',
];
foreach ($pairs as $k => $v) {
  $line = $k.'='.$v;
  if (preg_match('/^'.preg_quote($k, '/').'=.*/m', $env)) {
    $env = preg_replace('/^'.preg_quote($k, '/').'=.*/m', $line, $env);
  } else {
    $env .= "\n".$line;
  }
}
file_put_contents($path, $env);
echo "ENV_REDIS_OK\n";
PHP
"$DOCKER" cp /tmp/set-redis-env.php "$C:/tmp/set-redis-env.php"
"$DOCKER" exec "$C" php /tmp/set-redis-env.php
"$DOCKER" exec "$C" php /var/www/html/artisan config:clear

cat > /tmp/test-redis-cache.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Cache::put('chat_redis_smoke', 'ok', 30);
$ok = Illuminate\Support\Facades\Cache::get('chat_redis_smoke') === 'ok';
echo $ok ? "REDIS_CACHE_OK\n" : "REDIS_CACHE_FAIL\n";
echo 'driver='.config('cache.default').' host='.config('database.redis.default.host').' client='.config('database.redis.client')."\n";
if (!$ok) { exit(1); }
PHP
"$DOCKER" cp /tmp/test-redis-cache.php "$C:/tmp/test-redis-cache.php"
"$DOCKER" exec "$C" php /tmp/test-redis-cache.php

"$DOCKER" exec "$C" mkdir -p /var/www/html/ops
"$DOCKER" cp /tmp/smoke-chat-interna.php "$C:/var/www/html/ops/smoke-chat-interna.php"
"$DOCKER" exec "$C" php -l /var/www/html/ops/smoke-chat-interna.php
"$DOCKER" exec "$C" php /var/www/html/ops/smoke-chat-interna.php

echo ALL_DONE
