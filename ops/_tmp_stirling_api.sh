#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

$DOCKER exec stirling-pdf sh -c 'which curl wget; curl -sS http://127.0.0.1:8080/pdf-tools/api/v1/info/status || true; echo; curl -sS http://127.0.0.1:8080/pdf-tools/api/v1/info/endpoints 2>/dev/null | head -c 3000; echo; curl -sS -o /tmp/home.html -w "%{http_code}" http://127.0.0.1:8080/pdf-tools/; echo; grep -oE "data-endpoint=\"[^\"]+\"" /tmp/home.html 2>/dev/null | sort -u | wc -l; grep -oE "href=\"[^\"]*tool[^\"]*\"" /tmp/home.html 2>/dev/null | head; ls /tmp/home.html; wc -c /tmp/home.html'

echo '=== enableLogin effective ==='
$DOCKER exec stirling-pdf sh -c 'grep -n "enableLogin\|^security:" -A2 /configs/settings.yml | head -20'
$DOCKER exec stirling-pdf printenv SECURITY_ENABLELOGIN

echo '=== compose on NAS ==='
head -40 /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml
