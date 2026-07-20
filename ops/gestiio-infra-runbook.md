# Gestiio Infrastructure Runbook

## Scope

This runbook covers the current production stack on the TerraMaster NAS:

- DockerEngine / DockerApi services
- `gestiio-db`
- `gestiio-app`
- `stirling-pdf` (PDF Tools, rete interna Gestiio)
- `corehost_traefik`
- `cloudflared_corehost`
- Cloudflare Tunnel for `gestiio.agenziaplinio.it`
- storage/public asset recovery

## Critical Paths

- Public login: `https://gestiio.agenziaplinio.it/login`
- Backend redirect: `https://gestiio.agenziaplinio.it/backend`
- Local app origin: `http://127.0.0.1:8090/login`
- Cloudflared origin rule observed in logs: `http://localhost:8090`

## Boot Recovery

The boot recovery entrypoint is:

```sh
/Volume1/homes/Carmine/start-all-docker-containers.sh
```

It is managed by:

```sh
systemctl status start-all-docker-containers.service
```

The script starts critical containers first:

- `gestiio-db`
- `gestiio-app`
- `corehost_traefik`
- `cloudflared_corehost`

Then it starts the remaining Docker containers (incl. `stirling-pdf` se presente).

PDF Tools: container `stirling-pdf` sulla rete `gestiio-20260624-2128_default`, compose in `/home/Carmine/apps/stirling-pdf/docker-compose.stirling.yml` (repo: `ops/docker-compose.stirling.yml`). UI Gestiio: `/backend/pdf-tools`.

## Health Check

Install/check the health script at:

```sh
/Volume1/homes/Carmine/gestiio-healthcheck.sh
```

Manual run:

```sh
/Volume1/homes/Carmine/gestiio-healthcheck.sh
tail -80 /home/Carmine/gestiio-healthcheck.log
```

Expected:

- critical containers are running
- `gestiio-db` is healthy
- local HTTP returns `200` or `302`
- public HTTP returns `200` or `302`

## Incident: Cloudflare 1033 / 530

Symptoms:

- Browser shows Cloudflare `1033`
- Cloudflare cannot resolve tunnel
- or `530` while tunnel/origin is recovering

Checks:

```sh
/Volume1/@apps/DockerEngine/dockerd/bin/docker ps --filter name=cloudflared_corehost
/Volume1/@apps/DockerEngine/dockerd/bin/docker logs --tail 120 cloudflared_corehost
curl -I --max-time 30 https://gestiio.agenziaplinio.it/backend
```

Fast mitigation:

```sh
/Volume1/@apps/DockerEngine/dockerd/bin/docker start gestiio-db gestiio-app corehost_traefik cloudflared_corehost
```

If DNS inside the tunnel times out, verify host DNS:

```sh
cat /etc/resolv.conf
nslookup region1.v2.argotunnel.com 1.1.1.1
```

## Incident: DockerEngine API Hangs

Symptoms:

- `dockerd` exists
- `docker ps` or `docker info` hangs or times out
- containers may be stopped after power loss

Checks:

```sh
systemctl status docker DockerEngine --no-pager -l
ps -o pid,ppid,stat,wchan:30,etime,cmd -p $(pgrep -f dockerd | head -1)
ss -xlpn | grep docker
```

Preferred mitigation:

```sh
systemctl restart DockerEngine
sleep 10
/Volume1/@apps/DockerEngine/dockerd/bin/docker ps -a
```

Avoid repeated aggressive restarts while Docker is rebuilding state.

## Incident: Slow DNS From NAS

Symptoms:

- public health check is much slower than local app check
- `curl https://gestiio.agenziaplinio.it/login` from NAS spends seconds in DNS lookup
- `nslookup` shows timeout on upstream DNS servers

Checks:

```sh
cat /etc/resolv.conf
resolvectl status
time nslookup gestiio.agenziaplinio.it
curl -L -o /dev/null -s -w 'dns=%{time_namelookup} total=%{time_total}\n' https://gestiio.agenziaplinio.it/login
```

Current fix:

```text
/etc/systemd/resolved.conf
DNS=1.1.1.1 8.8.8.8 192.168.1.254
FallbackDNS=1.0.0.1 8.8.4.4
DNSStubListener=yes
```

Apply after changes:

```sh
systemctl restart systemd-resolved
```

## Incident: Missing Public Assets

Symptoms:

- broken image icons
- `/storage/...` returns 404

Checks:

```sh
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app php artisan storage:link
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app ls -lah /var/www/html/public/storage
```

Current fallback:

```text
/images/logo-placeholder.svg
```

The model `App\Models\Gestore::immagineLogo()` returns `/storage/...` only when the file exists, otherwise the fallback placeholder is used.

## Performance

Laravel optimization/cache is enabled in production:

```sh
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app php artisan optimize
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app php artisan view:cache
```

PHP OPcache is tuned by:

```text
/usr/local/etc/php/conf.d/99-gestiio-performance.ini
```

Important: `opcache.validate_timestamps=0` means PHP code changes require an application container restart or explicit OPcache reset before they are visible.

Slow requests and slow database queries are logged in Laravel with:

- `slow_request`
- `slow_query`

Manual performance report:

```sh
/Volume1/homes/Carmine/gestiio-performance-report.sh
```

Use more log lines if needed:

```sh
LINES=20000 /Volume1/homes/Carmine/gestiio-performance-report.sh
```

## Verification After Changes

```sh
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app php -l /var/www/html/app/Models/Gestore.php
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app php artisan optimize:clear
curl -I --max-time 30 https://gestiio.agenziaplinio.it/login
curl -I --max-time 30 https://gestiio.agenziaplinio.it/backend
```

## Recovery Targets

- RTO target: restore public login within 15 minutes after NAS boot.
- RPO target: no application data loss beyond last database/storage backup.
- First response: check Cloudflare tunnel and critical containers.
- Escalation: reboot NAS only if DockerEngine is present but the API remains unresponsive after a controlled restart.
