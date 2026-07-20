#!/usr/bin/env sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear --no-interaction || true
php artisan route:clear --no-interaction || true
php artisan view:clear --no-interaction || true
php artisan package:discover --ansi --no-interaction || true
php artisan storage:link --no-interaction || true

# Queue worker (email, web push, listener in coda) — loop: --max-time termina il processo ogni ora
if ! pgrep -f "artisan queue:work" >/dev/null 2>&1; then
    su -s /bin/sh www-data -c 'while true; do php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /var/www/html/storage/logs/queue-worker.log 2>&1; sleep 1; done' &
fi

exec apache2-foreground
