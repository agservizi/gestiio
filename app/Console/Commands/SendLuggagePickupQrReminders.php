<?php

namespace App\Console\Commands;

use App\Actions\Luggage\SendPickupQrReminder;
use App\Enums\LuggageDepositStatus;
use App\Http\Support\LuggageConfig;
use App\Models\LuggageDeposit;
use Illuminate\Console\Command;

class SendLuggagePickupQrReminders extends Command
{
    protected $signature = 'luggage:send-pickup-qr-reminders {--hours= : Override hours before pickup}';

    protected $description = 'Send pickup QR emails when expected_check_out is approaching';

    public function handle(SendPickupQrReminder $sendPickupQrReminder): int
    {
        if (! LuggageConfig::notifyCustomerPickupQr()) {
            $this->info('Pickup QR notifications disabled.');

            return self::SUCCESS;
        }

        $hours = (int) ($this->option('hours') ?: LuggageConfig::pickupQrHoursBefore());
        $threshold = now()->addHours(max(1, $hours));

        $deposits = LuggageDeposit::query()
            ->where('status', LuggageDepositStatus::CHECK_IN)
            ->whereNotNull('customer_email')
            ->whereNotNull('expected_check_out')
            ->whereNull('pickup_qr_sent_at')
            ->where('expected_check_out', '<=', $threshold)
            ->get();

        $sent = 0;

        foreach ($deposits as $deposit) {
            if ($sendPickupQrReminder($deposit, $hours)) {
                $sent++;
            }
        }

        $this->info("Pickup QR reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
