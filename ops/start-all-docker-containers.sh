#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
LOG=/home/Carmine/start-all-docker-containers.log
LOCK=/var/run/start-all-docker-containers.lock
READY_TIMEOUT=300
CLI_TIMEOUT=45
MAX_ENGINE_RESTARTS=1

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
    log "another startup run is already active"
    exit 0
fi

log "startup begin"

systemctl start docker >> "$LOG" 2>&1 || /etc/init.d/docker start >> "$LOG" 2>&1 || true
systemctl start DockerEngine >> "$LOG" 2>&1 || /etc/init.d/DockerEngine start >> "$LOG" 2>&1 || true

engine_restart_count=0
deadline=$(( $(date +%s) + READY_TIMEOUT ))

while [ "$(date +%s)" -lt "$deadline" ]; do
    if run_timeout "$DOCKER" info >/dev/null 2>&1; then
        log "DockerEngine ready"
        break
    fi

    pids=$(pgrep -f "docker info|docker ps|$DOCKER info|$DOCKER ps" 2>/dev/null || true)
    if [ -n "$pids" ]; then
        log "killing stuck docker cli processes: $pids"
        kill $pids >/dev/null 2>&1 || true
    fi

    if [ "$engine_restart_count" -lt "$MAX_ENGINE_RESTARTS" ]; then
        engine_restart_count=$((engine_restart_count + 1))
        log "DockerEngine not ready, restart attempt $engine_restart_count"
        systemctl restart DockerEngine >> "$LOG" 2>&1 || /etc/init.d/DockerEngine restart >> "$LOG" 2>&1 || true
        sleep 10
    else
        sleep 5
    fi
done

if ! run_timeout "$DOCKER" info >/dev/null 2>&1; then
    log "DockerEngine not ready after ${READY_TIMEOUT}s"
    systemctl status docker DockerEngine --no-pager -l >> "$LOG" 2>&1 || true
    exit 1
fi

critical_containers="gestiio-db gestiio-app corehost_traefik cloudflared_corehost"
log "starting critical containers: $critical_containers"
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
done

containers=$(run_timeout "$DOCKER" ps -aq 2>>"$LOG" || true)
if [ -z "$containers" ]; then
    log "no containers found"
    exit 0
fi

log "starting all containers"
for container in $containers; do
    name=$(run_timeout "$DOCKER" inspect --format '{{.Name}}' "$container" 2>/dev/null | sed 's#^/##')
    [ -n "$name" ] || name="$container"
    if run_timeout "$DOCKER" start "$container" >> "$LOG" 2>&1; then
        log "started/already running: $name"
    else
        log "failed to start: $name"
    fi
done

log "final container status"
run_timeout "$DOCKER" ps -a --format "{{.Names}}|{{.Status}}" >> "$LOG" 2>&1 || true

log "startup complete"
