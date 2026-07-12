#!/bin/sh
# Deploy modulo Deposito Bagagli su NAS Gestiio
# Uso (da Git Bash / WSL, nella root del repo):
#   sh ops/deploy-luggage-module.sh
set -e

HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

echo "==> Backup hardlink"
ssh "$HOST" "cp -al $APP /home/Carmine/apps/gestiio-backup-\$(date +%Y%m%d-%H%M%S) && echo BACKUP_OK"

echo "==> Archivio file modulo"
tar -cf /tmp/luggage-deploy.tar \
  app/Enums/LuggageDepositStatus.php \
  app/Events/LuggageDepositCreated.php \
  app/Events/LuggageDepositCheckedIn.php \
  app/Events/LuggageDepositCheckedOut.php \
  app/Exceptions/LuggageNoAvailabilityException.php \
  app/Http/Kernel.php \
  app/Http/Middleware/ValidateLuggageApiKey.php \
  app/Http/Services/LuggageDepositService.php \
  app/Http/Resources/LuggageDepositResource.php \
  app/Http/Requests/StoreLuggageBookingRequest.php \
  app/Http/Requests/LuggageDepositActionRequest.php \
  app/Http/Requests/UpdateLuggageSettingsRequest.php \
  app/Http/Controllers/Backend/LuggageDepositController.php \
  app/Http/Controllers/Backend/SettingController.php \
  app/Http/Controllers/Frontend/LuggageBookingController.php \
  app/Http/Controllers/Frontend/LuggageVerifyPageController.php \
  app/Http/Controllers/Api \
  app/Http/OpenApi \
  app/Http/Support \
  app/Models/LuggageDeposit.php \
  app/Models/LuggageSetting.php \
  app/Policies/LuggageDepositPolicy.php \
  app/Listeners/NotifyStaffOnLuggageDepositCreated.php \
  app/Listeners/SendLuggageDepositReceiptEmail.php \
  app/Notifications/NotificaLuggageDepositCreated.php \
  app/Notifications/NotificaLuggageDepositReceipt.php \
  app/Providers/AuthServiceProvider.php \
  app/Providers/EventServiceProvider.php \
  config/luggage.php \
  config/luggage_settings_fields.php \
  config/setting_fields.php \
  routes/api.php \
  routes/web.php \
  routes/web-backend.php \
  database/migrations/2026_07_12_100001_create_luggage_settings_table.php \
  database/migrations/2026_07_12_100002_create_luggage_deposits_table.php \
  database/seeders/LuggageSettingSeeder.php \
  database/migrations/2026_07_12_100003_create_luggage_cash_movements_table.php \
  app/Models/LuggageCashMovement.php \
  app/Http/Support/LuggageQrCode.php \
  app/Listeners/LogLuggageDepositCheckedIn.php \
  resources/views/Backend/_layout/app-sidebar-menu.blade.php \
  resources/views/Backend/Setting/index.blade.php \
  resources/views/Backend/Setting/sections \
  resources/views/Backend/Dashboard \
  resources/views/auth/login.blade.php \
  resources/views/Backend/LuggageDeposit \
  resources/views/Frontend/LuggageDeposit

echo "==> Upload archivio (base64 via SSH)"
base64 /tmp/luggage-deploy.tar | ssh "$HOST" "base64 -d > /tmp/luggage-deploy.tar"

