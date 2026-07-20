#!/bin/sh
# Deploy Produzione Operatore + Fattura Proforma su NAS Gestiio
# Uso: sh ops/deploy-proforma-module.sh
set -e

HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

cd "$ROOT"

echo "==> Backup hardlink"
ssh "$HOST" "cp -al $APP /home/Carmine/apps/gestiio-backup-\$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"

echo "==> Archivio file modulo Proforma"
tar -cf /tmp/proforma-deploy.tar \
  app/Enums/FatturaProformaStatus.php \
  app/Policies/FatturaProformaPolicy.php \
  app/Policies/ProduzioneOperatorePolicy.php \
  app/Models/FatturaProforma.php \
  app/Models/ProduzioneOperatore.php \
  app/Http/Services/FatturaProformaService.php \
  app/Http/Controllers/Backend/FatturaProformaController.php \
  app/Http/Controllers/Backend/ProduzioneOperatoreController.php \
  app/Http/Requests/UpdateFatturaProformaIntestazioneRequest.php \
  app/Notifications/NotificaFatturaProforma.php \
  app/Providers/AuthServiceProvider.php \
  routes/web-backend.php \
  database/migrations/2026_07_17_210000_add_status_to_fatture_proforma_table.php \
  resources/views/Backend/FatturaProforma/index.blade.php \
  resources/views/Backend/FatturaProforma/tabella.blade.php \
  resources/views/Backend/FatturaProforma/show.blade.php \
  resources/views/Backend/FatturaProforma/card.blade.php \
  resources/views/Backend/ProduzioneOperatore/index.blade.php \
  resources/views/Backend/ProduzioneOperatore/tabella.blade.php \
  resources/views/Backend/ProduzioneOperatore/show.blade.php \
  resources/views/Backend/ProduzioneOperatore/partials/preview-modal.blade.php \
  tests/Unit/FatturaProformaServiceTest.php \
  tests/Unit/FatturaProformaStatusTest.php

echo "==> Upload archivio (base64 via SSH)"
base64 /tmp/proforma-deploy.tar | ssh "$HOST" "base64 -d > /tmp/proforma-deploy.tar"

echo "==> Estrazione su host + sync container"
ssh "$HOST" "
set -e
cd $APP
tar -xf /tmp/proforma-deploy.tar
find app routes database resources tests -type f \( -name '*.php' -o -name '*.blade.php' \) -print0 2>/dev/null | xargs -0 sed -i 's/\r\$//' || true

$DOCKER exec $CONTAINER mkdir -p \
  /var/www/html/app/Enums \
  /var/www/html/app/Policies \
  /var/www/html/app/Notifications \
  /var/www/html/app/Http/Requests \
  /var/www/html/app/Http/Services \
  /var/www/html/app/Http/Controllers/Backend \
  /var/www/html/app/Models \
  /var/www/html/app/Providers \
  /var/www/html/routes \
  /var/www/html/database/migrations \
  /var/www/html/resources/views/Backend/FatturaProforma \
  /var/www/html/resources/views/Backend/ProduzioneOperatore/partials \
  /var/www/html/tests/Unit

# Sync file by file into container from host app tree
for f in \
  app/Enums/FatturaProformaStatus.php \
  app/Policies/FatturaProformaPolicy.php \
  app/Policies/ProduzioneOperatorePolicy.php \
  app/Models/FatturaProforma.php \
  app/Models/ProduzioneOperatore.php \
  app/Http/Services/FatturaProformaService.php \
  app/Http/Controllers/Backend/FatturaProformaController.php \
  app/Http/Controllers/Backend/ProduzioneOperatoreController.php \
  app/Http/Requests/UpdateFatturaProformaIntestazioneRequest.php \
  app/Notifications/NotificaFatturaProforma.php \
  app/Providers/AuthServiceProvider.php \
  routes/web-backend.php \
  database/migrations/2026_07_17_210000_add_status_to_fatture_proforma_table.php \
  resources/views/Backend/FatturaProforma/index.blade.php \
  resources/views/Backend/FatturaProforma/tabella.blade.php \
  resources/views/Backend/FatturaProforma/show.blade.php \
  resources/views/Backend/FatturaProforma/card.blade.php \
  resources/views/Backend/ProduzioneOperatore/index.blade.php \
  resources/views/Backend/ProduzioneOperatore/tabella.blade.php \
  resources/views/Backend/ProduzioneOperatore/show.blade.php \
  resources/views/Backend/ProduzioneOperatore/partials/preview-modal.blade.php \
  tests/Unit/FatturaProformaServiceTest.php \
  tests/Unit/FatturaProformaStatusTest.php
do
  $DOCKER cp \"$APP/\$f\" \"$CONTAINER:/var/www/html/\$f\"
done

rm -f /tmp/proforma-deploy.tar
echo DEPLOY_COPY_OK
"

echo "==> Migrate + cache clear"
ssh "$HOST" "
set -e
DOCKER=$DOCKER
\$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && php artisan migrate --force'
\$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && php artisan optimize:clear && php artisan view:cache'
echo MIGRATE_OK
"

echo "==> Restart container (OPcache)"
ssh "$HOST" "
DOCKER=$DOCKER
\$DOCKER restart $CONTAINER
"

echo "==> Wait + health check"
sleep 8
ssh "$HOST" "
DOCKER=$DOCKER
\$DOCKER ps --filter name=$CONTAINER --format '{{.Names}} {{.Status}}'
curl -s -o /dev/null -w 'login_http_status=%{http_code}\n' http://localhost:8090/login
\$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && php artisan route:list --path=fattura-proforma 2>/dev/null | head -20'
\$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && php artisan route:list --path=produzione-operatore 2>/dev/null | head -20'
\$DOCKER exec $CONTAINER sh -lc \"cd /var/www/html && php -r \\\"echo Schema::hasColumn('fatture_proforma','status') ? 'status_column=OK' : 'status_column=MISSING';\\\"\" 2>/dev/null || \
  \$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && php artisan tinker --execute=\"echo Schema::hasColumn(\\\"fatture_proforma\\\", \\\"status\\\") ? \\\"status_column=OK\\\\n\\\" : \\\"status_column=MISSING\\\\n\\\";\"'
"

rm -f /tmp/proforma-deploy.tar
echo "==> DEPLOY PROFORMA COMPLETO"
