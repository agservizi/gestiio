#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CFG=/opt/cloudflared/config.yml

cp -f "$CFG" "$CFG.bak-documenti-$(date +%Y%m%d-%H%M%S)"

# Inserisci hostname documenti prima del catch-all finale (ultimo - service: senza hostname)
if grep -q 'hostname: documenti.agenziaplinio.it' "$CFG"; then
  echo "ingress già presente"
else
  python3 - <<'PY'
from pathlib import Path
p = Path('/opt/cloudflared/config.yml')
text = p.read_text()
block = """  - hostname: documenti.agenziaplinio.it
    service: http://localhost:8089
    originRequest:
      httpHostHeader: documenti.agenziaplinio.it
"""
# Insert before final catch-all entry that has only "service:" at ingress root
marker = "  - service: https://localhost:4443"
if marker not in text:
    raise SystemExit('catch-all marker not found')
if 'hostname: documenti.agenziaplinio.it' in text:
    print('already present')
else:
    text = text.replace(marker, block + marker)
    p.write_text(text)
    print('config updated')
PY
fi

echo "== config snippet =="
grep -n -A3 'documenti.agenziaplinio.it' "$CFG" || true

# DNS route via cloudflared (crea CNAME sul tunnel)
$DOCKER exec cloudflared_corehost cloudflared tunnel route dns corehost documenti.agenziaplinio.it 2>&1 || \
$DOCKER exec cloudflared_corehost cloudflared tunnel route dns --overwrite-dns corehost documenti.agenziaplinio.it 2>&1 || \
echo "DNS_ROUTE_MANUAL_NEEDED"

$DOCKER restart cloudflared_corehost
sleep 5
$DOCKER ps --filter name=cloudflared_corehost --format '{{.Names}} {{.Status}}'

echo "== local seafile =="
curl -sI -H 'Host: documenti.agenziaplinio.it' http://127.0.0.1:8089/ | head -8

echo "== public =="
# attesa propagazione breve
i=0
while [ $i -lt 12 ]; do
  code=$(curl -sI -o /dev/null -w '%{http_code}' --connect-timeout 5 https://documenti.agenziaplinio.it/ 2>/dev/null || echo 000)
  echo "try=$i code=$code"
  case "$code" in 200|301|302|303) break ;; esac
  i=$((i+1)); sleep 5
done

curl -sI --connect-timeout 8 https://documenti.agenziaplinio.it/ 2>&1 | head -12 || echo STILL_FAIL
echo DONE
