#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
ENV="$APP/.env"

# Fix botched hostnames
perl -pi -e 's/smtp\.rresend\.com/smtp.resend.com/g; s/smtp\.esend\.com/smtp.resend.com/g' "$ENV"
perl -pi -e 's/^MAIL_MAILER=esend$/MAIL_MAILER=resend/; s/^MAIL_USERNAME=esend$/MAIL_USERNAME=resend/; s/^RESEND_USERNAME=esend$/RESEND_USERNAME=resend/' "$ENV"

echo '=== mail/resend (redacted) ==='
grep -E '^(MAIL_|RESEND_)' "$ENV" | sed 's/PASSWORD=.*/PASSWORD=***/;s/KEY=.*/KEY=***/'

$DOCKER cp "$ENV" gestiio-app:/var/www/html/.env
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear

cat > /tmp/otp_smoke.php <<'PHP'
<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'default='.config('mail.default').PHP_EOL;
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
