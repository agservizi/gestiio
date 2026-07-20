#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== container PdfTools ==='
$DOCKER exec gestiio-app grep -n 'App Windows\|desktop-bar\|192.168\|gestiio-pdf-tools-v3' /var/www/html/resources/views/Backend/PdfTools/index.blade.php || echo NO_MATCH_CONTAINER
$DOCKER exec gestiio-app wc -l /var/www/html/resources/views/Backend/PdfTools/index.blade.php
$DOCKER exec gestiio-app sed -n '1,90p' /var/www/html/resources/views/Backend/PdfTools/index.blade.php

echo '=== host PdfTools ==='
grep -n 'App Windows\|desktop-bar\|192.168\|gestiio-pdf-tools-v3' /home/Carmine/apps/gestiio-20260624-2128/resources/views/Backend/PdfTools/index.blade.php || echo NO_MATCH_HOST

echo '=== all gestiio app folders ==='
ls -d /home/Carmine/apps/gestiio* 2>/dev/null || true
for d in /home/Carmine/apps/gestiio*; do
  f="$d/resources/views/Backend/PdfTools/index.blade.php"
  if [ -f "$f" ]; then
    if grep -q 'App Windows Stirling' "$f"; then
      echo "HAS_BAR $f"
    else
      echo "NO_BAR $f"
    fi
  fi
done

echo '=== containers ==='
$DOCKER ps --format '{{.Names}} {{.Image}} {{.Ports}}' | grep -iE 'gestiio|nginx|caddy|traefik' || true

echo '=== cloudflared / proxy origin ==='
$DOCKER logs cloudflared_corehost 2>/dev/null | tail -5 || true
grep -Rsn '8090\|gestiio' /home/Carmine/apps/*/config*.yml 2>/dev/null | head -20 || true

echo DONE
