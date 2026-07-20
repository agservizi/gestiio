#!/bin/sh
# Optional: add local DNS hint via /etc/hosts on NAS for LAN tools
set -e
HOSTS=/etc/hosts
IP=188.114.97.7
if grep -q 'documenti.agenziaplinio.it' "$HOSTS" 2>/dev/null; then
  sed -i '/documenti\.agenziaplinio\.it/d' "$HOSTS"
fi
echo "$IP documenti.agenziaplinio.it" >> "$HOSTS"
echo "NAS hosts updated"
getent hosts documenti.agenziaplinio.it || true
curl -sI --connect-timeout 8 https://documenti.agenziaplinio.it/accounts/login/ | head -8
