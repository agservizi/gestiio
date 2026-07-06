#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PUBLIC_URL=${PUBLIC_URL:-https://gestiio.agenziaplinio.it/login}
LOCAL_URL=${LOCAL_URL:-http://127.0.0.1:8090/login}
LOG=${LOG:-/home/Carmine/gestiio-healthcheck.log}

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

check_container() {
    name=$1
    if ! "$DOCKER" inspect "$name" >/dev/null 2>&1; then
        log "CRITICAL missing container: $name"
        return 1
    fi

    running=$("$DOCKER" inspect -f '{{.State.Running}}' "$name" 2>/dev/null || echo false)
    health=$("$DOCKER" inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$name" 2>/dev/null || echo unknown)

    if [ "$running" != "true" ]; then
        log "CRITICAL container stopped: $name"
        return 1
    fi

    if [ "$health" = "unhealthy" ]; then
        log "CRITICAL container unhealthy: $name"
        return 1
    fi

    log "OK container: $name health=$health"
}

check_http() {
    label=$1
    url=$2
    code=$(curl -k -L -o /dev/null -s -w "%{http_code}" --max-time 20 "$url" || echo 000)
    case "$code" in
        200|302)
            log "OK http $label code=$code url=$url"
            ;;
        *)
            log "CRITICAL http $label code=$code url=$url"
            return 1
            ;;
    esac
}

status=0

for container in gestiio-db gestiio-app corehost_traefik cloudflared_corehost; do
    check_container "$container" || status=1
done

check_http local "$LOCAL_URL" || status=1
check_http public "$PUBLIC_URL" || status=1

exit "$status"
