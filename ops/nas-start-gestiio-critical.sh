#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
LOG=/home/Carmine/start-all-docker-containers.log
LOCK=/var/run/gestiio-critical-start.lock
READY_TIMEOUT=180
CLI_TIMEOUT=25

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

run_timeout() {
    timeout "$CLI_TIMEOUT" "$@"
}

cleanup() {
    rmdir "$LOCK" 2>/dev/null || true
}

trap cleanup EXIT INT TERM

if ! mkdir "$LOCK" 2>/dev/null; then
    log "critical startup already active"
    exit 0
fi

log "critical startup begin"

systemctl start docker >> "$LOG" 2>&1 || /etc/init.d/docker start >> "$LOG" 2>&1 || true
systemctl start DockerEngine >> "$LOG" 2>&1 || /etc/init.d/DockerEngine start >> "$LOG" 2>&1 || true

deadline=$(( $(date +%s) + READY_TIMEOUT ))
restart_attempted=0

while [ "$(date +%s)" -lt "$deadline" ]; do
    if run_timeout "$DOCKER" info >/dev/null 2>&1; then
        log "DockerEngine ready"
        break
    fi

    if [ "$restart_attempted" -eq 0 ]; then
        restart_attempted=1
        log "DockerEngine not ready, restart once"
        systemctl restart DockerEngine >> "$LOG" 2>&1 || /etc/init.d/DockerEngine restart >> "$LOG" 2>&1 || true
    fi

    sleep 5
done

if ! run_timeout "$DOCKER" info >/dev/null 2>&1; then
    log "DockerEngine not ready after ${READY_TIMEOUT}s"
    systemctl status docker DockerEngine --no-pager -l >> "$LOG" 2>&1 || true
    exit 1
fi

critical_containers="gestiio-db gestiio-app corehost_traefik cloudflared_corehost"
log "starting critical containers only: $critical_containers"

for container in $critical_containers; do
    if run_timeout "$DOCKER" inspect "$container" >/dev/null 2>&1; then
        if run_timeout "$DOCKER" start "$container" >> "$LOG" 2>&1; then
            log "critical started/already running: $container"
        else
            log "critical failed to start: $container"
        fi
    else
        log "critical container not found: $container"
    fi

    sleep 2
done

log "critical final container status"
run_timeout "$DOCKER" ps -a --filter name=gestiio-db --filter name=gestiio-app --filter name=corehost_traefik --filter name=cloudflared_corehost --format "{{.Names}}|{{.Status}}" >> "$LOG" 2>&1 || true

log "critical startup complete"
