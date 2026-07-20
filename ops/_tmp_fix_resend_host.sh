#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
ENV="$APP/.env"

perl -pi -e 's/^RESEND_HOST=smtp\.esend\.com$/RESEND_HOST=smtp.resend.com/' "$ENV"
perl -pi -e 's/^RESEND_USERNAME=esend$/RESEND_USERNAME=resend/' "$ENV"
# Also fix any remaining esend typos
perl -pi -e 's/esend\.com/resend.com/g; s/=esend$/=resend/g' "$ENV"

echo '=== mail/resend (redacted) ==='
grep -E '^(MAIL_|RESEND_)' "$ENV" | sed 's/PASSWORD=.*/PASSWORD=***/;s/KEY=.*/KEY=***/'

$DOCKER cp "$ENV" gestiio-app:/var/www/html/.env
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

cat > /tmp/otp_smoke.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'host='.config('mail.mailers.resend.host').' user='.config('mail.mailers.resend.username').PHP_EOL;
$u = App\Models\User::find(2);
try {
    app(App\Notifications\SendOTP::class)->sendToUser($u);
    echo "OTP_SEND_OK\n";
} catch (Throwable $e) {
    echo 'OTP_SEND_FAIL '.$e->getMessage()."\n";
}
PHP
$DOCKER cp /tmp/otp_smoke.php gestiio-app:/tmp/otp_smoke.php
$DOCKER exec gestiio-app php /tmp/otp_smoke.php
echo DONE
