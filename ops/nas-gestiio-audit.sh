#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
LOG=/home/Carmine/gestiio-audit.log
REPORT=/home/Carmine/gestiio-audit-latest.txt
ALERT=/Volume1/homes/Carmine/gestiio-alert.sh
CLI_TIMEOUT=20

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

alert() {
    if [ -x "$ALERT" ]; then
        "$ALERT" "$1" "$2"
    fi
}

run_timeout() {
    timeout "$CLI_TIMEOUT" "$@"
}

{
    echo "Gestiio NAS audit $(stamp)"
    echo
    echo "== Critical containers =="
    run_timeout "$DOCKER" ps -a --filter name=gestiio-db --filter name=gestiio-app --filter name=corehost_traefik --filter name=cloudflared_corehost --format "{{.Names}}|{{.Status}}"
    echo
    echo "== Unhealthy containers =="
    run_timeout "$DOCKER" ps -a --filter health=unhealthy --format "{{.Names}}|{{.Status}}" || true
    echo
    echo "== Exited containers =="
    run_timeout "$DOCKER" ps -a --filter status=exited --format "{{.Names}}|{{.Status}}" || true
    echo
    echo "== Disk =="
    df -h /Volume1 /tmp 2>/dev/null || df -h
    echo
    echo "== Memory =="
    free -h 2>/dev/null || true
    echo
    echo "== Docker disk usage =="
    run_timeout "$DOCKER" system df 2>/dev/null || echo "docker system df timed out"
} > "$REPORT"

cat "$REPORT" >> "$LOG"

if run_timeout "$DOCKER" ps -a --filter health=unhealthy --format "{{.Names}}" | grep -q .; then
    alert "Audit Gestiio: presenti container unhealthy. Vedi $REPORT" "WARNING"
fi

if run_timeout "$DOCKER" ps -a --filter status=exited --format "{{.Names}}" | grep -q .; then
    alert "Audit Gestiio: presenti container exited. Vedi $REPORT" "WARNING"
fi
