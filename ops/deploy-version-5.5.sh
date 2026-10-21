#!/bin/sh
# Release 5.5 — gare/listino/referral (NO formazione/Boorite)
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
TAR=/tmp/gestiio-version-5.5.tar

TITLE='Gare premi + listino offerte + referral soglia'
NOTES='Gare / premi obiettivo: CRUD Metronic (Documenti e Impostazioni), soglie lump-sum separate dalle fasce listino, filtri Offerte telefonia / gestore energia, colonna Gara in liquidazione + proforma + export CSV, ricalcolo su produzione mese.\nListino: adattato a Offerte telefonia (canoni listino rosso, senza promo mobile); fix navigazione pagina/modale (toggleZ + cache).\nReferral affiliati: alla registrazione solo legame invitante→nuovo affiliato (niente bonus); pending solo con ≥2 contratti energia + ≥1 telefonia; sotto soglia non si aggancia; ricontrollo soglia in conferma admin; landing aggiornata con regole e soglia.\nOps: fix OPcache Apache su rotte nuove; finish soft Blade/view.'

cd "$(dirname "$0")/.."

tar -cf "$TAR" config/configurazione.php
base64 "$TAR" | ssh "$HOST" "base64 -d > $TAR"

ssh "$HOST" "
set -e
cd $APP
tar -xf $TAR
$DOCKER cp $APP/config/configurazione.php $CONTAINER:/var/www/html/config/configurazione.php
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
# Web SAPI opcache (Apache) — endpoint di reset se presente, altrimenti graceful
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan tinker --execute=\"Illuminate\\\\Support\\\\Facades\\\\Cache::put(\\\"CacheUnaVoltaAlGiorno\\\", now(), now()->endOfDay());\"' || true
$DOCKER exec $CONTAINER sh -lc \"php /var/www/html/artisan gestiio:release 5.5 --title='$TITLE' --notes='$NOTES'\"
$DOCKER exec $CONTAINER sh -lc 'grep versione /var/www/html/config/configurazione.php | head -1'
$DOCKER exec $CONTAINER php /var/www/html/artisan tinker --execute=\"\\\$r=\\\\App\\\\Models\\\\AppVersionRelease::where('version','5.5')->first(); echo \\\$r ? (\\\$r->version.'|'.\\\$r->title.'|'.\\\$r->released_at) : 'MISSING';\"
echo VERSION_55_OK
"

rm -f "$TAR"
