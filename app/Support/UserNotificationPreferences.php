<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserNotificationPreferences
{
    public static function shouldSend(User $user, Notification $notification, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $className = class_basename($notification);

        if (Str::contains($className, 'Ticket')) {
            $ticket = self::extractTicket($notification);
            if ($ticket && ($ticket->servizio_type ?? null) === 'spedizione-brt') {
                return self::preference($user, 'notifiche_email_spedizioni', true);
            }

            return self::preference($user, 'notifiche_email_ticket', true);
        }

        if (Str::contains($className, ['Spedizione', 'Brt'])) {
            return self::preference($user, 'notifiche_email_spedizioni', true);
        }

        return self::preference($user, 'notifiche_email_amministrative', true);
    }

    public static function wantsBrowserNotifications(User $user): bool
    {
        return self::preference($user, 'notifiche_browser', true);
    }

    protected static function preference(User $user, string $key, bool $default = true): bool
    {
        $value = $user->getExtra($key);
        if ($value === null) {
            return $default;
        }

        return (bool)$value;
    }

    protected static function extractTicket(Notification $notification): ?Ticket
    {
        try {
            $ref = new \ReflectionObject($notification);
            if (!$ref->hasProperty('ticket')) {
                return null;
            }

            $property = $ref->getProperty('ticket');
            $property->setAccessible(true);
            $ticket = $property->getValue($notification);

            return $ticket instanceof Ticket ? $ticket : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
