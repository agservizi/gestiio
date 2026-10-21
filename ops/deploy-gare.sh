#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
ARCHIVE=/tmp/gestiio-gare.tar

cd "$(dirname "$0")/.."

tar -cf "$ARCHIVE" \
  database/migrations/2026_10_21_072000_create_gare_tables.php \
  app/Models/Gara.php \
  app/Models/GaraSoglia.php \
  app/Models/GaraRisultato.php \
  app/Http/Services/GaraEvalService.php \
  app/Http/Services/LiquidazioneProvvigioniService.php \
  app/Http/Services/FatturaProformaService.php \
  app/Exports/LiquidazioneProvvigioniExport.php \
  app/Models/ProduzioneOperatore.php \
  app/Http/Controllers/Backend/GaraController.php \
  app/Http/Controllers/Backend/ListinoController.php \
  app/Http/Controllers/Backend/FasciaTipoContrattoController.php \
  resources/views/Backend/Gara \
  resources/views/Backend/Listino/show.blade.php \
  resources/views/Backend/LiquidazioneProvvigioni/index.blade.php \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  routes/web-backend.php

base64 "$ARCHIVE" | ssh "$HOST" "base64 -d > $ARCHIVE"

ssh "$HOST" "
set -e
cd $APP
tar -xf $ARCHIVE
$DOCKER cp $APP/database/migrations/2026_10_21_072000_create_gare_tables.php $CONTAINER:/var/www/html/database/migrations/2026_10_21_072000_create_gare_tables.php
$DOCKER cp $APP/app/Models/Gara.php $CONTAINER:/var/www/html/app/Models/Gara.php
$DOCKER cp $APP/app/Models/GaraSoglia.php $CONTAINER:/var/www/html/app/Models/GaraSoglia.php
$DOCKER cp $APP/app/Models/GaraRisultato.php $CONTAINER:/var/www/html/app/Models/GaraRisultato.php
$DOCKER cp $APP/app/Http/Services/GaraEvalService.php $CONTAINER:/var/www/html/app/Http/Services/GaraEvalService.php
$DOCKER cp $APP/app/Http/Services/LiquidazioneProvvigioniService.php $CONTAINER:/var/www/html/app/Http/Services/LiquidazioneProvvigioniService.php
$DOCKER cp $APP/app/Http/Services/FatturaProformaService.php $CONTAINER:/var/www/html/app/Http/Services/FatturaProformaService.php
$DOCKER cp $APP/app/Exports/LiquidazioneProvvigioniExport.php $CONTAINER:/var/www/html/app/Exports/LiquidazioneProvvigioniExport.php
$DOCKER cp $APP/app/Models/ProduzioneOperatore.php $CONTAINER:/var/www/html/app/Models/ProduzioneOperatore.php
$DOCKER cp $APP/app/Http/Controllers/Backend/GaraController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/GaraController.php
$DOCKER cp $APP/app/Http/Controllers/Backend/ListinoController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/ListinoController.php
$DOCKER cp $APP/app/Http/Controllers/Backend/FasciaTipoContrattoController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/FasciaTipoContrattoController.php
$DOCKER cp $APP/resources/views/Backend/Gara $CONTAINER:/var/www/html/resources/views/Backend/Gara
$DOCKER cp $APP/resources/views/Backend/Listino/show.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Listino/show.blade.php
$DOCKER cp $APP/resources/views/Backend/LiquidazioneProvvigioni/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/LiquidazioneProvvigioni/index.blade.php
$DOCKER cp $APP/resources/views/Backend/_layout/app-sidebar-menu.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
$DOCKER cp $APP/routes/web-backend.php $CONTAINER:/var/www/html/routes/web-backend.php

$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan migrate --force --path=database/migrations/2026_10_21_072000_create_gare_tables.php'
$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan config:cache; php /var/www/html/artisan route:cache; php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan tinker --execute=\"Illuminate\\\\Support\\\\Facades\\\\Cache::put(\\\"CacheUnaVoltaAlGiorno\\\", now(), now()->endOfDay());\"' || true

$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan tinker --execute=\"
\\\$anno=(int)now()->year; \\\$mese=(int)now()->month;
if (!App\\\\Models\\\\Gara::where(\\\"nome\\\",\\\"Smoke Fastweb Tel\\\")->exists()) {
  \\\$g=App\\\\Models\\\\Gara::create([\\\"nome\\\"=>\\\"Smoke Fastweb Tel\\\",\\\"canale\\\"=>\\\"telefonia\\\",\\\"anno\\\"=>\\\$anno,\\\"mese\\\"=>\\\$mese,\\\"attivo\\\"=>1,\\\"ambito\\\"=>\\\"tutti\\\"]);
  App\\\\Models\\\\GaraSoglia::create([\\\"gara_id\\\"=>\\\$g->id,\\\"da_valore\\\"=>1,\\\"a_valore\\\"=>null,\\\"premio\\\"=>10]);
}
\\\$r=app(App\\\\Http\\\\Services\\\\GaraEvalService::class)->ricalcolaMese(\\\$anno,\\\$mese);
echo \\\"eval updated=\\\".\\\$r[\\\"updated\\\"].\\\" skipped=\\\".\\\$r[\\\"skipped\\\"].PHP_EOL;
echo \\\"gare=\\\".App\\\\Models\\\\Gara::count().\\\" risultati=\\\".App\\\\Models\\\\GaraRisultato::count().PHP_EOL;
\"'

echo GARE_DEPLOY_OK
"

rm -f "$ARCHIVE"
