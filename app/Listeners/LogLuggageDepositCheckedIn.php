<?php

namespace App\Listeners;

use App\Events\LuggageDepositCheckedIn;
use Illuminate\Support\Facades\Log;

class LogLuggageDepositCheckedIn
{
    public function handle(LuggageDepositCheckedIn $event): void
    {
        Log::info('luggage_deposit_checked_in', [
            'deposit_id' => $event->deposit->id,
            'code' => $event->deposit->code,
            'bag_count' => $event->deposit->bag_count,
        ]);
    }
}
