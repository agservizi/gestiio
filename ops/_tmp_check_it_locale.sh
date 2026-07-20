#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CFG=/home/Carmine/apps/stirling-pdf/configs

echo '=== settings ==='
grep -nE 'defaultLocale|languages:' "$CFG/settings.yml"

echo '=== app-config (no auth) ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS -m 5 http://127.0.0.1:8080/pdf-tools/api/v1/config/app-config' | head -c 3000
echo

echo '=== find it locale files ==='
$DOCKER exec stirling-pdf sh -c 'find /app /usr -iname "*it-IT*" 2>/dev/null | head -40'
$DOCKER exec stirling-pdf sh -c 'find /app -iname "*it_IT*" 2>/dev/null | head -20'
$DOCKER exec stirling-pdf sh -c 'ls /app/frontend 2>/dev/null; ls /app/static 2>/dev/null | head; find /app -name "messages*.toml" 2>/dev/null | head; find /app -path "*i18n*" 2>/dev/null | head -30'

echo '=== version ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS http://127.0.0.1:8080/pdf-tools/api/v1/info/status'
echo
