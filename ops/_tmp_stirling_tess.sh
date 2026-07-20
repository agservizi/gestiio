#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== host tessdata (mounted over image!) ==='
ls -la /home/Carmine/apps/stirling-pdf/tessdata | head -30
echo count=$(ls /home/Carmine/apps/stirling-pdf/tessdata 2>/dev/null | wc -l)

echo '=== container tessdata ==='
$DOCKER exec stirling-pdf sh -c 'ls /usr/share/tessdata | head -30; echo count=$(ls /usr/share/tessdata | wc -l); tesseract --list-langs 2>&1 | head -20'

echo '=== which tools unavailable? ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS http://127.0.0.1:8080/pdf-tools/api/v1/info/available-features 2>/dev/null | head -c 4000; echo; curl -sS http://127.0.0.1:8080/pdf-tools/api/v1/config 2>/dev/null | head -c 2000; echo'
