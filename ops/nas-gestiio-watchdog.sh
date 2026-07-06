#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
START_SCRIPT=/Volume1/homes/Carmine/start-all-docker-containers.sh
LOG=/home/Carmine/gestiio-watchdog.log
LOCK=/var/run/gestiio-watchdog.lock
ALERT=/Volume1/homes/Carmine/gestiio-alert.sh
CLI_TIMEOUT=20
LOCAL_URL=http://127.0.0.1:8090/login
PUBLIC_URL=https://gestiio.agenziaplinio.it/login
BACKEND_URL=https://gestiio.agenziaplinio.it/backend

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

run_timeout() {
    timeout "$CLI_TIMEOUT" "$@"
}

alert() {
    if [ -x "$ALERT" ]; then
        "$ALERT" "$1" "$2" "${3:-}"
    fi
}

cleanup() {
    rmdir "$LOCK" 2>/dev/null || true
}

trap cleanup EXIT INT TERM

if ! mkdir "$LOCK" 2>/dev/null; then
    exit 0
fi

if ! run_timeout "$DOCKER" info >/dev/null 2>&1; then
    log "DockerEngine unavailable, running critical startup"
    alert "Gestiio watchdog: DockerEngine non disponibile, avvio recovery critico" "CRITICAL" "$BACKEND_URL"
    sh "$START_SCRIPT" >> "$LOG" 2>&1 || log "critical startup failed"
    exit 0
fi

for container in gestiio-db gestiio-app corehost_traefik cloudflared_corehost; do
    state=$(run_timeout "$DOCKER" inspect --format '{{.State.Running}}' "$container" 2>/dev/null || echo "missing")
    if [ "$state" != "true" ]; then
        log "container not running: $container state=$state"
        alert "Gestiio watchdog: container non attivo $container, avvio recovery" "CRITICAL" "$BACKEND_URL"
        sh "$START_SCRIPT" >> "$LOG" 2>&1 || log "critical startup failed after $container"
        break
    fi
done

local_code=$(curl -ksS -o /dev/null -w "%{http_code}" --max-time 10 "$LOCAL_URL" 2>/dev/null || echo "000")
if [ "$local_code" != "200" ] && [ "$local_code" != "302" ]; then
    log "local check failed code=$local_code, restarting app stack"
    alert "Gestiio watchdog: check locale fallito code=$local_code, restart app stack" "CRITICAL" "$BACKEND_URL"
    run_timeout "$DOCKER" restart gestiio-db gestiio-app corehost_traefik >> "$LOG" 2>&1 || true
    sleep 8
fi

public_code=$(curl -ksS -o /dev/null -w "%{http_code}" --max-time 15 "$PUBLIC_URL" 2>/dev/null || echo "000")
if [ "$public_code" != "200" ] && [ "$public_code" != "302" ]; then
    log "public check failed code=$public_code, restarting cloudflared and critical stack"
    alert "Gestiio watchdog: check pubblico fallito code=$public_code, restart tunnel" "CRITICAL" "$BACKEND_URL"
    run_timeout "$DOCKER" restart cloudflared_corehost >> "$LOG" 2>&1 || true
    sleep 8

    public_code_retry=$(curl -ksS -o /dev/null -w "%{http_code}" --max-time 15 "$PUBLIC_URL" 2>/dev/null || echo "000")
    if [ "$public_code_retry" != "200" ] && [ "$public_code_retry" != "302" ]; then
        log "public retry failed code=$public_code_retry, running critical startup"
        alert "Gestiio watchdog: retry pubblico fallito code=$public_code_retry, recovery critico" "CRITICAL" "$BACKEND_URL"
        sh "$START_SCRIPT" >> "$LOG" 2>&1 || log "critical startup failed after public retry"
    else
        log "public recovered after cloudflared restart code=$public_code_retry"
        alert "Gestiio watchdog: pubblico recuperato dopo restart tunnel code=$public_code_retry" "INFO" "$BACKEND_URL"
    fi
fi

final_local=$(curl -ksS -o /dev/null -w "%{http_code}" --max-time 10 "$LOCAL_URL" 2>/dev/null || echo "000")
final_public=$(curl -ksS -o /dev/null -w "%{http_code}" --max-time 15 "$PUBLIC_URL" 2>/dev/null || echo "000")
log "watchdog ok local=$final_local public=$final_public"
