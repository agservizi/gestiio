#!/bin/sh
# Deploy modulo Locker Point su NAS Gestiio
# Uso: sh ops/deploy-locker-module.sh
set -e

HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

echo "==> Backup hardlink"
ssh "$HOST" "cp -al $APP /home/Carmine/apps/gestiio-backup-\$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"

echo "==> Archivio file modulo Locker Point"
tar -cf /tmp/locker-deploy.tar \
  app/Enums/LockerPackageStatus.php \
  app/Enums/LockerPackageSource.php \
  app/Exceptions/LockerNoAvailabilityException.php \
  app/Models/LockerSetting.php \
  app/Models/LockerStation.php \
  app/Models/LockerPackage.php \
  app/Models/LockerAgentSubscription.php \
  app/Models/LockerCashMovement.php \
  app/Policies/LockerPackagePolicy.php \
  app/Http/Middleware/ValidateLockerApiKey.php \
  app/Http/Services/LockerStationService.php \
  app/Http/Services/LockerAgentSubscriptionService.php \
  app/Http/Services/LockerPackageService.php \
  app/Http/Support/LockerConfig.php \
  app/Http/Support/LockerTagPdf.php \
  app/Http/Resources/LockerPackageResource.php \
  app/Http/Requests/StoreLockerBookingRequest.php \
  app/Http/Requests/LockerPackageActionRequest.php \
  app/Http/Requests/UpdateLockerSettingsRequest.php \
  app/Http/Controllers/Api/ResolvesLockerStationFromRequest.php \
  app/Http/Controllers/Api/RespondsWithLockerJson.php \
  app/Http/Controllers/Api/Public/LockerHealthController.php \
  app/Http/Controllers/Api/Public/LockerBookController.php \
  app/Http/Controllers/Api/Public/LockerAvailabilityController.php \
  app/Http/Controllers/Api/Public/LockerAvailabilityRangeController.php \
  app/Http/Controllers/Api/Public/LockerPricingController.php \
  app/Http/Controllers/Api/Public/LockerPackageController.php \
  app/Http/Controllers/Backend/LockerPackageController.php \
  app/Http/Controllers/Frontend/LockerBookingController.php \
  app/Http/Controllers/Frontend/LockerPickupController.php \
  app/Console/Commands/ChargeLockerAgentSubscriptions.php \
  app/Console/Kernel.php \
  app/Http/Kernel.php \
  app/Http/Controllers/Backend/AgenteController.php \
  app/Http/Controllers/Backend/AttivaServizioController.php \
  app/Providers/AuthServiceProvider.php \
  config/locker.php \
  config/locker_settings_fields.php \
  routes/api.php \
  routes/web.php \
  routes/web-backend.php \
  database/migrations/2026_07_16_100000_create_locker_settings_table.php \
  database/migrations/2026_07_16_100001_create_locker_stations_table.php \
  database/migrations/2026_07_16_100002_create_locker_packages_table.php \
  database/migrations/2026_07_16_100003_create_locker_agent_subscriptions_and_cash_movements.php \
  database/migrations/2026_07_16_210000_seed_servizio_locker_point_permission.php \
  resources/views/Backend/LockerPoint \
  resources/views/Frontend/LockerPoint \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  resources/views/Backend/Agente/edit.blade.php \
  docs/LOCKER_API_PUBLIC.md \
  ops/smoke-locker-point.php \
  ops/locker-env.fragment

echo "==> Upload archivio (base64 via SSH)"
base64 /tmp/locker-deploy.tar | ssh "$HOST" "base64 -d > /tmp/locker-deploy.tar"

echo "==> Estrazione su host + container"
ssh "$HOST" "
set -e
cd $APP
tar -xf /tmp/locker-deploy.tar
find . -name '*.php' -print0 | xargs -0 sed -i 's/\r$//' || true
find . -name '*.blade.php' -print0 | xargs -0 sed -i 's/\r$//' || true

$DOCKER exec $CONTAINER mkdir -p /var/www/html/app/Enums /var/www/html/app/Http/Controllers/Api/Public /var/www/html/resources/views/Backend/LockerPoint /var/www/html/resources/views/Frontend/LockerPoint /var/www/html/database/migrations

