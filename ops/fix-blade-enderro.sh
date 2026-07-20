#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

# (?!r) evita di toccare @endfor / @enderror durante la correzione di @enderro
ssh "$HOST" "find $APP/resources/views -name '*.blade.php' -exec perl -pi -e 's/\\@enderro(?!r)/\\@enderror/g' {} +"

ssh "$HOST" "$DOCKER exec $CONTAINER find /var/www/html/resources/views -name '*.blade.php' -exec perl -pi -e 's/\\@enderro(?!r)/\\@enderror/g' {} +"

ssh "$HOST" "$DOCKER exec $CONTAINER grep -rn ' @enderro' /var/www/html/resources/views --include='*.blade.php' | grep -v '@enderror' || echo 'NONE'"

ssh "$HOST" "$DOCKER exec -u www-data $CONTAINER php /var/www/html/artisan view:clear"
ssh "$HOST" "$DOCKER restart $CONTAINER"
sleep 6
ssh "$HOST" "curl -s -o /dev/null -w 'login=%{http_code}\n' http://localhost:8090/login"
echo FIX_BLADE_ENDERRO_OK
