#!/bin/sh
# Rimuove Outreach Poste Private da NAS Gestiio (codice + DB + crawler)
# Uso: sh ops/deploy-remove-outreach.sh
set -e

HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

echo "==> Backup hardlink"
ssh "$HOST" "cp -al $APP /home/Carmine/apps/gestiio-backup-\$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"

echo "==> Archivio file aggiornati (wiring + drop migration)"
tar -cf /tmp/outreach-remove.tar \
  ANALISI_MODULO_SEND.md \
  app/Providers/AuthServiceProvider.php \
  config/services.php \
  database/migrations/2026_07_18_200000_drop_marketing_outreach_module.php \
  docker-compose.nas.yml \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  routes/web-backend.php \
  routes/web.php

echo "==> Upload archivio (base64 via SSH)"
base64 /tmp/outreach-remove.tar | ssh "$HOST" "base64 -d > /tmp/outreach-remove.tar"

echo "==> Estrazione su host + container + rimozione file modulo"
ssh "$HOST" "
set -e
cd $APP
tar -xf /tmp/outreach-remove.tar
find ANALISI_MODULO_SEND.md app config database docker-compose.nas.yml resources routes -type f \( -name '*.php' -o -name '*.blade.php' -o -name '*.md' -o -name '*.yml' \) -print0 2>/dev/null | xargs -0 sed -i 's/\r$//' || true

$DOCKER cp /tmp/outreach-remove.tar $CONTAINER:/tmp/outreach-remove.tar
$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && tar -xf /tmp/outreach-remove.tar && rm -f /tmp/outreach-remove.tar'
$DOCKER exec $CONTAINER sh -lc \"find /var/www/html/app /var/www/html/config /var/www/html/database /var/www/html/resources /var/www/html/routes -type f \\( -name '*.php' -o -name '*.blade.php' \\) -print0 2>/dev/null | xargs -0 sed -i 's/\\r\$//' || true\"

# Rimuovi sorgenti/ops del modulo su host e container
RM_PATHS='
app/Models/MarketingOutreachCampaign.php
app/Models/MarketingOutreachMessage.php
app/Models/MarketingLead.php
app/Models/MarketingSuppression.php
app/Enums/MarketingOutreachCampaignStatus.php
app/Enums/MarketingOutreachMessageStatus.php
app/Enums/MarketingLeadStatus.php
app/Http/Controllers/Backend/MarketingOutreachController.php
app/Http/Controllers/Frontend/MarketingOutreachUnsubscribeController.php
app/Http/Controllers/Webhook/N8nMarketingWebhookController.php
app/Http/Services/MarketingOutreachService.php
app/Http/Services/OutreachDraftGenerator.php
app/Jobs/GenerateOutreachDraftJob.php
app/Notifications/NotificaOutreachPostePrivate.php
app/Policies/MarketingOutreachPolicy.php
config/marketing_outreach.php
tests/Feature/MarketingOutreachTest.php
resources/views/Mail/outreach-poste-private.blade.php
database/migrations/2026_07_17_220000_create_marketing_outreach_tables.php
database/migrations/2026_07_17_220001_seed_servizio_outreach_marketing_permission.php
ops/deploy-outreach-crawler.sh
ops/deploy-outreach-module.sh
ops/smoke-outreach-crawler.php
ops/smoke-marketing-outreach.php
'

for p in \$RM_PATHS; do
  [ -z \"\$p\" ] && continue
  rm -f \"\$APP/\$p\"
  $DOCKER exec $CONTAINER rm -f \"/var/www/html/\$p\" 2>/dev/null || true
done

rm -rf \"\$APP/resources/views/Backend/MarketingOutreach\" \
       \"\$APP/resources/views/Frontend/MarketingOutreach\" \
       \"\$APP/ops/outreach-crawler\"
$DOCKER exec $CONTAINER rm -rf \
  /var/www/html/resources/views/Backend/MarketingOutreach \
  /var/www/html/resources/views/Frontend/MarketingOutreach \
  /var/www/html/ops/outreach-crawler \
  /var/www/html/config/marketing_outreach.php

rm -f /tmp/outreach-remove.tar
echo EXTRACT_AND_RM_OK
"

echo "==> Migration drop + cache + restart"
ssh "$HOST" "
set -e
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan migrate --force --path=database/migrations/2026_07_18_200000_drop_marketing_outreach_module.php'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan permission:cache-reset || true'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan view:clear && php /var/www/html/artisan config:clear && php /var/www/html/artisan route:clear && php /var/www/html/artisan cache:clear'
$DOCKER restart $CONTAINER
echo RESTART_OK
"

echo "==> Stop outreach-crawler se presente"
ssh "$HOST" "
$DOCKER ps -a --format '{{.Names}}' | grep -qx outreach-crawler && {
  $DOCKER stop outreach-crawler || true
  $DOCKER rm outreach-crawler || true
  echo CRAWLER_REMOVED
} || echo CRAWLER_ABSENT
"

echo "==> Verifica"
ssh "$HOST" "
set -e
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan route:list --path=outreach' || true
$DOCKER exec $CONTAINER sh -lc \"php -r \\\"require '/var/www/html/vendor/autoload.php'; \\\$app=require '/var/www/html/bootstrap/app.php'; \\\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap(); echo 'tables=' . implode(',', array_filter([Schema::hasTable('marketing_leads')?'leads':null, Schema::hasTable('marketing_outreach_messages')?'messages':null, Schema::hasTable('marketing_outreach_campaigns')?'campaigns':null, Schema::hasTable('marketing_suppressions')?'suppressions':null])) . PHP_EOL; echo 'perm=' . (DB::table('permissions')->where('name','servizio_outreach_marketing')->exists()?'YES':'NO') . PHP_EOL;\\\"\"
$DOCKER exec $CONTAINER sh -lc 'test ! -f /var/www/html/config/marketing_outreach.php && echo CONFIG_GONE'
$DOCKER exec $CONTAINER sh -lc 'test ! -d /var/www/html/resources/views/Backend/MarketingOutreach && echo VIEWS_GONE'
$DOCKER exec $CONTAINER sh -lc \"grep -c 'Outreach Poste Private' /var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php || echo SIDEBAR_CLEAN\"
"

rm -f /tmp/outreach-remove.tar
echo "==> DONE"
