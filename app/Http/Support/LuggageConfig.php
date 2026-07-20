<?php

namespace App\Http\Support;

use Illuminate\Support\Facades\Schema;

class LuggageConfig
{
    public static function setting(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return setting($key, $default);
    }

    public static function onlineBookingEnabled(): bool
    {
        return (bool) self::setting('luggage_online_booking_enabled', true);
    }

    public static function bookingInstructions(): string
    {
        return (string) self::setting('luggage_booking_instructions', '');
    }

    public static function notifyStaff(): bool
    {
        return (bool) self::setting('luggage_notify_staff', true);
    }

    public static function notifyCustomerBooking(): bool
    {
        return (bool) self::setting('luggage_notify_customer_booking', true);
    }

    public static function notifyCustomerThankYou(): bool
    {
        if (self::setting('luggage_notify_customer_thank_you') !== null) {
            return (bool) self::setting('luggage_notify_customer_thank_you');
        }

        return (bool) self::setting('luggage_notify_customer_receipt', true);
    }

    public static function notifyCustomerPickupQr(): bool
    {
        return (bool) self::setting('luggage_notify_customer_pickup_qr', true);
    }

    public static function pickupQrHoursBefore(): int
    {
        return max(1, (int) self::setting('luggage_pickup_qr_hours_before', 24));
    }

    public static function staffNotificationEmail(): string
    {
        return trim((string) self::setting('luggage_staff_notification_email', ''));
    }

    public static function agentMonthlyFee(): float
    {
        return max(0, (float) self::setting('luggage_agent_monthly_fee', 2));
    }
}
