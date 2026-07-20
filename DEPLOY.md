# Deploy Gestiio in Produzione

Guida operativa per deployare modifiche sul NAS di produzione. Vale sia per Claude Code che per Cursor o altri assistenti: la pipeline GitHub Actions (`.github/workflows/deploy.yml`) **non è collegata alla produzione reale** (manca dei secrets e punta a una directory sbagliata) — il deploy vero si fa via SSH diretto sul NAS, come descritto qui sotto.

## Riferimenti rapidi

| Cosa | Valore |
|---|---|
| Host NAS | `192.168.1.50` |
| Utente SSH | `Carmine` |
| Directory app live (host) | `/home/Carmine/apps/gestiio-20260624-2128` |
| Container applicativo | `gestiio-app` |
| Container DB | `gestiio-db` |
| Binario Docker sul NAS | `/Volume1/@apps/DockerEngine/dockerd/bin/docker` |
| Porta locale app | `8090` (`http://localhost:8090`) |
| URL pubblico | `https://gestiio.agenziaplinio.it` |
| Path dentro il container | `/var/www/html` |

Altri runbook infra correlati: `ops/gestiio-devops-runbook.md` (recovery rapido), `ops/gestiio-infra-runbook.md` (scope infrastruttura completo).

## Procedura standard di deploy

### 0. Prerequisiti

- Le modifiche devono essere già **committate e pushate** su `main` (`git push origin main`).
- Verificare `git status` pulito prima di iniziare.

### 1. Backup (sempre, prima di ogni deploy)

Snapshot via hardlink (istantaneo, non occupa spazio extra finché i file non cambiano):

```sh
ssh Carmine@192.168.1.50 "cp -al /home/Carmine/apps/gestiio-20260624-2128 /home/Carmine/apps/gestiio-backup-$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"
```

### 2. Copiare i file modificati sul NAS

`scp` spesso chiude la connessione su questo NAS (subsystem SFTP limitato). Il metodo affidabile è **pipe via `cat`** su SSH, file per file:

```sh
ssh Carmine@192.168.1.50 "cat > /tmp/NomeFile.php" < path/locale/NomeFile.php
```

Ripetere per ogni file toccato dal commit. Per le view Blade usare un nome temporaneo distintivo (es. `/tmp/agente-edit.blade.php`) per evitare collisioni tra file con lo stesso basename in cartelle diverse.

