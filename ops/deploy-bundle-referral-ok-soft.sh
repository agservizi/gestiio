#!/bin/sh
# Bundle soft: referral soglia OK + UI + silent-failure report + tests already local
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
TAR=/tmp/gestiio-referral-ok.tar
RESET=/tmp/gestiio-opcache-reset.php

cd "$(dirname "$0")/.."

cat > "$RESET" <<'PHP'
<?php
if (!isset($_GET['k']) || $_GET['k'] !== 'gestiio-opcache-20261021') {
    http_response_code(404);
    exit;
}
echo function_exists('opcache_reset') && opcache_reset() ? 'opcache_reset_ok' : 'opcache_unavailable';
PHP

tar -cf "$TAR" \
  app/Http/Services/AffiliateReferralService.php \
  app/Models/ProduzioneOperatore.php \
  resources/views/Backend/ReferralAffiliato/index.blade.php \
  resources/views/Backend/AdminReferral/index.blade.php \
  .cursor/rules/gestiio-ecc-workflow.mdc \
  .gitignore

base64 "$TAR" | ssh "$HOST" "base64 -d > $TAR"
base64 "$RESET" | ssh "$HOST" "base64 -d > /tmp/gestiio-opcache-reset.php"

ssh "$HOST" "
set -e
cd $APP
tar -xf $TAR
$DOCKER cp $APP/app/Http/Services/AffiliateReferralService.php $CONTAINER:/var/www/html/app/Http/Services/AffiliateReferralService.php
$DOCKER cp $APP/app/Models/ProduzioneOperatore.php $CONTAINER:/var/www/html/app/Models/ProduzioneOperatore.php
$DOCKER cp $APP/resources/views/Backend/ReferralAffiliato/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/ReferralAffiliato/index.blade.php
$DOCKER cp $APP/resources/views/Backend/AdminReferral/index.blade.php $CONTAINER:/var/www/html/resources/views/Backend/AdminReferral/index.blade.php
$DOCKER cp /tmp/gestiio-opcache-reset.php $CONTAINER:/var/www/html/public/opcache-reset.php

# finish soft
$DOCKER exec $CONTAINER sh -lc 'rm -rf /var/www/html/storage/framework/views/*; php /var/www/html/artisan view:clear'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan view:cache' || true
$DOCKER exec $CONTAINER sh -lc 'php -r \"if (function_exists(\\\"opcache_reset\\\")) { opcache_reset(); }\"' || true
$DOCKER exec $CONTAINER sh -lc 'curl -sk -H \"Host: gestiio.agenziaplinio.it\" \"http://127.0.0.1/opcache-reset.php?k=gestiio-opcache-20261021\"'
echo
$DOCKER exec $CONTAINER rm -f /var/www/html/public/opcache-reset.php
rm -f /tmp/gestiio-opcache-reset.php

echo VERIFY
$DOCKER exec $CONTAINER grep -n \"esito_finale\", 'ok' /var/www/html/app/Http/Services/AffiliateReferralService.php | head -5
$DOCKER exec $CONTAINER php /var/www/html/artisan tinker --execute=\"echo 'v='.config('configurazione.versione').PHP_EOL; echo 'gare='.(Illuminate\\\\Support\\\\Facades\\\\Schema::hasTable('gare')?'yes':'no').PHP_EOL; echo 'ref='.(Illuminate\\\\Support\\\\Facades\\\\Schema::hasTable('affiliate_referral_bonuses')?'yes':'no').PHP_EOL; \\\$r=\\\\App\\\\Models\\\\AppVersionRelease::where('version','5.5')->first(); echo \\\$r?'rel55=ok':'rel55=missing';\"
echo REFERRAL_OK_BUNDLE_OK
"

rm -f "$TAR" "$RESET"
