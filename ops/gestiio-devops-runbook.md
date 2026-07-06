# Gestiio NAS DevOps Runbook

## Servizi Critici

- `gestiio-db`: database applicativo.
- `gestiio-app`: applicazione Laravel.
- `corehost_traefik`: reverse proxy locale.
- `cloudflared_corehost`: Cloudflare Tunnel.

## Recovery Rapido

```sh
ssh Carmine@192.168.1.50
/bin/sh /Volume1/homes/Carmine/start-all-docker-containers.sh
/Volume1/homes/Carmine/gestiio-healthcheck.sh
tail -20 /home/Carmine/gestiio-healthcheck.log
```

## Watchdog

Cron installati:

```cron
@reboot /bin/sh /Volume1/homes/Carmine/start-all-docker-containers.sh >> /home/Carmine/start-all-docker-containers.log 2>&1
*/2 * * * * /bin/sh /Volume1/homes/Carmine/gestiio-watchdog.sh >> /home/Carmine/gestiio-watchdog.log 2>&1
```

Log:

- `/home/Carmine/gestiio-watchdog.log`
- `/home/Carmine/start-all-docker-containers.log`
- `/home/Carmine/gestiio-healthcheck.log`
- `docker logs cloudflared_corehost`

## Backup

```sh
/bin/sh /Volume1/homes/Carmine/gestiio-backup.sh
ls -lh /Volume1/homes/Carmine/gestiio-backups
```

I backup includono dump DB, `storage/app` e `.env` redatto. Retention: 14 giorni per archivi compressi.

## Deploy E Rollback

```sh
/bin/sh /Volume1/homes/Carmine/gestiio-deploy.sh
tail -80 /home/Carmine/gestiio-deploy.log
```

Se healthcheck fallisce, lo script ripristina la release precedente in automatico.

## Alert

Gli alert email usano Resend leggendo `RESEND_KEY`, `MAIL_FROM_ADDRESS` e `MAIL_FROM_NAME` dal `.env` Laravel in `/Volume1/homes/Carmine/gestiio-latest-deploy/.env`.
Il template email include badge severita, timestamp, host, servizio interessato, messaggio e link rapido a Gestiio. Viene inviato anche un fallback testo semplice.
Il terzo parametro dello script imposta il link del bottone:

```sh
/Volume1/homes/Carmine/gestiio-alert.sh "Errore tunnel" CRITICAL "https://gestiio.agenziaplinio.it/backend"
```

Se il link non viene passato, `CRITICAL` usa `/backend` e gli altri livelli usano `/login`.

Configurazione opzionale per cambiare destinatario o aggiungere webhook/Telegram:

```sh
cat >/Volume1/homes/Carmine/gestiio-alert.env <<'EOF'
ALERT_EMAIL_TO=
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
ALERT_WEBHOOK_URL=
EOF
chmod 600 /Volume1/homes/Carmine/gestiio-alert.env
```

Test:

```sh
/Volume1/homes/Carmine/gestiio-alert.sh "Test alert Gestiio" INFO
tail -20 /home/Carmine/gestiio-alert.log
```

## Verifica Pubblica

```sh
curl -I https://gestiio.agenziaplinio.it/backend
```

Risultato atteso: `302` verso `/login` oppure `200` su `/login`.
