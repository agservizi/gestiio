#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PASS=stirling
NEW=uYc36gDhd-3ti2UKHQ1g4MidqMeVTklL
TOKEN=$($DOCKER exec gestiio-app curl -sS -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"stirling"}' \
  http://stirling-pdf:8080/pdf-tools/api/v1/auth/login | python3 -c 'import sys,json; print(json.load(sys.stdin)["session"]["access_token"])')

# Query-string style
code=$($DOCKER exec gestiio-app curl -sS -o /tmp/chpw.json -w '%{http_code}' -X POST \
  -H "Authorization: Bearer $TOKEN" \
  "http://stirling-pdf:8080/pdf-tools/api/v1/user/change-password?currentPassword=$PASS&newPassword=$NEW")
echo "query style -> $code $($DOCKER exec gestiio-app head -c 200 /tmp/chpw.json)"

# form urlencoded
code=$($DOCKER exec gestiio-app curl -sS -o /tmp/chpw2.json -w '%{http_code}' -X POST \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "currentPassword=$PASS" --data-urlencode "newPassword=$NEW" \
  http://stirling-pdf:8080/pdf-tools/api/v1/user/change-password)
echo "form style -> $code $($DOCKER exec gestiio-app head -c 200 /tmp/chpw2.json)"
