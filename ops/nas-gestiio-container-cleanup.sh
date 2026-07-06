#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
LOG=/home/Carmine/gestiio-container-cleanup.log
APPLY=${APPLY:-0}

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

critical=" gestiio-db gestiio-app corehost_traefik cloudflared_corehost "

log "cleanup scan begin apply=$APPLY"

"$DOCKER" ps -a --filter status=exited --format "{{.Names}}" | while read name; do
    [ "$name" != "" ] || continue
    case "$critical" in
        *" $name "*) log "skip critical $name"; continue ;;
    esac

    log "candidate exited container: $name"

    if [ "$APPLY" = "1" ]; then
        "$DOCKER" rm "$name" >> "$LOG" 2>&1 || log "failed remove $name"
    fi
done

log "cleanup scan complete"
