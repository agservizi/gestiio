# HOTFIX: Production Twilio Provider Error

## Problem
```
ERROR: include(.../laravel-notification-channels/twilio/src/TwilioProvider.php):
Failed to open stream: No such file or directory
```

## Root Cause
Twilio notification package was removed during Sentry installation, but the Composer autoload cache in production still references the old provider.

## Immediate Fix (Production)

### Option 1: Full Composer Install (Safest)
```bash
cd /path/to/gestiio
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
# Restart PHP-FPM
systemctl restart php8.2-fpm
```

### Option 2: Quick Autoload Regeneration
```bash
cd /path/to/gestiio
composer dump-autoload --optimize
php artisan cache:clear
# Restart PHP-FPM
systemctl restart php8.2-fpm
```

### Option 3: Manual Cache Clear (Minimal)
```bash
rm -rf /path/to/gestiio/vendor/composer/*.php
composer dump-autoload --optimize
php artisan cache:clear
systemctl restart php8.2-fpm
```

## Verification
```bash
# Should not show Twilio errors
php /path/to/gestiio/artisan tinker
>>> exit
```

## Why This Happened
- Sentry installation removed: `laravel-notification-channels/twilio` and `twilio/sdk`
- Composer autoload cache in production wasn't updated
- PHP tried to load the provider class that no longer exists

## Prevention
- Always run `composer install` with `--optimize-autoloader` after composer changes
- Monitor Sentry/logs for ClassNotFoundException errors
- Test deployments with a full `composer install`

