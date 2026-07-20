<?php

namespace App\Listeners;

use App\Events\LuggageDepositCreated;
use App\Http\Support\LuggageConfig;
use App\Notifications\NotificaLuggageBookingConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLuggageBookingConfirmationEmail implements ShouldQueue
{
    public function handle(LuggageDepositCreated $event): void
    {
        if (! LuggageConfig::notifyCustomerBooking()) {
            return;
        }

        $deposit = $event->deposit->fresh();
        if (empty($deposit->customer_email)) {
            return;
        }

        try {
            Notification::route('mail', $deposit->customer_email)
                ->notify(new NotificaLuggageBookingConfirmation($deposit));
        } catch (Throwable $e) {
            Log::warning('luggage_booking_confirmation_email_failed', [
                'deposit_id' => $deposit->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
