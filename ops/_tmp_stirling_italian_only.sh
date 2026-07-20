#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
YML=/home/Carmine/apps/stirling-pdf/configs/settings.yml
COMPOSE=/home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml

cp -a "$YML" "$YML.bak-locale-$(date +%Y%m%d%H%M%S)"

# Force Italian as default locale
perl -pi -e 's/^(\s*defaultLocale:\s*)""(.*)$/${1}"it-IT"${2}/' "$YML"
# Restrict UI languages to Italian only
perl -pi -e 's/^(\s*languages:\s*)\[\](.*)$/${1}["it_IT"]${2}/' "$YML"
# Disable analytics prompt (causa 500 su update-enable-analytics)
perl -pi -e 's/^(\s*enableAnalytics:\s*)null(.*)$/${1}false${2}/' "$YML"
perl -pi -e 's/^(\s*enablePosthog:\s*)null(.*)$/${1}false${2}/' "$YML"
perl -pi -e 's/^(\s*enableScarf:\s*)null(.*)$/${1}false${2}/' "$YML"

echo '=== locale lines ==='
grep -n 'defaultLocale\|languages:\|enableAnalytics\|enablePosthog\|enableScarf' "$YML" | head -20

# Ensure compose env
if ! grep -q 'UI_LANGUAGES' "$COMPOSE"; then
  perl -pi -e 's/SYSTEM_DEFAULTLOCALE: it-IT/SYSTEM_DEFAULTLOCALE: it-IT\n      UI_LANGUAGES: it_IT/' "$COMPOSE"
fi
grep -n 'DEFAULTLOCALE\|UI_LANGUAGES\|ANALYTICS' "$COMPOSE" | head -10

cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d
for i in $(seq 1 40); do
  st=$($DOCKER inspect -f '{{.State.Health.Status}}' stirling-pdf 2>/dev/null || echo starting)
  echo "health=$st"
  [ "$st" = "healthy" ] && break
  sleep 2
done
curl -sS -m 10 http://127.0.0.1:8091/pdf-tools/api/v1/info/status; echo
echo LOCALE_OK
