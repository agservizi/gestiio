#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo "== containers =="
$DOCKER ps -a --format '{{.Names}}' | grep -iE 'cloud|traefik|corehost|gestiio|seafile' || true

echo "== cloudflared inspect =="
$DOCKER inspect cloudflared_corehost >/tmp/cf.json 2>/dev/null || $DOCKER ps -a | head -30
python3 - <<'PY' 2>/dev/null || true
import json
try:
  d=json.load(open('/tmp/cf.json'))[0]
except Exception as e:
  print('no inspect', e); raise SystemExit
print('Image', d['Config']['Image'])
print('Cmd', d['Config'].get('Cmd'))
print('Env:')
for e in d['Config'].get('Env') or []:
  if 'TUNNEL' in e or 'TOKEN' in e or 'CONFIG' in e:
    print(' ', e[:80])
print('Mounts:')
for m in d.get('Mounts') or []:
  print(' ', m.get('Source'), '->', m.get('Destination'))
print('Networks:', list((d.get('NetworkSettings') or {}).get('Networks') or {}))
PY

echo "== find configs =="
find /home/Carmine /Volume1/docker /opt /etc -name '*cloudflared*' 2>/dev/null | head -40
find /home/Carmine /Volume1/docker -name 'config.yml' 2>/dev/null | head -40
$DOCKER exec cloudflared_corehost sh -c 'ls -la /etc/cloudflared 2>/dev/null; ls -la /home/nonroot/.cloudflared 2>/dev/null; cat /etc/cloudflared/config.yml 2>/dev/null | head -80' 2>/dev/null || true

echo "== traefik =="
$DOCKER inspect corehost_traefik --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}' 2>/dev/null || true
$DOCKER exec corehost_traefik sh -c 'ls /etc/traefik 2>/dev/null; ls /data 2>/dev/null; cat /etc/traefik/traefik.yml 2>/dev/null | head -40' 2>/dev/null || true
