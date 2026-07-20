#!/bin/sh
# Avvia (se assente) il queue worker nel container gestiio-app sul NAS.
set -e

HOST="${GESTIIO_DEPLOY_HOST:-Carmine@192.168.1.50}"
DOCKER="${GESTIIO_DOCKER_BIN:-/Volume1/@apps/DockerEngine/dockerd/bin/docker}"
CONTAINER="${GESTIIO_CONTAINER:-gestiio-app}"

ssh "$HOST" "$DOCKER exec $CONTAINER sh -lc '
if pgrep -f \"php artisan queue:work\" >/dev/null 2>&1; then
  echo QUEUE_WORKER_ALREADY_RUNNING
  pgrep -af \"php artisan queue:work\"
  exit 0
fi
su -s /bin/sh www-data -c \"while true; do php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /var/www/html/storage/logs/queue-worker.log 2>&1; sleep 1; done\" &
sleep 2
pgrep -af \"php artisan queue:work\" || { echo QUEUE_WORKER_START_FAILED; exit 1; }
echo QUEUE_WORKER_STARTED
'"
