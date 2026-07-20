#!/bin/sh
# Aggiunge invoice.agenziaplinio.it al Cloudflare Tunnel corehost (come documenti.agenziaplinio.it).
# Eseguire sul NAS (o: ssh Carmine@192.168.1.50 'bash -s' < ops/remote-add-invoice-tunnel.sh)
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CFG=/opt/cloudflared/config.yml

cp -f "$CFG" "$CFG.bak-invoice-$(date +%Y%m%d-%H%M%S)"

if grep -q 'hostname: invoice.agenziaplinio.it' "$CFG"; then
  echo "ingress già presente"
else
  python3 - <<'PY'
from pathlib import Path
p = Path('/opt/cloudflared/config.yml')
text = p.read_text()
block = """  - hostname: invoice.agenziaplinio.it
    service: http://localhost:8093
    originRequest:
      httpHostHeader: invoice.agenziaplinio.it
"""
marker = "  - service: https://localhost:4443"
if marker not in text:
    raise SystemExit('catch-all marker not found')
if 'hostname: invoice.agenziaplinio.it' in text:
    print('already present')
else:
    text = text.replace(marker, block + marker)
    p.write_text(text)
    print('config updated')
PY
fi

echo "== config snippet =="
grep -n -A3 'invoice.agenziaplinio.it' "$CFG" || true

$DOCKER exec cloudflared_corehost cloudflared tunnel route dns corehost invoice.agenziaplinio.it 2>&1 || \
$DOCKER exec cloudflared_corehost cloudflared tunnel route dns --overwrite-dns corehost invoice.agenziaplinio.it 2>&1 || \
echo "DNS_ROUTE_MANUAL_NEEDED"

$DOCKER restart cloudflared_corehost
sleep 5
$DOCKER ps --filter name=cloudflared_corehost --format '{{.Names}} {{.Status}}'

echo "== local invoiceshelf =="
curl -sI -H 'Host: invoice.agenziaplinio.it' http://127.0.0.1:8093/ | head -8

echo "== public =="
i=0
while [ $i -lt 18 ]; do
  code=$(curl -sI -o /dev/null -w '%{http_code}' --connect-timeout 5 https://invoice.agenziaplinio.it/ 2>/dev/null || echo 000)
  echo "try=$i code=$code"
  case "$code" in 200|301|302|303) break ;; esac
  i=$((i+1)); sleep 5
done

curl -sI --connect-timeout 8 https://invoice.agenziaplinio.it/ 2>&1 | head -12 || echo STILL_FAIL
echo DONE
