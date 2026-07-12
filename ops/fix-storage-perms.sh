#!/bin/sh
# Ripristina permessi storage dopo deploy (eseguire sempre come root nel container)
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage/framework
find /var/www/html/storage/framework/views -type f -name '*.php' -exec chmod 664 {} + 2>/dev/null || true
rm -f /var/www/html/storage/framework/views/*.php 2>/dev/null || true
su -s /bin/sh www-data -c 'cd /var/www/html && php artisan view:clear' 2>/dev/null || php artisan view:clear
