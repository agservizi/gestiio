#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128
ENV="$APP/.env"

# Backup
cp -a "$ENV" "$ENV.bak-otp-$(date +%Y%m%d%H%M%S)"

# Fix typo esend -> resend
perl -pi -e 's/^MAIL_MAILER=esend$/MAIL_MAILER=resend/' "$ENV"
perl -pi -e 's/^MAIL_HOST=smtp\.esend\.com$/MAIL_HOST=smtp.resend.com/' "$ENV"
perl -pi -e 's/^MAIL_USERNAME=esend$/MAIL_USERNAME=resend/' "$ENV"

# Ensure RESEND_* aligned with MAIL_* if missing
if ! grep -q '^RESEND_HOST=' "$ENV"; then
  echo 'RESEND_HOST=smtp.resend.com' >> "$ENV"
fi
if ! grep -q '^RESEND_PORT=' "$ENV"; then
  echo 'RESEND_PORT=587' >> "$ENV"
fi
if ! grep -q '^RESEND_USERNAME=' "$ENV"; then
  echo 'RESEND_USERNAME=resend' >> "$ENV"
fi
# Copy MAIL_PASSWORD into RESEND_KEY if RESEND_KEY empty/missing
if ! grep -q '^RESEND_KEY=.' "$ENV"; then
  MP=$(grep '^MAIL_PASSWORD=' "$ENV" | head -1 | cut -d= -f2-)
  if [ -n "$MP" ]; then
    if grep -q '^RESEND_KEY=' "$ENV"; then
      perl -pi -e "s|^RESEND_KEY=.*|RESEND_KEY=$MP|" "$ENV"
    else
      echo "RESEND_KEY=$MP" >> "$ENV"
    fi
  fi
fi

echo '=== mail lines (redacted) ==='
grep -E '^(MAIL_|RESEND_)' "$ENV" | sed 's/PASSWORD=.*/PASSWORD=***/;s/KEY=.*/KEY=***/;s/SECRET=.*/SECRET=***/'

$DOCKER cp "$ENV" gestiio-app:/var/www/html/.env
$DOCKER exec gestiio-app php /var/www/html/artisan config:clear
$DOCKER exec gestiio-app php /var/www/html/artisan optimize:clear

echo '=== config check ==='
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "default=".config("mail.default")."\n";
echo "has_esend=".(config("mail.mailers.esend")?"yes":"no")."\n";
echo "has_resend=".(config("mail.mailers.resend")?"yes":"no")."\n";
echo "resend_host=".config("mail.mailers.resend.host")."\n";
echo "resend_user=".config("mail.mailers.resend.username")."\n";
echo "resend_pass_len=".strlen((string)config("mail.mailers.resend.password"))."\n";
echo "from=".config("mail.from.address")."\n";
'

# Smoke: generate OTP and try notify (catch errors)
$DOCKER exec gestiio-app php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::find(2);
try {
  app(App\Notifications\SendOTP::class)->sendToUser($u);
  echo "OTP_SEND_OK\n";
} catch (Throwable $e) {
  echo "OTP_SEND_FAIL ".$e->getMessage()."\n";
}
'
echo DONE
