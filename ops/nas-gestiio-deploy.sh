#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_CONTAINER=gestiio-app
DEPLOY_DIR=/Volume1/homes/Carmine/gestiio-latest-deploy
RELEASES=/Volume1/homes/Carmine/gestiio-releases
HEALTH=/Volume1/homes/Carmine/gestiio-healthcheck.sh
LOG=/home/Carmine/gestiio-deploy.log
ALERT=/Volume1/homes/Carmine/gestiio-alert.sh

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

alert() {
    if [ -x "$ALERT" ]; then
        "$ALERT" "$1" "$2"
    fi
}

rollback() {
    previous=$(ls -1dt "$RELEASES"/* 2>/dev/null | sed -n '2p')
    if [ "$previous" = "" ]; then
        log "rollback unavailable: no previous release"
        alert "Deploy Gestiio fallito e rollback non disponibile" "CRITICAL"
        exit 1
    fi

    log "rollback to $previous"
    rsync -a --delete --exclude='.env' "$previous"/ "$DEPLOY_DIR"/ >> "$LOG" 2>&1
    "$DOCKER" cp "$DEPLOY_DIR"/. "$APP_CONTAINER:/var/www/html" >> "$LOG" 2>&1
    "$DOCKER" exec "$APP_CONTAINER" sh -lc "cd /var/www/html && php artisan optimize && php artisan view:cache" >> "$LOG" 2>&1 || true
    "$DOCKER" restart "$APP_CONTAINER" >> "$LOG" 2>&1 || true
    alert "Rollback Gestiio eseguito verso $previous" "WARNING"
}

log "deploy begin"
mkdir -p "$RELEASES"

release="$RELEASES/$(date '+%Y%m%d-%H%M%S')"
mkdir -p "$release"
rsync -a --delete --exclude='.env' "$DEPLOY_DIR"/ "$release"/ >> "$LOG" 2>&1
log "snapshot created: $release"

if ! "$DOCKER" exec "$APP_CONTAINER" sh -lc "cd /var/www/html && php artisan migrate --no-interaction --force" >> "$LOG" 2>&1; then
    log "artisan migrate failed"
    rollback
    exit 1
fi

if ! "$DOCKER" exec "$APP_CONTAINER" sh -lc "cd /var/www/html && php artisan config:clear && php artisan cache:clear && php artisan optimize && php artisan view:cache" >> "$LOG" 2>&1; then
    log "artisan optimize failed"
    rollback
    exit 1
fi

"$DOCKER" restart "$APP_CONTAINER" >> "$LOG" 2>&1 || true
sleep 8

if ! "$HEALTH" >> "$LOG" 2>&1; then
    log "healthcheck failed after deploy"
    rollback
    exit 1
fi

find "$RELEASES" -maxdepth 1 -mindepth 1 -type d | sort -r | sed -n '6,$p' | while read old; do
    rm -rf "$old"
done

log "deploy complete"
alert "Deploy Gestiio completato con healthcheck OK" "INFO"
