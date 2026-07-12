<?php

namespace App\Listeners;

use App\Events\LuggageDepositCreated;
use App\Http\Support\LuggageConfig;
use App\Models\User;
use App\Notifications\NotificaLuggageDepositCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyStaffOnLuggageDepositCreated implements ShouldQueue
{
    public function handle(LuggageDepositCreated $event): void
    {
        if (! LuggageConfig::notifyStaff()) {
            return;
        }

        try {
            $notification = new NotificaLuggageDepositCreated($event->deposit);

            $admins = User::permission('admin')->get();

            foreach ($admins as $admin) {
                $admin->notify($notification);
            }

            $extraEmail = LuggageConfig::staffNotificationEmail();
            if ($extraEmail !== '') {
                Notification::route('mail', $extraEmail)->notify($notification);
            }
        } catch (Throwable $e) {
            Log::warning('luggage_deposit_staff_notification_failed', [
                'deposit_id' => $event->deposit->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
