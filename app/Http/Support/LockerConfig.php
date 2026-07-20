<?php

namespace App\Http\Support;

use Illuminate\Support\Facades\Schema;

class LockerConfig
{
    public static function setting(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return setting($key, $default);
    }

    public static function onlineIntakeEnabled(): bool
    {
        return (bool) self::setting('locker_online_intake_enabled', true);
    }

    public static function bookingInstructions(): string
    {
        return (string) self::setting('locker_booking_instructions', '');
    }

    public static function notifyStaff(): bool
    {
        return (bool) self::setting('locker_notify_staff', true);
    }

    public static function staffNotificationEmail(): string
    {
        return trim((string) self::setting('locker_staff_notification_email', ''));
    }

    public static function agentMonthlyFee(): float
    {
        return max(0, (float) self::setting('locker_agent_monthly_fee', 2));
    }

    public static function maxPhotoKb(): int
    {
        return max(512, (int) config('locker.max_photo_kb', 8192));
    }
}
