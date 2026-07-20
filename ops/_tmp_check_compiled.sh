#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
echo "=== compiled views with PDF Tools ==="
$DOCKER exec gestiio-app sh -c 'grep -l "PDF Tools" /var/www/html/storage/framework/views/*.php 2>/dev/null || true'
echo "=== compiled views with Outreach ==="
$DOCKER exec gestiio-app sh -c 'grep -l "Outreach" /var/www/html/storage/framework/views/*.php 2>/dev/null || true'
echo "=== dump matches ==="
$DOCKER exec gestiio-app sh -c 'for f in /var/www/html/storage/framework/views/*.php; do echo "== $f"; grep -n "PDF Tools\|Outreach\|Proforma" "$f" 2>/dev/null | head -20; done'
echo "=== force recompile by requesting via artisan view:cache after clear ==="
$DOCKER exec gestiio-app sh -c 'rm -f /var/www/html/storage/framework/views/*.php; php artisan view:clear'
echo CLEARED
