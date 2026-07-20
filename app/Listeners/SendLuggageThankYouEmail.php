<?php

namespace App\Listeners;

use App\Events\LuggageDepositCheckedOut;
use App\Http\Support\LuggageConfig;
use App\Notifications\NotificaLuggageThankYou;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLuggageThankYouEmail implements ShouldQueue
{
    public function handle(LuggageDepositCheckedOut $event): void
    {
        if (! LuggageConfig::notifyCustomerThankYou()) {
            return;
        }

        if (empty($event->deposit->customer_email)) {
            return;
        }

        try {
            Notification::route('mail', $event->deposit->customer_email)
                ->notify(new NotificaLuggageThankYou($event->deposit));
        } catch (Throwable $e) {
            Log::warning('luggage_thank_you_email_failed', [
                'deposit_id' => $event->deposit->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
