#!/bin/bash
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
# Check select2 in plugins
$DOCKER exec gestiio-app sh -c "grep -c 'fn.select2' /var/www/html/public/assets_backend/plugins/global/plugins.bundle.js || true"
# Extract JS from rendered page is hard; instead check if compiled view has select2 init
$DOCKER exec gestiio-app php /var/www/html/artisan view:clear
# Copy host file into container again to be sure
cp -f /home/Carmine/apps/gestiio-20260624-2128/resources/views/Backend/Chat/index.blade.php /tmp/chat-index.blade.php 2>/dev/null || true
if [ -f /tmp/index.blade.php ]; then
  cp -f /tmp/index.blade.php /home/Carmine/apps/gestiio-20260624-2128/resources/views/Backend/Chat/index.blade.php
  $DOCKER cp /tmp/index.blade.php gestiio-app:/var/www/html/resources/views/Backend/Chat/index.blade.php
  echo COPIED_FROM_TMP
fi
# Verify attributes on template select inside container
$DOCKER exec gestiio-app sed -n '365,390p' /var/www/html/resources/views/Backend/Chat/index.blade.php
echo '---'
$DOCKER exec gestiio-app sed -n '1315,1355p' /var/www/html/resources/views/Backend/Chat/index.blade.php
