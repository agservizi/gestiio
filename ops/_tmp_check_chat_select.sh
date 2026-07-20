#!/bin/bash
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
echo "=== remote file snippet ==="
"$DOCKER" exec gestiio-app sed -n '365,400p' /var/www/html/resources/views/Backend/Chat/index.blade.php
echo "=== init search ==="
"$DOCKER" exec gestiio-app grep -c initChatComposerSelect2 /var/www/html/resources/views/Backend/Chat/index.blade.php || true
"$DOCKER" exec gestiio-app grep -c 'form-select-solid' /var/www/html/resources/views/Backend/Chat/index.blade.php || true
