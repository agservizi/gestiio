#!/bin/sh
# Deploy modulo SEND (completo piano A–D) su NAS Gestiio
# Uso: sh ops/deploy-send-module.sh
set -e

HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

echo "==> Backup hardlink"
ssh "$HOST" "cp -al $APP /home/Carmine/apps/gestiio-backup-\$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"

echo "==> Archivio file modulo SEND"
tar -cf /tmp/send-deploy.tar \
  ANALISI_MODULO_SEND.md \
  config/send.php \
  app/Contracts/SendProviderInterface.php \
  app/Contracts/SendProviderResult.php \
  app/Enums/SendApplicantType.php \
  app/Enums/SendDocumentCategory.php \
  app/Enums/SendNoteVisibility.php \
  app/Enums/SendPriority.php \
  app/Enums/SendRequestStatus.php \
  app/Models/SendRequest.php \
  app/Models/SendRequestAssignment.php \
  app/Models/SendRequestAuditLog.php \
  app/Models/SendRequestChecklistItem.php \
  app/Models/SendRequestConsent.php \
  app/Models/SendRequestDelivery.php \
  app/Models/SendRequestDocument.php \
  app/Models/SendRequestNote.php \
  app/Models/SendRequestStatusHistory.php \
  app/Models/SendRequestSubject.php \
  app/Models/SendSetting.php \
  app/Policies/SendRequestPolicy.php \
  app/Http/Controllers/Backend/SendRequestController.php \
  app/Http/Controllers/Backend/AttivaServizioController.php \
  app/Http/Controllers/Backend/AgenteController.php \
  app/Http/Controllers/Backend/DashboardController.php \
  app/Console/Kernel.php \
  app/Console/Commands/SendAssignPending.php \
  app/Console/Commands/SendMarkSlaBreaches.php \
  app/Console/Commands/SendExpireStale.php \
  app/Console/Commands/SendRetentionPurge.php \
  app/Http/Requests/Send \
  app/Http/Services/Send \
  app/Http/Services/SendAssignmentService.php \
  app/Http/Services/SendAuditService.php \
  app/Http/Services/SendChecklistService.php \
  app/Http/Services/SendDocumentService.php \
  app/Http/Services/SendRequestService.php \
  app/Http/Services/SendRequestStatusService.php \
  app/Notifications/NotificaSendAssigned.php \
  app/Notifications/NotificaSendAwaitingAssignment.php \
  app/Notifications/NotificaSendCompleted.php \
  app/Notifications/NotificaSendIntegrationRequired.php \
  app/Notifications/NotificaSendTakenInCharge.php \
  app/Notifications/NotificaSendRejected.php \
  app/Notifications/NotificaSendCancelled.php \
  app/Notifications/NotificaSendDelivered.php \
  app/Providers/AppServiceProvider.php \
  app/Providers/AuthServiceProvider.php \
  database/migrations/2026_07_18_100000_create_send_module_tables.php \
  database/migrations/2026_07_18_100001_seed_servizio_send_permissions.php \
  database/migrations/2026_07_18_140000_add_pricing_to_send_requests_table.php \
  database/migrations/2026_07_18_160000_add_upload_uid_to_send_request_documents.php \
  database/migrations/2026_07_18_180000_send_plan_permissions_cleanup.php \
  docs/SEND_MODULE.md \
  docs/SEND_USER_GUIDE.md \
  docs/SEND_SECURITY.md \
  resources/views/Backend/Send \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  resources/views/Backend/_layout/sidebar-send-menu-item.blade.php \
  resources/views/Backend/_layout/sidebar-send-icon.blade.php \
  resources/views/Backend/Dashboard/showAgente.blade.php \
  resources/views/Backend/Dashboard/showSupervisore.blade.php \
  resources/views/Backend/Agente/show.blade.php \
  resources/views/Backend/Agente/edit.blade.php \
  routes/web-backend.php \
  tests/Feature/Send

echo "==> Upload archivio (base64 via SSH)"
base64 /tmp/send-deploy.tar | ssh "$HOST" "base64 -d > /tmp/send-deploy.tar"

echo "==> Estrazione su host + container"
ssh "$HOST" "
set -e
cd $APP
tar -xf /tmp/send-deploy.tar
find app config database docs resources routes tests ANALISI_MODULO_SEND.md -type f \( -name '*.php' -o -name '*.blade.php' -o -name '*.md' \) -print0 2>/dev/null | xargs -0 sed -i 's/\r$//' || true

$DOCKER cp /tmp/send-deploy.tar $CONTAINER:/tmp/send-deploy.tar
$DOCKER exec $CONTAINER sh -lc 'cd /var/www/html && tar -xf /tmp/send-deploy.tar && rm -f /tmp/send-deploy.tar'
$DOCKER exec $CONTAINER sh -lc \"find /var/www/html/app /var/www/html/config /var/www/html/database /var/www/html/docs /var/www/html/resources /var/www/html/routes /var/www/html/tests -type f \\( -name '*.php' -o -name '*.blade.php' \\) -print0 2>/dev/null | xargs -0 sed -i 's/\\r\$//' || true\"

rm -f /tmp/send-deploy.tar
echo EXTRACT_OK
"

echo "==> Variabili SEND in .env sul container"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'grep -q SEND_PROVIDER /var/www/html/.env || cat >> /var/www/html/.env <<EOF

# SEND — Notifiche Digitali
SEND_PROVIDER=manual
SEND_INTEGRATION_ENABLED=false
SEND_NUMBER_PREFIX=SEND
SEND_ASSIGNMENT_METHOD=least_open
SEND_MAX_UPLOAD_KB=20480
SEND_PRIVACY_VERSION=2026-07-01
SEND_RETENTION_DAYS=0
SEND_PREZZO_CLIENTE=5
SEND_PREZZO_AGENTE=4
EOF
'
"

echo "==> Migration + cache + restart"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan migrate --force'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan send:assign-pending'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan permission:cache-reset'
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
$DOCKER exec $CONTAINER chmod -R 775 /var/www/html/storage/framework
$DOCKER exec -u www-data $CONTAINER sh -lc 'php /var/www/html/artisan view:clear && php /var/www/html/artisan config:clear && php /var/www/html/artisan route:clear && php /var/www/html/artisan cache:clear'
$DOCKER restart $CONTAINER
sleep 5
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
"

echo "==> Verifica file + HTTP"
sleep 8
ssh "$HOST" "
$DOCKER ps --filter name=$CONTAINER --format '{{.Names}} {{.Status}}'
echo '--- file chiave ---'
$DOCKER exec $CONTAINER sh -lc 'ls -la /var/www/html/resources/views/Backend/Send/ | head -20'
$DOCKER exec $CONTAINER sh -lc 'test -f /var/www/html/resources/views/Backend/Send/report.blade.php && echo OK_report'
$DOCKER exec $CONTAINER sh -lc 'test -f /var/www/html/app/Notifications/NotificaSendRejected.php && echo OK_notif_rejected'
$DOCKER exec $CONTAINER sh -lc 'test -f /var/www/html/app/Console/Commands/SendMarkSlaBreaches.php && echo OK_sla_cmd'
$DOCKER exec $CONTAINER sh -lc 'test -f /var/www/html/database/migrations/2026_07_18_180000_send_plan_permissions_cleanup.php && echo OK_migration_cleanup'
$DOCKER exec $CONTAINER sh -lc 'grep -c is_supervisor_view /var/www/html/app/Http/Services/SendRequestService.php'
$DOCKER exec $CONTAINER sh -lc 'grep -c deliveryReceiptPdf /var/www/html/app/Http/Controllers/Backend/SendRequestController.php'
$DOCKER exec $CONTAINER sh -lc 'grep -c send:mark-sla /var/www/html/app/Console/Kernel.php'
curl -s -o /dev/null -w 'send_dashboard=%{http_code}\n' http://localhost:8090/backend/send
curl -s -o /dev/null -w 'send_report=%{http_code}\n' http://localhost:8090/backend/send/report
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan route:list --path=send 2>/dev/null | head -35'
"

rm -f /tmp/send-deploy.tar
echo "DEPLOY_SEND_OK"
