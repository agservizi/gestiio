<?php

namespace App\Console\Commands;

use App\Http\Services\LuggageAgentSubscriptionService;
use Illuminate\Console\Command;

class ChargeLuggageAgentSubscriptions extends Command
{
    protected $signature = 'luggage:charge-agent-subscriptions';

    protected $description = 'Addebita il canone mensile Deposito Bagagli agli agenti abilitati';

    public function handle(LuggageAgentSubscriptionService $service): int
    {
        $stats = $service->renewDueSubscriptions();

        $this->info(sprintf(
            'Canoni deposito bagagli: %d agenti · %d addebitati · %d sospesi',
            $stats['processed'],
            $stats['charged'],
            $stats['suspended']
        ));

        return self::SUCCESS;
    }
}
