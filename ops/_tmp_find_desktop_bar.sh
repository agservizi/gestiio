#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== search App Windows Stirling on host ==='
grep -Rsn 'App Windows Stirling' /home/Carmine/apps 2>/dev/null | head -40 || true

echo '=== search in all containers ==='
for c in $($DOCKER ps --format '{{.Names}}'); do
  hits=$($DOCKER exec "$c" sh -c "grep -Rsn 'App Windows Stirling' /var/www /app /usr/share/nginx 2>/dev/null | head -5" || true)
  if [ -n "$hits" ]; then
    echo "CONTAINER $c"
    echo "$hits"
  fi
done

echo '=== gestiio-app PdfTools index ==='
$DOCKER exec gestiio-app wc -l /var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER exec gestiio-app grep -n 'App Windows\|desktop-bar\|8091\|8092\|192.168' /var/www/html/resources/views/Backend/PdfTools/index.blade.php || echo 'NO_MATCH_IN_CONTAINER'

echo '=== which processes / apps on 8090 ==='
$DOCKER ps --format 'table {{.Names}}\t{{.Ports}}' | head -40

echo '=== curl public page snippet (needs auth likely) ==='
curl -sS -m 10 -o /tmp/pt.html -w 'code=%{http_code}\n' https://gestiio.agenziaplinio.it/backend/pdf-tools || true
grep -o 'App Windows Stirling\|gestiio-pdf-tools-v3\|8091\|8092\|192.168.1.80' /tmp/pt.html | sort | uniq -c || true

echo '=== local 8090 pdf-tools backend ==='
curl -sS -m 5 -o /tmp/pt2.html -w 'code=%{http_code}\n' -H 'Host: gestiio.agenziaplinio.it' http://127.0.0.1:8090/backend/pdf-tools || true
grep -o 'App Windows Stirling\|gestiio-pdf-tools-v3\|pdf-tools-desktop\|enterUrl\|192.168' /tmp/pt2.html | sort | uniq -c || true
head -c 200 /tmp/pt2.html; echo