echo "==> Estrazione su host + container"
ssh "$HOST" "
set -e
cd $APP
tar -xf /tmp/luggage-deploy.tar
find . -name '*.php' -print0 | xargs -0 sed -i 's/\r$//' || true
find . -name '*.blade.php' -print0 | xargs -0 sed -i 's/\r$//' || true
# Copia nel container solo i file del modulo (NON l'intero $APP/.env)
$DOCKER cp $APP/app/Enums/LuggageDepositStatus.php $CONTAINER:/var/www/html/app/Enums/LuggageDepositStatus.php
$DOCKER cp $APP/app/Http $CONTAINER:/var/www/html/app/
$DOCKER cp $APP/app/Models/LuggageDeposit.php $CONTAINER:/var/www/html/app/Models/LuggageDeposit.php
$DOCKER cp $APP/app/Models/LuggageSetting.php $CONTAINER:/var/www/html/app/Models/LuggageSetting.php
$DOCKER cp $APP/app/Models/LuggageCashMovement.php $CONTAINER:/var/www/html/app/Models/LuggageCashMovement.php
$DOCKER cp $APP/app/Models/Cliente.php $CONTAINER:/var/www/html/app/Models/Cliente.php
$DOCKER cp $APP/app/Policies/LuggageDepositPolicy.php $CONTAINER:/var/www/html/app/Policies/LuggageDepositPolicy.php
$DOCKER cp $APP/app/Listeners/NotifyStaffOnLuggageDepositCreated.php $CONTAINER:/var/www/html/app/Listeners/NotifyStaffOnLuggageDepositCreated.php
$DOCKER cp $APP/app/Listeners/LogLuggageDepositCheckedIn.php $CONTAINER:/var/www/html/app/Listeners/LogLuggageDepositCheckedIn.php
$DOCKER cp $APP/app/Listeners/SendLuggageDepositReceiptEmail.php $CONTAINER:/var/www/html/app/Listeners/SendLuggageDepositReceiptEmail.php
$DOCKER cp $APP/app/Notifications/NotificaLuggageDepositCreated.php $CONTAINER:/var/www/html/app/Notifications/NotificaLuggageDepositCreated.php
$DOCKER cp $APP/app/Notifications/NotificaLuggageDepositReceipt.php $CONTAINER:/var/www/html/app/Notifications/NotificaLuggageDepositReceipt.php
$DOCKER cp $APP/app/Events $CONTAINER:/var/www/html/app/
$DOCKER cp $APP/app/Exceptions/LuggageNoAvailabilityException.php $CONTAINER:/var/www/html/app/Exceptions/LuggageNoAvailabilityException.php
$DOCKER cp $APP/app/Providers/AuthServiceProvider.php $CONTAINER:/var/www/html/app/Providers/AuthServiceProvider.php
$DOCKER cp $APP/app/Providers/EventServiceProvider.php $CONTAINER:/var/www/html/app/Providers/EventServiceProvider.php
$DOCKER cp $APP/config/luggage.php $CONTAINER:/var/www/html/config/luggage.php
$DOCKER cp $APP/config/luggage_settings_fields.php $CONTAINER:/var/www/html/config/luggage_settings_fields.php
$DOCKER cp $APP/config/setting_fields.php $CONTAINER:/var/www/html/config/setting_fields.php
$DOCKER cp $APP/routes/api.php $CONTAINER:/var/www/html/routes/api.php
$DOCKER cp $APP/routes/web.php $CONTAINER:/var/www/html/routes/web.php
$DOCKER cp $APP/routes/web-backend.php $CONTAINER:/var/www/html/routes/web-backend.php
$DOCKER cp $APP/resources/views/Backend/_layout/app-sidebar-menu.blade.php $CONTAINER:/var/www/html/resources/views/Backend/_layout/app-sidebar-menu.blade.php
$DOCKER cp $APP/resources/views/Backend/Setting $CONTAINER:/var/www/html/resources/views/Backend/
$DOCKER cp $APP/resources/views/Backend/Dashboard $CONTAINER:/var/www/html/resources/views/Backend/
$DOCKER cp $APP/resources/views/auth/login.blade.php $CONTAINER:/var/www/html/resources/views/auth/login.blade.php
$DOCKER cp $APP/resources/views/Backend/LuggageDeposit $CONTAINER:/var/www/html/resources/views/Backend/
$DOCKER cp $APP/resources/views/Frontend/LuggageDeposit $CONTAINER:/var/www/html/resources/views/Frontend/
$DOCKER cp $APP/database/migrations/2026_07_12_100001_create_luggage_settings_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_12_100001_create_luggage_settings_table.php
$DOCKER cp $APP/database/migrations/2026_07_12_100002_create_luggage_deposits_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_12_100002_create_luggage_deposits_table.php
$DOCKER cp $APP/database/migrations/2026_07_12_100003_create_luggage_cash_movements_table.php $CONTAINER:/var/www/html/database/migrations/2026_07_12_100003_create_luggage_cash_movements_table.php
$DOCKER cp $APP/database/seeders/LuggageSettingSeeder.php $CONTAINER:/var/www/html/database/seeders/LuggageSettingSeeder.php
$DOCKER cp $APP/docs/LUGGAGE_API_PUBLIC.md $CONTAINER:/var/www/html/docs/LUGGAGE_API_PUBLIC.md
$DOCKER cp $APP/docs/LUGGAGE_API_ADMIN.md $CONTAINER:/var/www/html/docs/LUGGAGE_API_ADMIN.md
rm -f /tmp/luggage-deploy.tar
echo EXTRACT_OK
"

echo "==> Verifica LUGGAGE_API_KEY in .env sul container"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'grep -q LUGGAGE_API_KEY /var/www/html/.env || cat >> /var/www/html/.env <<EOF

# Deposito Bagagli
LUGGAGE_API_KEY=\$(php -r \"echo bin2hex(random_bytes(32));\")
LUGGAGE_DEFAULT_RATE=2
LUGGAGE_MAX_CAPACITY=50
LUGGAGE_MAX_BAGS_PER_BOOKING=10
LUGGAGE_MIN_DAYS=1
LUGGAGE_CURRENCY=EUR
EOF
'
"

echo "==> Migration + cache + permessi storage"
ssh "$HOST" "
$DOCKER exec $CONTAINER sh -lc 'php /var/www/html/artisan migrate --force'
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
$DOCKER exec $CONTAINER chmod -R 775 /var/www/html/storage/framework
$DOCKER exec -u www-data $CONTAINER sh -lc 'php /var/www/html/artisan view:clear && php /var/www/html/artisan config:clear'
$DOCKER restart $CONTAINER
$DOCKER exec $CONTAINER chown -R www-data:www-data /var/www/html/storage
"

echo "==> Verifica"
sleep 6
ssh "$HOST" "
$DOCKER ps --filter name=$CONTAINER --format '{{.Names}} {{.Status}}'
curl -s -o /dev/null -w 'login=%{http_code}\n' http://localhost:8090/login
curl -s -o /dev/null -w 'deposito_bagagli=%{http_code}\n' http://localhost:8090/deposito-bagagli
curl -s -o /dev/null -w 'luggage_dashboard=%{http_code}\n' http://localhost:8090/backend/deposito-bagagli/dashboard
curl -s -o /dev/null -w 'luggage_health=%{http_code}\n' http://localhost:8090/api/public/deposito-bagagli/health
"

echo "DEPLOY_LUGGAGE_OK — recupera LUGGAGE_API_KEY con:"
echo "  ssh $HOST \"$DOCKER exec $CONTAINER grep LUGGAGE_API_KEY /var/www/html/.env\""
