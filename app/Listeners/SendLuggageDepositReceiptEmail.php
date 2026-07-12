<?php

namespace App\Listeners;

use App\Events\LuggageDepositCheckedOut;
use App\Http\Support\LuggageConfig;
use App\Notifications\NotificaLuggageDepositReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendLuggageDepositReceiptEmail implements ShouldQueue
{
    public function handle(LuggageDepositCheckedOut $event): void
    {
        if (! LuggageConfig::notifyCustomerReceipt()) {
            return;
        }

        if (empty($event->deposit->customer_email)) {
            return;
        }

        Notification::route('mail', $event->deposit->customer_email)
            ->notify(new NotificaLuggageDepositReceipt($event->deposit, $event->totalAmount));
    }
}
