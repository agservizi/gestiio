#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== container ==='
$DOCKER ps --filter name=stirling-pdf --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}'

echo '=== env (feature-related) ==='
$DOCKER exec stirling-pdf sh -c 'env | sort | grep -Ei "DOCKER_|SYSTEM_|SECURITY_|DISABLE|ENABLE|UI_|METRICS|LANG|LICENSE|ENTERPRISE|FAT|ULTRA|VERSION" || true'

echo '=== settings.yml / configs ==='
$DOCKER exec stirling-pdf sh -c 'ls -la /configs 2>/dev/null; ls -la /configs/settings.yml 2>/dev/null; ls -la /configs/custom_settings.yml 2>/dev/null'
$DOCKER exec stirling-pdf sh -c 'head -n 120 /configs/settings.yml 2>/dev/null || head -n 120 /configs/custom_settings.yml 2>/dev/null || echo NO_SETTINGS'

echo '=== endpoints homepage tools count ==='
$DOCKER exec stirling-pdf sh -c 'wget -qO- http://127.0.0.1:8080/pdf-tools/ 2>/dev/null | head -c 500; echo; wget -qO- http://127.0.0.1:8080/pdf-tools/api/v1/info/status 2>/dev/null || wget -qO- http://127.0.0.1:8080/api/v1/info/status 2>/dev/null || true'
