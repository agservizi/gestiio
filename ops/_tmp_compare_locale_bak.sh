#!/bin/bash
set -euo pipefail
CFG=/home/Carmine/apps/stirling-pdf/configs
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== CURRENT locale lines ==='
grep -nE 'defaultLocale|languages:' "$CFG/settings.yml"

echo
echo '=== DIFF locale vs morning-ish bak (16:17 content / bak 200503) ==='
diff -u \
  <(grep -nE 'defaultLocale|languages:|enableLogin|rootUriPath|frontendUrl|appName' "$CFG/settings.yml.bak-locale-20260717200503") \
  <(grep -nE 'defaultLocale|languages:|enableLogin|rootUriPath|frontendUrl|appName' "$CFG/settings.yml") || true

echo
echo '=== ALL BAK locale lines ==='
for f in \
  settings.yml.bak-locale-20260717200503 \
  settings.yml.bak-it-20260717201051 \
  settings.yml.bak-fixlocale-20260717201606 \
  settings.yml.bak-locale-20260717202941 \
  settings.yml.bak-locale-20260717202958
do
  echo "--- $f ---"
  grep -nE 'defaultLocale|languages:' "$CFG/$f" || true
done

echo
echo '=== compose DEFAULTLOCALE ==='
grep -nE 'DEFAULTLOCALE|ROOTURI|FRONTENDURL' /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml || true

echo
echo '=== image ==='
$DOCKER inspect stirling-pdf --format 'Image={{.Config.Image}} Created={{.Created}}'
$DOCKER image ls stirlingtools/stirling-pdf --format '{{.ID}} {{.Tag}} {{.CreatedSince}}'

echo
echo '=== app-config locale from API ==='
$DOCKER exec stirling-pdf sh -c 'curl -sS -m 5 http://127.0.0.1:8080/pdf-tools/api/v1/config/app-config' | head -c 2000
echo

echo
echo '=== morning DB backups ==='
ls -la "$CFG/backup/db/"
