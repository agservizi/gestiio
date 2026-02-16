<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\UserNotificationPreferences;
use Illuminate\Notifications\Events\NotificationSending;

class RespectUserNotificationPreferences
{
    public function handle(NotificationSending $event): bool
    {
        if (!($event->notifiable instanceof User)) {
            return true;
        }

        return UserNotificationPreferences::shouldSend(
            $event->notifiable,
            $event->notification,
            $event->channel
        );
    }
}
