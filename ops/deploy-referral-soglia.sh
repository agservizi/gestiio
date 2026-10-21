#!/bin/sh
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
ARCHIVE=/tmp/gestiio-referral-soglia.tar

cd "$(dirname "$0")/.."

tar -cf "$ARCHIVE" \
  app/Http/Services/AffiliateReferralService.php \
  app/Http/Controllers/Backend/AdminReferralController.php \
  app/Models/ContrattoTelefonia.php \
  app/Models/ContrattoEnergia.php \
  resources/views/Backend/ReferralAffiliato/index.blade.php \
  resources/views/Backend/AdminReferral/index.blade.php

base64 "$ARCHIVE" | ssh "$HOST" "base64 -d > $ARCHIVE"

ssh "$HOST" "
set -e
cd $APP
tar -xf $ARCHIVE
$DOCKER cp $APP/app/Http/Services/AffiliateReferralService.php $CONTAINER:/var/www/html/app/Http/Services/AffiliateReferralService.php
$DOCKER cp $APP/app/Http/Controllers/Backend/AdminReferralController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/AdminReferralController.php
$DOCKER cp $APP/app/Models/ContrattoTelefonia.php $CONTAINER:/var/www/html/app/Models/ContrattoTelefonia.php
$DOCKER cp $APP/app/Models/ContrattoEnergia.php $CONTAINER:/var/www/html/app/Models/ContrattoEnergia.php
$DOCKER cp $APP/resources/views/Backend/ReferralAffiliato/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/ReferralAffiliato/index.blade.php
$DOCKER cp $APP/resources/views/Backend/AdminReferral/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/AdminReferral/index.blade.php

$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true

$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan tinker --execute=\"
\\\$svc=app(App\\\\Http\\\\Services\\\\AffiliateReferralService::class);
\\\$n=0;
foreach(App\\\\Models\\\\User::query()->whereNotNull(\\\"referred_by_user_id\\\")->get([\\\"id\\\"]) as \\\$u){
  \\\$svc->evaluateReferralBonusForUser(\\\$u);
  \\\$n++;
}
echo \\\"evaluated_users=\\\".\\\$n.PHP_EOL;
echo \\\"pending=\\\".App\\\\Models\\\\AffiliateReferralBonus::where(\\\"stato\\\",\\\"pending\\\")->count().PHP_EOL;
echo \\\"MIN_TEL=\\\".App\\\\Http\\\\Services\\\\AffiliateReferralService::MIN_TELEFONIA.\\\" MIN_EN=\\\".App\\\\Http\\\\Services\\\\AffiliateReferralService::MIN_ENERGIA.PHP_EOL;
\"'

echo REFERRAL_SOGLIA_OK
"

rm -f "$ARCHIVE"
