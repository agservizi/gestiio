#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== current settings ==='
grep -n 'defaultLocale\|languages:\|appNameNavbar\|logoStyle\|Frontend\|frontendUrl\|enableAnalytics' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -30

echo '=== desktop url env ==='
grep STIRLING_DESKTOP /home/Carmine/apps/gestiio-20260624-2128/.env || true

echo '=== find language keys in SPA ==='
# Get a JS chunk and grep language storage keys
HTML=$($DOCKER exec stirling-pdf wget -qO- --header='Authorization: Bearer x' http://127.0.0.1:8080/pdf-tools/login 2>/dev/null || true)
# login works without auth
HTML=$($DOCKER exec stirling-pdf wget -qO- http://127.0.0.1:8080/pdf-tools/login)
echo "$HTML" | head -c 500; echo
JS=$(echo "$HTML" | grep -oE 'assets/[^"]+\.js' | head -5)
echo "js refs: $JS"
for j in $JS; do
  $DOCKER exec stirling-pdf wget -qO- "http://127.0.0.1:8080/pdf-tools/$j" 2>/dev/null | tr ',' '\n' | grep -E 'i18nextLng|languageCode|localStorage.*(lang|locale)|changeLanguage' | head -20
done
