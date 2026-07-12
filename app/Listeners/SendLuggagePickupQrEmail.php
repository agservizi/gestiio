<?php

namespace App\Listeners;

use App\Events\LuggageDepositCheckedIn;
use App\Http\Support\LuggageConfig;
use App\Notifications\NotificaLuggagePickupQr;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLuggagePickupQrEmail implements ShouldQueue
{
    public function handle(LuggageDepositCheckedIn $event): void
    {
        if (! LuggageConfig::notifyCustomerPickupQr()) {
            return;
        }

        $deposit = $event->deposit->fresh();
        if (empty($deposit->customer_email)) {
            return;
        }

        try {
            Notification::route('mail', $deposit->customer_email)
                ->notify(new NotificaLuggagePickupQr($deposit));
        } catch (Throwable $e) {
            Log::warning('luggage_pickup_qr_email_failed', [
                'deposit_id' => $deposit->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