$DOCKER cp $APP/app/Enums/LockerPackageStatus.php $CONTAINER:/var/www/html/app/Enums/LockerPackageStatus.php
$DOCKER cp $APP/app/Enums/LockerPackageSource.php $CONTAINER:/var/www/html/app/Enums/LockerPackageSource.php
$DOCKER cp $APP/app/Exceptions/LockerNoAvailabilityException.php $CONTAINER:/var/www/html/app/Exceptions/LockerNoAvailabilityException.php
$DOCKER cp $APP/app/Models/LockerSetting.php $CONTAINER:/var/www/html/app/Models/LockerSetting.php
$DOCKER cp $APP/app/Models/LockerStation.php $CONTAINER:/var/www/html/app/Models/LockerStation.php
$DOCKER cp $APP/app/Models/LockerPackage.php $CONTAINER:/var/www/html/app/Models/LockerPackage.php
$DOCKER cp $APP/app/Models/LockerAgentSubscription.php $CONTAINER:/var/www/html/app/Models/LockerAgentSubscription.php
$DOCKER cp $APP/app/Models/LockerCashMovement.php $CONTAINER:/var/www/html/app/Models/LockerCashMovement.php
$DOCKER cp $APP/app/Policies/LockerPackagePolicy.php $CONTAINER:/var/www/html/app/Policies/LockerPackagePolicy.php
$DOCKER cp $APP/app/Http/Middleware/ValidateLockerApiKey.php $CONTAINER:/var/www/html/app/Http/Middleware/ValidateLockerApiKey.php
$DOCKER cp $APP/app/Http/Services/LockerStationService.php $CONTAINER:/var/www/html/app/Http/Services/LockerStationService.php
$DOCKER cp $APP/app/Http/Services/LockerAgentSubscriptionService.php $CONTAINER:/var/www/html/app/Http/Services/LockerAgentSubscriptionService.php
$DOCKER cp $APP/app/Http/Services/LockerPackageService.php $CONTAINER:/var/www/html/app/Http/Services/LockerPackageService.php
$DOCKER cp $APP/app/Http/Support/LockerConfig.php $CONTAINER:/var/www/html/app/Http/Support/LockerConfig.php
$DOCKER cp $APP/app/Http/Support/LockerTagPdf.php $CONTAINER:/var/www/html/app/Http/Support/LockerTagPdf.php
$DOCKER cp $APP/app/Http/Resources/LockerPackageResource.php $CONTAINER:/var/www/html/app/Http/Resources/LockerPackageResource.php
$DOCKER cp $APP/app/Http/Requests/StoreLockerBookingRequest.php $CONTAINER:/var/www/html/app/Http/Requests/StoreLockerBookingRequest.php
$DOCKER cp $APP/app/Http/Requests/LockerPackageActionRequest.php $CONTAINER:/var/www/html/app/Http/Requests/LockerPackageActionRequest.php
$DOCKER cp $APP/app/Http/Requests/UpdateLockerSettingsRequest.php $CONTAINER:/var/www/html/app/Http/Requests/UpdateLockerSettingsRequest.php
$DOCKER cp $APP/app/Http/Controllers/Api/ResolvesLockerStationFromRequest.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/ResolvesLockerStationFromRequest.php
$DOCKER cp $APP/app/Http/Controllers/Api/RespondsWithLockerJson.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/RespondsWithLockerJson.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerHealthController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerHealthController.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerBookController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerBookController.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerAvailabilityController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerAvailabilityController.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerAvailabilityRangeController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerAvailabilityRangeController.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerPricingController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerPricingController.php
$DOCKER cp $APP/app/Http/Controllers/Api/Public/LockerPackageController.php $CONTAINER:/var/www/html/app/Http/Controllers/Api/Public/LockerPackageController.php
$DOCKER cp $APP/app/Http/Controllers/Backend/LockerPackageController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/LockerPackageController.php
$DOCKER cp $APP/app/Http/Controllers/Frontend/LockerBookingController.php $CONTAINER:/var/www/html/app/Http/Controllers/Frontend/LockerBookingController.php
$DOCKER cp $APP/app/Http/Controllers/Frontend/LockerPickupController.php $CONTAINER:/var/www/html/app/Http/Controllers/Frontend/LockerPickupController.php
$DOCKER cp $APP/app/Console/Commands/ChargeLockerAgentSubscriptions.php $CONTAINER:/var/www/html/app/Console/Commands/ChargeLockerAgentSubscriptions.php
$DOCKER cp $APP/app/Console/Kernel.php $CONTAINER:/var/www/html/app/Console/Kernel.php
$DOCKER cp $APP/app/Http/Kernel.php $CONTAINER:/var/www/html/app/Http/Kernel.php
$DOCKER cp $APP/app/Http/Controllers/Backend/AgenteController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/AgenteController.php
$DOCKER cp $APP/app/Http/Controllers/Backend/AttivaServizioController.php $CONTAINER:/var/www/html/app/Http/Controllers/Backend/AttivaServizioController.php
$DOCKER cp $APP/app/Providers/AuthServiceProvider.php $CONTAINER:/var/www/html/app/Providers/AuthServiceProvider.php
$DOCKER cp $APP/config/locker.php $CONTAINER:/var/www/html/config/locker.php
$DOCKER cp $APP/config/locker_settings_fields.php $CONTAINER:/var/www/html/config/locker_settings_fields.php
$DOCKER cp $APP/routes/api.php $CONTAINER:/var/www/html/routes/api.php
$DOCKER cp $APP/routes/web.php $CONTAINER:/var/www/html/routes/web.php
$DOCKER cp $APP/routes/web-backend.php $CONTAINER:/var/www/html/routes/web-backend.php
$DOCKER cp $APP/database/migrations/2026_07_16_100000_create_locker_settings_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_16_100000_create_locker_settings_table.php
$DOCKER cp $APP/database/migrations/2026_07_16_100001_create_locker_stations_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_16_100001_create_locker_stations_table.php
$DOCKER cp $APP/database/migrations/2026_07_16_100002_create_locker_packages_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_16_100002_create_locker_packages_table.php
$DOCKER cp $APP/database/migrations/2026_07_16_100003_create_locker_agent_subscriptions_and_cash_movements.php $CONTAINER:/var/www/html/database/migrations/2026_07_16_100003_create_locker_agent_subscriptions_and_cash_movements.php
$DOCKER cp $APP/database/migrations/2026_07_16_210000_seed_servizio_locker_point_permission.php $CONTAINER:/var/www/html/database/migrations/2026_07_16_210000_seed_servizio_locker_point_permission.php
$DOCKER cp $APP/resources/views/Backend/LockerPoint $CONTAINER:/var/www/html/resources/views/Backend/
$DOCKER cp $APP/resources/views/Frontend/LockerPoint $CONTAINER:/var/www/html/resources/views/Frontend/
$DOCKER cp $APP/resources/views/Backend/_layout/app-sidebar-menu.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
$DOCKER cp $APP/resources/views/Backend/Agente/edit.blade.php $CONTAINER:/var/www/html/resources/views/Backend/Agente/edit.blade.php
$DOCKER cp $APP/docs/LOCKER_API_PUBLIC.md $CONTAINER:/var/www/html/docs/LOCKER_API_PUBLIC.md
$DOCKER cp $APP/ops/smoke-locker-point.php $CONTAINER:/var/www/html/ops/smoke-locker-point.php
rm -f /tmp/locker-deploy.tar
echo EXTRACT_OK
"

