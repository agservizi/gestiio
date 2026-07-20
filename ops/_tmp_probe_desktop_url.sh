#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== app-config (LAN) ==='
curl -sS -m 10 http://127.0.0.1:8091/pdf-tools/api/v1/config/app-config | head -c 800; echo

echo '=== status paths ==='
for u in \
  'http://127.0.0.1:8091/pdf-tools/api/v1/info/status' \
  'http://127.0.0.1:8091/api/v1/info/status' \
  'http://127.0.0.1:8091/pdf-tools/' \
  'http://192.168.1.50:8091/pdf-tools/api/v1/info/status'
 do
  code=$(curl -sS -m 5 -o /tmp/st.out -w '%{http_code}' "$u" || echo ERR)
  echo "$code  $u  snip=$(head -c 80 /tmp/st.out | tr '\n' ' ')"
done

echo '=== firewall / iptables 8091 ==='
iptables -L INPUT -n 2>/dev/null | head -40 || true
iptables -L DOCKER -n 2>/dev/null | head -40 || true
# Synology/QNAP style
which firewall-cmd >/dev/null 2>&1 && firewall-cmd --list-ports || true

echo '=== cors / security headers on status ==='
curl -sSI -m 5 http://192.168.1.50:8091/pdf-tools/api/v1/info/status | head -30

echo '=== compose FRONTEND/BACKEND ==='
grep -E 'FRONTEND|BACKEND|DEFAULTLOCALE|8091' /home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml
