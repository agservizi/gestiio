<?php

namespace App\Actions\Luggage;

use App\Enums\LuggageDepositStatus;
use App\Http\Support\LuggageConfig;
use App\Models\LuggageDeposit;
use App\Notifications\NotificaLuggagePickupQr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendPickupQrReminder
{
    public function __invoke(LuggageDeposit $deposit, ?int $hoursBefore = null): bool
    {
        if (! LuggageConfig::notifyCustomerPickupQr()) {
            return false;
        }

        $deposit = $deposit->fresh();

        if ($deposit->status !== LuggageDepositStatus::CHECK_IN
            || empty($deposit->customer_email)
            || $deposit->expected_check_out === null
            || $deposit->pickup_qr_sent_at !== null
        ) {
            return false;
        }

        $hours = max(1, $hoursBefore ?? LuggageConfig::pickupQrHoursBefore());
        $threshold = now()->addHours($hours);
        if ($deposit->expected_check_out->gt($threshold)) {
            return false;
        }

        try {
            Notification::route('mail', $deposit->customer_email)
                ->notify(new NotificaLuggagePickupQr($deposit));

            $deposit->update(['pickup_qr_sent_at' => now()]);

            return true;
        } catch (Throwable $e) {
            Log::warning('luggage_pickup_qr_reminder_failed', [
                'deposit_id' => $deposit->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