echo "==> LOCKER_API_KEY in .env sul container"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'grep -q LOCKER_API_KEY /var/www/html/.env || cat >> /var/www/html/.env <<EOF

# Locker Point
LOCKER_API_KEY=\$(php -r \"echo bin2hex(random_bytes(32));\")
LOCKER_DEFAULT_RATE=3
LOCKER_MAX_CAPACITY=100
LOCKER_MAX_PACKAGES_PER_BOOKING=5
LOCKER_MIN_DAYS=1
LOCKER_CURRENCY=EUR
EOF
'
"

echo "==> Migration + cache + permessi"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan migrate --force'
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan permission:cache-reset'
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
$DOCKER exec $CONTAINER chmod -R 775 /var/www/html/storage/framework
$DOCKER exec -u www-data $CONTAINER sh -lc 'php /var/www/html/artisan view:clear && php /var/www/html/artisan config:clear && php /var/www/html/artisan route:clear'
$DOCKER restart $CONTAINER
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
"

echo "==> Smoke test"
sleep 8
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/ops/smoke-locker-point.php'
"

echo "==> Verifica HTTP"
ssh "$HOST" "
$DOCKER ps --filter name=$CONTAINER --format '{{.Names}} {{.Status}}'
curl -s -o /dev/null -w 'locker_point=%{http_code}\n' http://localhost:8090/locker-point
curl -s -o /dev/null -w 'locker_health=%{http_code}\n' http://localhost:8090/api/public/locker-point/health
curl -s -o /dev/null -w 'locker_dashboard=%{http_code}\n' http://localhost:8090/backend/locker-point/dashboard
"

echo "DEPLOY_LOCKER_OK — recupera LOCKER_API_KEY con:"
echo "  ssh $HOST \"$DOCKER exec $CONTAINER grep LOCKER_API_KEY /var/www/html/.env\""
