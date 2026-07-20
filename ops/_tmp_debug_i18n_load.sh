#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== settings.yml locale ==='
grep -nE 'defaultLocale|languages:' /home/Carmine/apps/stirling-pdf/configs/settings.yml

echo '=== locales via stirling direct ==='
for p in \
  locales/it-IT/translation.toml \
  locales/en-GB/translation.toml \
  locales/it-IT/messages.toml \
  locales/it-IT/tools.toml
do
  code=$($DOCKER exec stirling-pdf sh -c "curl -sS -m 3 -o /tmp/l.out -w %{http_code} http://127.0.0.1:8080/pdf-tools/$p")
  size=$(wc -c < /tmp/l.out 2>/dev/null || $DOCKER exec stirling-pdf wc -c /tmp/l.out 2>/dev/null | awk '{print $1}')
  echo "direct $p => $code bytes=$size"
done

echo '=== locales via gestiio local proxy (8090) ==='
for p in locales/it-IT/translation.toml locales/en-GB/translation.toml; do
  code=$(curl -sS -m 5 -o /tmp/g.out -w '%{http_code}' "http://127.0.0.1:8090/pdf-tools/$p" || echo 000)
  echo "proxy $p => $code bytes=$(wc -c </tmp/g.out) ctype=$(file -b /tmp/g.out | head -c 60)"
  head -c 120 /tmp/g.out; echo
done

echo '=== public https locales ==='
code=$(curl -sS -m 8 -o /tmp/p.out -w '%{http_code}' 'https://gestiio.agenziaplinio.it/pdf-tools/locales/it-IT/translation.toml' || echo 000)
echo "public it-IT => $code bytes=$(wc -c </tmp/p.out)"
head -c 200 /tmp/p.out; echo

echo '=== sample Italian strings in toml ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS http://127.0.0.1:8080/pdf-tools/locales/it-IT/translation.toml' | grep -iE 'upload|merge|search|file|drop' | head -20

echo '=== index.html base href ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS http://127.0.0.1:8080/pdf-tools/ | head -c 1500'
echo
