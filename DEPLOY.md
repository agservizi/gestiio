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

## Checklist rapida (copia-incolla mentale)

1. `git push origin main` ✅ fatto?
2. Backup hardlink sul NAS
3. `ssh ... "cat > /tmp/x" < file_locale` per ogni file (verificare LF con `file`)
4. `cp` in `$APP/...` + `docker cp` nel container per ogni file
5. Migration se presenti (`migrate --force`, eventualmente `permission:cache-reset`)
6. `view:clear && view:cache && config:clear`
7. `docker restart gestiio-app` — **sempre**
8. `docker ps` + `curl .../login` → `200`
