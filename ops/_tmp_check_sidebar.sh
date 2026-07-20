#!/bin/sh
echo "=== HOST sidebar ==="
grep -n "Outreach\|PDF Tools\|Proforma" /home/Carmine/apps/gestiio-20260624-2128/resources/views/Backend/_layout/app-sidebar-menu.blade.php
echo "=== CONTAINER sidebar ==="
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app grep -n "Outreach\|PDF Tools\|Proforma" /var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
echo "=== MOUNTS ==="
/Volume1/@apps/DockerEngine/dockerd/bin/docker inspect gestiio-app --format '{{json .Mounts}}'
echo ""
echo "=== CONTEXT around PDF Tools in container ==="
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app sed -n '457,490p' /var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