**Verifica sempre i fine-riga prima di procedere** (vedi sezione [Line endings](#line-endings-crlf-vs-lf) più sotto):

```sh
ssh Carmine@192.168.1.50 "file /tmp/NomeFile.php"
```

Deve dire `... text` (LF), **non** `... with CRLF line terminators`.

### 3. Copiare dalla directory host al container

```sh
ssh Carmine@192.168.1.50 '
set -e
APP=/home/Carmine/apps/gestiio-20260624-2128
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

cp /tmp/NomeFile.php "$APP/percorso/relativo/NomeFile.php"
"$DOCKER" cp "$APP/percorso/relativo/NomeFile.php" gestiio-app:/var/www/html/percorso/relativo/NomeFile.php

rm -f /tmp/NomeFile.php
echo DEPLOY_COPY_OK
'
```

Ripetere `cp`/`docker cp` per ogni file. Copiare **sia** nella directory host (`$APP/...`, è la copia "sorgente" per i prossimi deploy) **sia** dentro il container (altrimenti l'app in esecuzione non vede la modifica).

### 4. Migrazioni database (solo se il commit include nuove migration)

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan migrate --force"
'
```

Se la migration tocca permessi Spatie, aggiungere anche:

```sh
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan permission:cache-reset"
```

(Può restituire "Unable to flush cache" in modo innocuo se il cache store non supporta `flush()`: la migration stessa invalida già la cache dei permessi via `PermissionRegistrar::forgetCachedPermissions()`.)

### 5. Cache clear + rebuild

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan view:clear && php artisan view:cache && php artisan config:clear"
'
```

### 6. Restart del container — **OBBLIGATORIO, non facoltativo**

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
"$DOCKER" restart gestiio-app
'
```

Vedi sezione [OPcache](#opcache-perché-il-restart-è-sempre-necessario) sotto per il perché.

### 7. Verifica post-deploy

```sh
sleep 6
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
"$DOCKER" ps --filter name=gestiio-app --format "{{.Names}} {{.Status}}"
curl -s -o /dev/null -w "login_http_status=%{http_code}\n" http://localhost:8090/login
'
```

Ci si aspetta `Up ...` e `login_http_status=200`.

Per verifiche più approfondite (rendering di una view specifica, stato di un permesso, ecc.) si può usare `php artisan tinker --execute="..."` dentro il container — ma **occhio**: rendere una view via tinker fuori da una vera richiesta HTTP non ha `$errors` (ViewErrorBag) né `Auth::user()` popolati; un errore su questi due punti nel test è normale e NON indica un bug reale.

## Gotcha e lezioni imparate

### OPcache: perché il restart è sempre necessario

Il file `docker/php/conf.d/99-gestiio-performance.ini` imposta `opcache.validate_timestamps=0`. Questo significa che **anche se** `php artisan view:clear` + `view:cache` rigenerano correttamente il file compilato su disco, OPcache continua a servire il **vecchio bytecode in memoria** per quel path finché il processo PHP non viene riavviato. Risultato: la modifica sembra "non essere andata" anche se il file sul disco è corretto.

**Regola**: dopo ogni deploy che tocca file `.php` o `.blade.php`, eseguire sempre `docker restart gestiio-app` — `view:clear` + `view:cache` da soli **non bastano**.

### Line endings: CRLF vs LF

Il repo è sviluppato su Windows. Anche con `.gitattributes` che imposta `text=auto`, il working copy locale può materializzare i file con **CRLF**, mentre il container Linux si aspetta **LF**. Un file `.php`/`.blade.php` con CRLF di solito "funziona" comunque (PHP tollera `\r\n`), ma può rompere in modo sottile costrutti specifici (es. la sintassi inline `@php($espressione)` di Blade, che senza chiusura `?>` corretta può inghiottire il resto del file come PHP letterale, causando un 500 con errore tipo `syntax error, unexpected token "class"`).

Il repo ha `.gitattributes` con:

```
*.php text eol=lf
*.blade.php text eol=lf
```

che forza LF per questi file ad ogni checkout. Se un file locale risulta comunque in CRLF (verificabile con `file nomefile.php`), rigenerarlo con:

```sh
sed -i 's/\r$//' path/al/file.php
```

**Verifica sempre `file /tmp/NomeFile.php` sul NAS prima di copiarlo nel container** (step 2 sopra).

### Blade `@php ... @endphp` a blocco vs `@php(espressione)` inline

Attenzione mescolando le due forme nello stesso file: in questa versione di Blade, un `@php` "a blocco" (senza parentesi subito dopo) può essere accoppiato per errore con un `@endphp` **molto più avanti nel file**, anche se logicamente non c'entra — inghiottendo tutto il contenuto/HTML in mezzo come se fosse PHP letterale, con conseguente 500 su un token HTML qualsiasi (es. `class="..."`).

**Regola pratica**: se un file usa già solo la forma inline `@php($x = $y)` (senza `@endphp`), **non aggiungere blocchi `@php ... @endphp`** nello stesso file — spostare la logica multi-statement nel Controller e passare alla view solo variabili già pronte (array, bool, ecc.).

### Permessi Spatie

Dopo aver modificato permessi via migration o via `syncPermissions()`/`givePermissionTo()`, ricordarsi che:

- Le modifiche dirette sui permessi di un modello (`$user->givePermissionTo(...)`) invalidano automaticamente la cache interna di Spatie.
- Le migration che toccano la tabella `permissions` dovrebbero chiamare esplicitamente `app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();`.
- In caso di dubbio, eseguire comunque `php artisan permission:cache-reset` dopo il deploy (vedi step 4).

## Rollback

Ogni deploy crea uno snapshot in `/home/Carmine/apps/gestiio-backup-<timestamp>` (step 1). Per tornare indietro:

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
BACKUP=/home/Carmine/apps/gestiio-backup-<timestamp>   # scegliere lo snapshot giusto
APP=/home/Carmine/apps/gestiio-20260624-2128

rsync -a --delete --exclude=".env" "$BACKUP"/ "$APP"/
"$DOCKER" cp "$APP"/. gestiio-app:/var/www/html
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan view:clear && php artisan view:cache"
"$DOCKER" restart gestiio-app
'
```

Se il rollback deve annullare anche una migration, valutare caso per caso: molte migration di questo repo sono scritte come **irreversibili** (`down()` vuoto, commento "rollback manuale non supportato") — leggere la migration specifica prima di agire sul DB.

## Modulo Deposito Bagagli

Modulo custodia bagagli con prenotazioni online (API REST), backoffice operatori e pagina pubblica integrata.

### Variabili `.env` (obbligatorie in produzione)

```env
LUGGAGE_API_KEY=<chiave-segreta-lunga>
LUGGAGE_DEFAULT_RATE=5
LUGGAGE_MAX_CAPACITY=50
LUGGAGE_MAX_BAGS_PER_BOOKING=10
LUGGAGE_MIN_DAYS=1
LUGGAGE_CURRENCY=EUR
```

Generare la chiave: `php -r "echo bin2hex(random_bytes(32));"`

La stessa chiave va sul **sito esterno** che chiama le API (header `x-api-key`).

### Migration e seed (primo deploy del modulo)

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan migrate --force"
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan db:seed --class=LuggageSettingSeeder --force"
"$DOCKER" exec gestiio-app sh -lc "cd /var/www/html && php artisan config:clear"
'
```

### URL operativi

| Uso | URL |
|---|---|
| Backoffice | `https://gestiio.agenziaplinio.it/backend/deposito-bagagli/dashboard` |
| Prenotazione pubblica | `https://gestiio.agenziaplinio.it/deposito-bagagli` |
| API pubbliche | `https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/` |
| OpenAPI | `.../api/public/deposito-bagagli/docs` |

### Verifica post-deploy

```sh
curl -s -o /dev/null -w "booking_page=%{http_code}\n" https://gestiio.agenziaplinio.it/deposito-bagagli
curl -s -H "x-api-key: LA_CHIAVE" https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/pricing
```

### Test locali

```sh
php artisan test --filter=Luggage
```

## Modulo PDF Tools (Stirling PDF)

Stirling PDF gira in Docker (`stirling-pdf`) sulla rete Gestiio. Il browser lo usa via iframe/proxy same-origin; l’**app Windows** usa la porta LAN `8091` (non pubblicata su Cloudflare; `8090` è di gestiio-app).

### Avvio container (una tantum / dopo reboot)

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
mkdir -p /home/Carmine/apps/stirling-pdf/{configs,logs,tessdata,pipeline,customFiles}
# Copiare ops/docker-compose.stirling.yml sul NAS, poi:
cd /home/Carmine/apps/stirling-pdf
$DOCKER compose -f docker-compose.stirling.yml up -d
$DOCKER ps --filter name=stirling-pdf
'
```

Compose: [`ops/docker-compose.stirling.yml`](ops/docker-compose.stirling.yml) — login on, **`storage.enabled=false`** (niente My Files), porta host **`8091:8080`** (8090 è di gestiio-app), `SYSTEM_FRONTENDURL` / `SYSTEM_BACKENDURL` = URL pubblico Gestiio.

**Sessione condivisa (default):** tutti gli agenti usano lo stesso account Stirling admin (niente provisioning `gestiio-{id}`), così si resta nel piano free senza archivio per utente. I file **non** restano sul server: elabora e scarica. Banner informativo su `/backend/pdf-tools`.

Per tornare allo storage isolato per agente: `STIRLING_SHARED_SESSION=false`, `STIRLING_STORAGE_ENABLED=true` e `storage.enabled: true` in settings.yml.

**App Windows:** URL `http://192.168.1.50:8092` + credenziali admin Stirling da `GET /backend/pdf-tools/desktop-credentials` (sessione condivisa). Non usare l’URL Cloudflare pubblico dall’app desktop.

Credenziali in `.env` Gestiio + `/home/Carmine/apps/stirling-pdf/.env`:
```env
STIRLING_ADMIN_USER=admin
STIRLING_ADMIN_PASSWORD=…          # default install: stirling
STIRLING_SHARED_SESSION=true
STIRLING_STORAGE_ENABLED=false
STIRLING_URL=http://stirling-pdf:8080
STIRLING_PUBLIC_URL=https://gestiio.agenziaplinio.it/pdf-tools
STIRLING_DESKTOP_URL=http://192.168.1.50:8092
```

**OCR / tessdata:** volume host `/home/Carmine/apps/stirling-pdf/tessdata` → `/usr/share/tessdata` (almeno `eng`, `ita`, `osd`).

**Nota licenza:** Piano / Audit avanzato / Analytics Pro richiedono licenza Stirling Pro/Enterprise.

### Variabili `.env` (container gestiio-app)

```env
STIRLING_URL=http://stirling-pdf:8080
STIRLING_PUBLIC_URL=https://gestiio.agenziaplinio.it/pdf-tools
STIRLING_DESKTOP_URL=http://192.168.1.50:8092
STIRLING_TIMEOUT=300
STIRLING_ADMIN_USER=admin
STIRLING_ADMIN_PASSWORD=…
STIRLING_USER_SECRET=…
```

### URL

| Uso | URL |
|---|---|
| Shell iframe (admin/agente) | `https://gestiio.agenziaplinio.it/backend/pdf-tools` |
| Proxy same-origin | `https://gestiio.agenziaplinio.it/pdf-tools` |
| App Windows (LAN) | `http://192.168.1.50:8092` |
| Mobile scanner (QR telefono, senza login) | `https://gestiio.agenziaplinio.it/pdf-tools/mobile-scanner?session=…` |
| Carica da telefono (allegati dropzone) | bridge `POST /backend/allegati-mobile-scan/session` |

Il proxy riscrive sempre `Location` in `https://` sul dominio pubblico e segue internamente i redirect trailing-slash di Stirling (evita mixed-content nell’iframe).

### Verifica

```sh
# Interno (rete Docker)
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
$DOCKER exec gestiio-app php /tmp/smoke-pdf-tools-public.php
# LAN app Windows
curl -sS http://127.0.0.1:8091/pdf-tools/api/v1/info/status
'
```

Gap noti chiusi: URL pubblico QR, mobile-scanner senza login, no Location `http://`, whitelist asset pubblici ristretta, allegati dropzone con materialize senza invalidare la sessione a metà upload, SSO per-agente, porta LAN desktop.

## Modulo Documenti (Seafile)

`/backend/documenti` apre la UI Seafile (iframe + SSO). I file storici restano su disk `sensitive` finché non li cancelli a mano; l’import copia l’albero cartelle in Seafile **senza cancellare** i locali.

### Ruoli

| Ruolo Gestiio | Accesso Documenti |
|---|---|
| admin | Sì — account Seafile RW (owner library) |
| agente | Sì — sola lettura (account RO condiviso) |
| operatore / supervisore | No (403 / voce menu nascosta) |

### Avvio Seafile (NAS, una tantum)

```sh
ssh Carmine@192.168.1.50 '
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
mkdir -p /home/Carmine/apps/seafile/{data,mysql}
# Copiare ops/docker-compose.seafile.yml e creare .env con password
cd /home/Carmine/apps/seafile
cat > .env <<EOF
SEAFILE_MYSQL_ROOT_PASSWORD=...
SEAFILE_ADMIN_EMAIL=admin@gestiio.local
SEAFILE_ADMIN_PASSWORD=...
SEAFILE_SERVER_HOSTNAME=documenti.agenziaplinio.it
EOF
$DOCKER compose -f docker-compose.seafile.yml up -d
'
```

Poi bootstrap (locale IT + library + utente agente RO):

```sh
# Impostare SEAFILE_ADMIN_PASSWORD / SEAFILE_AGENTE_PASSWORD e lanciare:
sh ops/seafile-bootstrap.sh
# Annotare SEAFILE_REPO_ID stampato dallo script
```

### DNS / TLS

Hostname pubblico via Cloudflare Tunnel (`cloudflared_corehost`):

| Hostname | Origin NAS |
|---|---|
| `documenti.agenziaplinio.it` | `http://127.0.0.1:8089` (Seafile) |

Config: `/opt/cloudflared/config.yml` — ingress `documenti.agenziaplinio.it` → `localhost:8089`.  
CNAME tunnel: `cloudflared tunnel route dns --overwrite-dns corehost documenti.agenziaplinio.it` (come root nel container).

Se il browser LAN vede ancora NXDOMAIN (DNS del router `192.168.1.254` con cache negativa), attendi 5–15 minuti oppure usa DNS `1.1.1.1` / svuota cache router. Verifica: `nslookup documenti.agenziaplinio.it 1.1.1.1`.

### Variabili `.env` gestiio-app

```env
SEAFILE_URL=http://seafile
SEAFILE_PUBLIC_URL=https://documenti.agenziaplinio.it
SEAFILE_ADMIN_EMAIL=admin@gestiio.local
SEAFILE_ADMIN_PASSWORD=...
SEAFILE_AGENTE_EMAIL=agente-ro@gestiio.local
SEAFILE_AGENTE_PASSWORD=...
SEAFILE_REPO_ID=...
SEAFILE_TIMEOUT=300
```

### Import file (preserva cartelle)

```sh
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile --dry-run
$DOCKER exec gestiio-app php /var/www/html/artisan documenti:import-seafile
```

- Ricostruisce l’albero NestedSet (`files_cartelle`) come directory Seafile
- Carica ogni file con `filename_originale`
- Scrive `files.seafile_path` / `seafile_imported_at`
- **Non elimina** i blob in `storage/app/sensitive/file_manager`

### URL

| Uso | URL |
|---|---|
| Shell Gestiio | `https://gestiio.agenziaplinio.it/backend/documenti` |
| SSO / iframe | `https://gestiio.agenziaplinio.it/backend/documenti/sso` |
| UI Seafile | `https://documenti.agenziaplinio.it` |
| Share legacy Gestiio | `https://gestiio.agenziaplinio.it/documenti/condivisi/{token}` (disk locale) |

### Verifica

```sh
$DOCKER exec -e SEAFILE_URL=http://seafile -e SEAFILE_ADMIN_PASSWORD=... -e SEAFILE_AGENTE_PASSWORD=... -e SEAFILE_REPO_ID=... \
  gestiio-app php /tmp/smoke-seafile-documenti.php
```

## Checklist rapida (copia-incolla mentale)

1. `git push origin main` ✅ fatto?
2. Backup hardlink sul NAS
3. `ssh ... "cat > /tmp/x" < file_locale` per ogni file (verificare LF con `file`)
4. `cp` in `$APP/...` + `docker cp` nel container per ogni file
5. Migration se presenti (`migrate --force`, eventualmente `permission:cache-reset`)
6. `view:clear && view:cache && config:clear`
7. `docker restart gestiio-app` — **sempre**
8. `docker ps` + `curl .../login` → `200`

## Redis + Chat realtime (SSE)

La chat interna (`/backend/chat-interna`) usa **SSE** + polling delta per il
realtime (Laravel 9 non ha Reverb: **non** installare `laravel/reverb`). Redis è
consigliato per cache/coda/presenza online, ma non è obbligatorio per l'SSE.

### Servizio Redis

`docker-compose.nas.yml` include ora un servizio `redis` (`redis:7-alpine`,
volume `gestiio_redis`, healthcheck `redis-cli ping`) e imposta sull'app:

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

In alternativa, per un Redis standalone (solo su `127.0.0.1:6379`):

```sh
docker compose -f ops/docker-compose.redis.yml up -d
```

### Note operative SSE

- Endpoint stream: `GET /backend/chat-interna/{thread}/stream` — tiene aperta la
  connessione fino a ~20s controllando ogni 500ms i nuovi messaggi, poi chiude
  (il client riapre). Invia header `X-Accel-Buffering: no` per evitare il
  buffering del reverse proxy.
- Se davanti c'è Nginx/Cloudflare, assicurarsi che non facciano buffering/gzip
  sulla rotta `*/stream` (già mitigato via header, ma verificare la config proxy).
- Il worker della coda va comunque tenuto attivo per le web push:
  `php artisan queue:work --tries=3`.

### Allegati chat (disk local)

I nuovi allegati sono salvati su disk `local` (`storage/app/chat-allegati/...`),
serviti solo tramite rotta autenticata. Per spostare eventuali vecchi allegati da
`public` a `local`:

```sh
$DOCKER exec gestiio-app php /var/www/html/artisan chat:migrate-attachments --dry-run
$DOCKER exec gestiio-app php /var/www/html/artisan chat:migrate-attachments
```

### Broadcasting

`App\Providers\BroadcastServiceProvider` è ora abilitato in `config/app.php`.
Gli eventi `ChatMessageSent` e `ChatTypingUpdated` (`ShouldBroadcastNow`) sono
predisposti per un futuro client Echo; l'UI attuale usa SSE e non dipende da un
server WebSocket.
